<?php
class CronTools
{
    public static function logger($type, $params, $settings)
    {
        switch ($type) {
            case 'daemon':
                $path = dirname(__FILE__) . '/../../logs/' . $settings['logs']['daemon'];
                error_log(date("Y-m-d H:i:s") . " PID[" . getmypid() . "]: CMD (" . $params["cmd"] . ")\n", 3, $path);
                break;
            case 'error':
                $path = dirname(__FILE__) . '/../../logs/' . $settings['logs']['error'];
                error_log(date("Y-m-d H:i:s") . " PID[" . getmypid() . "]: CMD (" . $params["cmd"] . ") ERROR (" . $params["msg"] . ")\n", 3, $path);
                break;
        }
    }

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
}
