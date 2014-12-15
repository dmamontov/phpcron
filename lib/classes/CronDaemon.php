<?php
class CronDaemon extends CronEntries implements DaemonInterface
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
     * Running tasks
     */
    protected $lastRun = array();

    /*
     * Debug mode
     */
    public $settings = array();

    /*
     * General Settings
     */
    protected $debug = false;

    /*
     * The maximum number of running processes
     */
    public $maxProcesses;

    /*
     * Shared variable
     */
    const STATUS = 1;

    /*
     * The unique identifier of the memory block
     */
    private $shmId;

    /*
     * Class constructor
     * @param $arg array - Console argument
     */
    public function __construct($arg = array())
    {
        CronDaemonCommand::run($arg, __FILE__);

        if (!extension_loaded('pcntl')) {
            echo "Starting the daemon can not: Do not set the library \"pcntl\"";
            exit();
        }

        if (version_compare(phpversion(), '5.3.0', '>=')) {
            pcntl_signal_dispatch();
        } else {
            declare (ticks=1);
        }

        $this->shmId = shm_attach(ftok(__FILE__, 'A'));

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

            CronTools::setName("cron-daemon");

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

    /*
     * Execution of tasks
     * @param $task array - Task parameters
     * @param $id int - Task identifier
     * @param $currentDate DateTime - The current date
     */
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
            if (shm_has_var($this->shmId, self::STATUS) && !shm_get_var($this->shmId, self::STATUS)) {
                $this->stopServer = true;
            }
        } else {
            if ($currentDate !== false && $this->check($task, $id, $currentDate)) {
                exec($task["cmd"]);
                CronTools::logger('daemon', array('cmd' => $task["cmd"]), $this->settings);
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
