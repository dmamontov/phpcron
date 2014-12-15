<?php
class CronDaemonCommand
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
