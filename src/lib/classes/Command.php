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
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
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
 * Command - Processing parameters to be passed CronDaemon
 *
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2014 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: @package_version@
 * @link      https://github.com/dmamontov/crondaemon/blob/master/src/lib/classes/Command.php
 * @since     Class available since Release 1.0.0
 */
class Command
{
    /*
     * Shared variable
     */
    const STATUS = 1;

    /*
     * Running Commands
     * @param $arg array - Console argument
     * @param $file __FILE__
     */
    public function run($arg, $file)
    {
        switch ($arg[1]) {
            case "restart":
            case "start":
                self::start($file);
                break;
            case "stop":
                self::stop($file);
                break;
            case "help":
            default:
                self::help();
                break;
        }
    }

    /*
     * Command start
     * @param $file __FILE__
     */
    public static function start($file)
    {
        $shmId = shm_attach(ftok($file, 'A'));

        if (shm_has_var($shmId, self::STATUS) && shm_get_var($shmId, self::STATUS)) {
            shm_put_var($shmId, 1, false);
            sleep(1);
            shm_put_var($shmId, 1, true);
        } else {
            shm_put_var($shmId, 1, true);
        }
    }

    /*
     * Command stop
     * @param $file __FILE__
     */
    public static function stop($file)
    {
        $shmId = shm_attach(ftok($file, 'A'));
    
        if (shm_has_var($shmId, self::STATUS) && shm_get_var($shmId, self::STATUS)) {
            shm_put_var($shmId, 1, false);
        }
        sleep(1);
        exit();
    }

    /*
     * Command help
     */
    public static function help()
    {
        echo "Usage example:\n";
        echo "php cli.php parameter\n\n";
        echo "Parameters:\n";
        echo "\tstart\t\tStart Daemon\n";
        echo "\tstop\t\tStop Daemon\n";
        echo "\trestart\t\tRestart Daemon\n";
        echo "\thelp\t\tHelp\n";
        exit();
    }
}
