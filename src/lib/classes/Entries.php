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
 * Entries - Processes the cron job file.
 *
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2014 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: @package_version@
 * @link      https://github.com/dmamontov/crondaemon/blob/master/src/lib/classes/Entries.php
 * @since     Class available since Release 1.0.0
 */
abstract class Entries
{
    /*
     * Regular expression to retrieve the parameters of the tasks
     */
    const REGEXP = "/^(\*(\/\d+)?|([0-5]?\d)(-([0-5]?\d)(\/\d+)?)?(,([0-5]?\d)(-([0-5]?\d)(\/\d+)?)?)*)\s(\*(\/\d+)?|([01]?\d|2[0-3])(-([01]?\d|2[0-3])(\/\d+)?)?(,([01]?\d|2[0-3])(-([01]?\d|2[0-3])(\/\d+)?)?)*)\s(\*(\/\d+)?|(0?[1-9]|[12]\d|3[01])(-(0?[1-9]|[12]\d|3[01])(\/\d+)?)?(,(0?[1-9]|[12]\d|3[01])(-(0?[1-9]|[12]\d|3[01])(\/\d+)?)?)*)\s(\*(\/\d+)?|([1-9]|1[012])(-([1-9]|1[012])(\/\d+)?)?(,([1-9]|1[012])(-([1-9]|1[012])(\/\d+)?)?)*|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)\s(\*(\/\d+)?|([0-7])(-([0-7])(\/\d+)?)?(,([0-7])(-([0-7])(\/\d+)?)?)*|mon|tue|wed|thu|fri|sat|sun)\s(.*)$/";

    /*
     * A list of folders to be scanned
     */
    private $arPath = array(
        '/../../tasks/'
    );

    /*
     * Task list
     */
    public $tasks = array();

    /*
     * Additional parameters dates
     */
    private $txtParams = array(
        'month' => array(
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4,
            'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8,
            'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12
        ),
        'dow' => array(
            'sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6
        ),
        'max' => array(
            'min' => 59, 'hour' => 23, 'day' => 31, 'month' => 12, 'dow' => 6
        )
    );

    /*
     * Add folder to scan files
     * @param $path string - The path to the folder
     * @return int - Serial number of folders
     */
    public function addPath($path = null)
    {
        if (!is_null($path) && !file_exists(dirname(__FILE__) . $path)
               && is_dir(dirname(__FILE__) . $path)) {
            return array_push($this->arPath, $path);
        }

        return false;
    }

    /*
     * Get all the tasks
     * @return array - Tasks and parameters
     */
    public function getAll()
    {
        return $this->getEntries($this->getFile());
    }

    /*
     * Checking task
     * @param $task array - Task parameters
     * @param $id int - Task identifier
     * @param $currentDate DateTime - The current date
     * @return boolean - Verification result
     */
    public function check($task, $id, $date)
    {
        $date = Tools::formatDateTime($date);
        foreach ($date as $type => $value) {
            if ($type == 'year') {
                continue;
            }

            if (($type == 'month' || $type == 'dow') && is_string($task[ $type ])
                    && array_key_exists($task[ $type ], $this->txtParams[ $type ])) {
                $task[ $type ] = $this->txtParams[ $type ][ $task[ $type ] ];
            }

            // example: *
            if ($task[ $type ] == '*') {
                continue;
            }

            // example: 23
            if (is_numeric($task[ $type ]) && (int) $task[ $type ] <= $this->txtParams['max'][ $type ]
                    && $value == (int) $task[ $type ]) {
                continue;
            }
    
            // example: */15
            if (preg_match("/^\*\/(\d+)$/", $task[ $type ], $out)) {
                if (is_numeric($out[1]) && (int) $out[1] <= $this->txtParams['max'][ $type ]
                        && ($value % (int) $out[1]) == 0) {
                    continue;
                }
            }
    
            // example: 5-15
            if (preg_match("/^(\d+)\-(\d+)$/", $task[ $type ], $out)) {
                if (is_numeric($out[1]) && is_numeric($out[2]) && (int) $out[1] <= $this->txtParams['max'][ $type ]
                        && $out[2] <= $this->txtParams['max'][ $type ] && $out[2] > $out[1] && $value >= $out[1]
                        && $value <= $out[2]) {
                    continue;
                }
            }
    
            // example: 5-15/2
            if (preg_match("/^(\d+)\-(\d+)\/(\d+)$/", $task[ $type ], $out)) {
                if (is_numeric($out[1]) && is_numeric($out[2]) && is_numeric($out[3])
                        && (int) $out[1] <= $this->txtParams['max'][ $type ]
                        && $out[2] <= $this->txtParams['max'][ $type ] && $out[3] <= $this->txtParams['max'][ $type ]
                        && $out[2] > $out[1] && $value >= $out[1] && $value <= $out[2]
                        && ($value % (int) $out[3]) == 0) {
                    continue;
                }
            }
    
            // example: 5,7,12
            $out = explode(',', $task[ $type ]);
            if (count($out) > 1 && in_array($value, $out)) {
                $key = array_search($value, $out);
                if (is_numeric($out[ $key ]) && (int) $out[ $key ] <= $this->txtParams['max'][ $type ]) {
                    continue;
                }
            }
    
            return false;
        }
    
        return true;
    }

    /*
     * Get all the tasks
     * @param $files array - Files for scanning
     * @return array - Tasks and parameters
     */
    private function getEntries($files)
    {
        if (isset($files) && count($files) > 0) {
            $result = array();
            $lines = array();
            foreach ($files as $file) {
                $lines = array_merge($lines, file($file, FILE_SKIP_EMPTY_LINES));
            }
            if (count($lines) > 0) {
                foreach ($lines as $line) {
                    if (preg_match(self::REGEXP, trim($line), $params)) {
                        $param = array(
                                     'min'   => $params[1],
                                     'hour'  => $params[12],
                                     'day'   => $params[23],
                                     'month' => $params[34],
                                     'dow'   => $params[45],
                                     'cmd'   => $params[56]
                                 );
                        $countParams = array_count_values($param);
                        if (!isset($countParams['*']) || ($countParams['*'] < 5 && $param['cmd'] != '*')) {
                            array_push($result, $param);
                        }
                    }
                }
                if (count($result) > 0) {
                    $this->tasks = $result;
                    return $result;
                }
            }
        }

        return false;
    }

    /*
     * Get a list of files with tasks
     * @return array - List files
     */
    private function getFile()
    {
        $result = array();
        $not = array('.', '..');

        $dir = dirname(__FILE__);

        foreach ($this->arPath as $path) {
            $files = scandir($dir . $path);
            if (is_array($files) && count($files) > 0) {
                foreach ($files as $file) {
                    if (in_array($file, $not)) {
                        continue;
                    }
                    if (!is_dir($dir . $path . $file) && stripos($file, '.cron')) {
                        array_push($result, $dir . $path . $file);
                    }
                }
            }
        }

        return $result;
    }
}
