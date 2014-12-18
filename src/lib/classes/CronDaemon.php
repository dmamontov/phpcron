<?php
/**
 * crondaemon
 *
 * Copyright (c) 2014, Dmitry Mamontov <d.slonyara@gmail.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Dmitry Mamontov nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * 'AS IS' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package   crondaemon
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2014 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @since     File available since Release 1.0.0
 */

/**
 * CronDaemon - The main class
 *
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2014 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: @package_version@
 * @link      https://github.com/dmamontov/crondaemon/blob/master/src/lib/classes/CronDaemon.php
 * @since     Class available since Release 1.0.0
 */
class CronDaemon extends Entries implements DaemonInterface
{
    /*
     * Exit status of the parent process
     */
    protected $stopServer = false;

    /*
     * Running tasks
     */
    protected $currentTask = array();

    /*
     * Debug mode
     */
    public $settings = array();

    /*
     * General Settings
     */
    protected $debug = false;

    /*
     * Current directory
     */
    private $dir;

    /*
     * Class constructor
     * @param $arg array - Console argument
     */
    public function __construct($arg = array())
    {
        if (!extension_loaded('pcntl')) {
            echo 'Starting the daemon can not: Do not set the library "pcntl"';
            exit();
        }

        Process::run($arg);

        if (version_compare(phpversion(), '5.3.0', '>=')) {
            pcntl_signal_dispatch();
        } else {
            declare (ticks=1);
        }

        $this->dir = dirname(__FILE__);
        $this->settings = parse_ini_file(strtr('*/../../config/settings.ini', array('*' => $this->dir)), true);

        if ($this->settings['general']['debug'] == '') {
            ini_set('error_log', strtr('*/../../logs/#', array(
                                                            '*' => $this->dir,
                                                            '#' => $this->settings['logs']['error']
                                                         )));
            fclose(STDIN);
            fclose(STDOUT);
            fclose(STDERR);
            $STDIN = fopen('/dev/null', 'r');
            $STDOUT = fopen(strtr('*/../../logs/#', array(
                                                            '*' => $this->dir,
                                                            '#' => $this->settings['logs']['application']
                                                         )), 'ab');
            $STDERR = fopen(strtr('*/../../logs/#', array(
                                                            '*' => $this->dir,
                                                            '#' => $this->settings['logs']['error']
                                                         )), 'ab');
        } else {
            $this->debug = true;
        }

        $this->getAll();

        pcntl_signal(SIGTERM, array($this, 'childSignalHandler'));
        pcntl_signal(SIGCHLD, array($this, 'childSignalHandler'));
    }

    /*
     * Running tasks
     */
    public function run()
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            exit();
        } elseif ($pid) {
            exit();
        } elseif (is_array($this->tasks) && count($this->tasks) > 0) {
            file_put_contents(strtr('*/../../crondaemon.pid', array('*' => $this->dir)), getmypid());

            while (!$this->stopServer) {
                foreach ($this->tasks as $key => $task) {
                    while (count($this->currentTask) >= count($this->tasks)) {
                        sleep(1);
                    }
                    if (in_array($key, $this->currentTask)) {
                        continue;
                    } else {
                        $this->launchTask($task, $key);
                    }
                }
            }
        }
        posix_setsid();
    }

    /*
     * Execution of tasks
     * @param $task array - Task parameters
     * @param $id int - Task identifier
     */
    protected function launchTask($task, $id)
    {
        $debugParams = array();
        $pid = pcntl_fork();

        if ($pid == -1) {
            return false;
        } elseif ($pid) {
            $this->currentTask[ $pid ] = $id;
        } else {
            while (!$this->stopServer) {
                $time = time();
                if ($this->check($task, $id, $time)) {
                    exec($task['cmd']);
                    Tools::logger('daemon', array('cmd' => $task['cmd']), $this->settings);
                    sleep(Tools::setSleep($task, $time));
                    exit();
                } else {
                    sleep(Tools::setSleep($task, $time));
                }
            }
            exit();
        }

        return true;
    }

    /*
     * Signal processing process
     * @param $signo int - The type of signal completion
     * @param $pid int - Process ID
     * @param $status int - The status of the process
     */
    public function childSignalHandler($signo, $pid = null, $status = null)
    {
        switch ($signo) {
            case SIGTERM:
                $this->stopServer = true;
                if (file_exists(strtr('*/../../crondaemon.pid', array('*' => $this->dir)))) {
                    unlink(strtr('*/../../crondaemon.pid', array('*' => $this->dir)));
                }
                break;
            case SIGCHLD:
                if (!$pid) {
                    $pid = pcntl_waitpid(-1, $status, WNOHANG);
                }

                while ($pid > 0) {
                    if ($pid && isset($this->currentTask[$pid])) {
                        unset($this->currentTask[$pid]);
                    }
                    $pid = pcntl_waitpid(-1, $status, WNOHANG);
                }
                break;
            default:
                break;
        }
    }
}
