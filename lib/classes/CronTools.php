<?php
class CronTools
{
    /*
     * Logging results
     * @param $type string - Type of log
     * @param $params array - Parameters
     * @param $settings array - General Settings
     */
    public static function logger($type, $params, $settings)
    {
        switch ($type) {
            case 'daemon':
                $path = dirname(__FILE__) . '/../../logs/' . $settings['logs']['daemon'];
                error_log(date("Y-m-d H:i:s") . " PID[" . getmypid() . "]: CMD (" . $params["cmd"] . ")\n", 3, $path);
                break;
            case 'error':
                $path = dirname(__FILE__) . '/../../logs/' . $settings['logs']['error'];
                $error = date("Y-m-d H:i:s") . " PID[" . getmypid() . "]: ";
                $error .= "CMD (" . $params["cmd"] . ") ";
                $error .= "ERROR (" . $params["msg"] . ")\n";
                error_log($error, 3, $path);
                break;
        }
    }

    /*
     * Formatting Dates
     * @param $date DateTime - Date
     * @return array - The formatted date
     */
    public static function formatDateTime($date)
    {
        if ($date instanceof DateTime) {
            return array(
                "min"   => intval($date->format("i")),
                "hour"  => intval($date->format("H")),
                "day"   => intval($date->format("j")),
                "month" => intval($date->format("n")),
                "dow"   => intval($date->format("w")),
                "year"  => intval($date->format("Y"))
            );
        } else {
            return false;
        }
    }

    /*
     * Set process name
     * @param $name string - Process name
     */
    public static function setName($name)
    {
        if (function_exists("cli_set_process_title")) {
            cli_set_process_title($name);
        } elseif (function_exists("setproctitle")) {
            setproctitle($name);
        }
    }
}
