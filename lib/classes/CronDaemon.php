<?php
class CronDaemon extends CronEntries
{
    protected $stopServer = false;
    protected $currentTask = array();
    protected $lastRun = array();

    public $settings = array();
    protected $debug = false;
    public $maxProcesses;

    public function __construct()
    {
        $dir = dirname(__FILE__);
        $this->settings = parse_ini_file($dir . "/../../config/settings.ini", true);

        if ($this->settings['general']['debug'] == "") {
            ini_set('error_log', $dir . '/../../logs/' . $this->settings['logs']['error']);
            fclose(STDIN);
            fclose(STDOUT);
            fclose(STDERR);
            $STDIN = fopen('/dev/null', 'r');
            $STDOUT = fopen($dir . '/../../logs/' . $this->settings['logs']['application'], 'ab');
            $STDERR = fopen($dir . '/../../logs/' . $this->settings['logs']['error'], 'ab');
        } else {
            $this->debug = true;
        }

        $this->getAll();

        $this->maxProcesses = $this->settings['general']['max_processes'];

        pcntl_signal(SIGTERM, array($this, "childSignalHandler"));
        pcntl_signal(SIGCHLD, array($this, "childSignalHandler"));
    }

    public function run()
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            die();
        } elseif ($pid) {
            die();
        } elseif (is_array($this->tasks) && count($this->tasks) > 0) {
            while (!$this->stopServer) {
                foreach ($this->tasks as $key => $task) {
                    while (count($this->currentTask) >= $this->maxProcesses
                              || count($this->currentTask) >= count($this->tasks)) {
                        sleep(1);
                    }
                    $this->launchTask($task, $key, new DateTime());
                }
            }
        }
        posix_setsid();
    }

    protected function launchTask($task, $id, $currentDate)
    {
        $debugParams = array();
        $pid = pcntl_fork();
        $currentDate = CronTools::formatDateTime($currentDate);

        if ($pid == -1) {
            return false;
        } elseif ($pid && $currentDate !== false) {
            $this->currentTask[ $pid ] = true;
            $this->lastRun[ $id ] = $currentDate;
        } else {
            if ($currentDate !== false && $this->check($task, $id, $currentDate)) {
                exec($task["cmd"], $out, $return);
                if (is_int($return) && $return === 0) {
                    CronTools::logger('error', array('cmd' => $task["cmd"], 'msg' => $out[0]), $this->settings);
                } else {
                    CronTools::logger('daemon', array('cmd' => $task["cmd"]), $this->settings);
                }
            }
            exit();
        }

        return true;
    }

    public function childSignalHandler($signo, $pid = null, $status = null)
    {
        switch ($signo) {
            case SIGTERM:
                $this->stopServer = true;
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
