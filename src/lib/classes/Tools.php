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
 * Tools - Additional tools
 *
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2014 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: @package_version@
 * @link      https://github.com/dmamontov/crondaemon/blob/master/src/lib/classes/Tools.php
 * @since     Class available since Release 1.0.0
 */
class Tools
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
     * @param $date int - Date
     * @return array - The formatted date
     */
    public static function formatDateTime($date)
    {
        return array(
            "min"   => intval(date("i", $date)),
            "hour"  => intval(date("H", $date)),
            "day"   => intval(date("j", $date)),
            "month" => intval(date("n", $date)),
            "dow"   => intval(date("w", $date)),
            "year"  => intval(date("Y", $date))
        );
    }

    /*
     * The timing of the next run
     * @param $task array - Task parameters
     * @param $time int - Date
     * @return int - The number of seconds for the next run
     */
    public static function setSleep($task, $time)
    {
        if ($task['min'] != '*') {
            return ($time + 60 - ($time % 3600) % (60)) - $time;
        } elseif ($task['hour'] != '*') {
            return ($time + 3600 - ($time % 3600) % (60)) - $time;
        } elseif ($task['day'] != '*') {
            return (strtotime('+1 day', $time) - ($time % 3600) % (60)) - $time;
        } elseif ($task['month'] != '*') {
            return (strtotime('+1 month', $time) - ($time % 3600) % (60)) - $time;
        } elseif ($task['dow'] != '*') {
            return (strtotime('+1 week', $time) - ($time % 3600) % (60)) - $time;
        } else {
            return 0;
        }
    }
}
