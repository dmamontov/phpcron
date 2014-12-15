<?php
interface DaemonInterface
{
    /*
     * Class constructor
     * @param $arg array - Argument daemon
     */
    public function __construct($arg);

    /*
     * Running tasks
     */
    public function run();

    /*
     * Signal processing process
     * @param $signo int - The type of signal completion
     * @param $pid int - Process ID
     * @param $status int - The status of the process
     */
    public function childSignalHandler($signo, $pid = null, $status = null);
}
