<?php
class CronDaemon
{
    protected $stopServer = false;
    protected $currentTask = array();
    protected $lastRun = array();

    protected $settings = array();
    protected $debug = false;
    public $maxProcesses;

    private $files = array();
    private $tasks = array();

    public $textToNumber =  array(
        "month" => array(
            'jan' => 1,
            'feb' => 2,
            'mar' => 3,
            'apr' => 4,
            'may' => 5,
            'jun' => 6,
            'jul' => 7,
            'aug' => 8,
            'sep' => 9,
            'oct' => 10,
            'nov' => 11,
            'dec' => 12
        ),
        "dow" => array(
            'sun' => 0,
            'mon' => 1,
            'tue' => 2,
            'wed' => 3,
            'thu' => 4,
            'fri' => 5,
            'sat' => 6
        ),
        "max" => array(
            "min"   => 59,
            "hour"  => 23,
            "day"   => 31,
            "month" => 12,
            "dow"   => 6
        )
    );

    private $parentDate = array(
                "min"   => "hour",
                "hour"  => "day",
                "day"   => "month",
                "month" => "year"
            );

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
            $STDERR = fopen($dir . '/../../logs/' . $this->settings['logs']['daemon'], 'ab');
        } else {
            $this->debug = true;
        }

        $this->scanFileTask($dir . "/../../tasks/");
        $this->getLineTask();

        $this->maxProcesses = $this->settings['general']['max_processes'];

        pcntl_signal(SIGTERM, array($this, "childSignalHandler"));
        pcntl_signal(SIGCHLD, array($this, "childSignalHandler"));
    }

    public function run()
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            die('could not fork'.PHP_EOL);
        } elseif ($pid) {
            die('die parent process'.PHP_EOL);
        } elseif (is_array($this->tasks) && count($this->tasks) > 0) {
            while (!$this->stopServer) {
                foreach ($this->tasks as $key => $task) {
                    while (count($this->currentTask) >= $this->maxProcesses || count($this->currentTask) >= count($this->tasks)) {
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
        if ($this->debug) {
            $debugParams = array(
                'id'   => $id,
                'pid'  => getmypid()
            );
        }
        $currentDate = array(
            "min"   => intval($currentDate->format("i")),
            "hour"  => intval($currentDate->format("H")),
            "day"   => intval($currentDate->format("j")),
            "month" => intval($currentDate->format("n")),
            "dow"   => intval($currentDate->format("w")),
            "year"  => intval($currentDate->format("Y"))
        );
        
        if ($pid == -1) {
            return false;
        } elseif ($pid) {
            $this->currentTask[$pid] = true;
            $this->lastRun[ $id ] = $currentDate;
        } else {
            if ($this->debug) {
                $debugParams["point"] = "begin";
                $debugParams["memory"] = round(memory_get_usage()/1024/1024, 2);
                $debugParams["time"] = microtime(true);
                $this->logger('debug', $debugParams);
            }
            if ($this->checkTask($task, $id, $currentDate)) {
                exec($task["cmd"]);
            }
            if ($this->debug) {
                $debugParams["point"] = "end";
                $debugParams["memory"] = round(memory_get_usage()/1024/1024, 2) - $debugParams["memory"];
                $debugParams["time"] = microtime(true) - $debugParams["time"];
                $this->logger('debug', $debugParams);
            }
            exit();
        }

        return true;
    }

    private function checkTask($task, $id, $date)
    {
        foreach ($date as $type => $value) {
            if ($type == "year") {
                continue;
            }

            if (($type == "month" || $type == "dow") && is_string($task[ $type ]) && array_key_exists($task[ $type ], $this->textToNumber[ $type ])) {
                $task[ $type ] = $this->textToNumber[ $type ][ $task[ $type ] ];
            }

            // exemple: *
            if ($task[ $type ] == "*") {
                continue;
            }

            // exemple: 23
            if (is_numeric($task[ $type ]) && (int) $task[ $type ] <= $this->textToNumber["max"][ $type ] && $value == (int) $task[ $type ]) {
                if (!isset($this->lastRun[ $id ]) || (isset($this->lastRun[ $id ]) &&
                    (!isset($this->parentDate[ $type ]) || $this->lastRun[ $id ][ $this->parentDate[ $type ] ] != $date[ $this->parentDate[ $type ] ]))) {
                    continue;
                }
            }

            // exemple: */15
            if (preg_match("/^\*\/(\d+)$/", $task[ $type ], $out)) {
                if (is_numeric($out[1]) && (int) $out[1] <= $this->textToNumber["max"][ $type ] && ($value % (int) $out[1]) == 0) {
                    if (!isset($this->lastRun[ $id ]) || (isset($this->lastRun[ $id ]) && $this->lastRun[ $id ][ $type ] != $value)) {
                        continue;
                    }
                }
            }

            // exemple: 5-15
            if (preg_match("/^(\d+)\-(\d+)$/", $task[ $type ], $out)) {
                if (is_numeric($out[1]) && is_numeric($out[2]) && (int) $out[1] <= $this->textToNumber["max"][ $type ] && $out[2] <= $this->textToNumber["max"][ $type ] &&
                    $out[2] > $out[1] && $value >= $out[1] && $value <= $out[2]) {
                    if (!isset($this->lastRun[ $id ]) || (isset($this->lastRun[ $id ]) && $this->lastRun[ $id ][ $type ] != $value)) {
                        continue;
                    }
                }
            }

            // exemple: 5-15/2
            if (preg_match("/^(\d+)\-(\d+)\/(\d+)$/", $task[ $type ], $out)) {
                if (is_numeric($out[1]) && is_numeric($out[2]) && is_numeric($out[3]) && (int) $out[1] <= $this->textToNumber["max"][ $type ] &&
                    $out[2] <= $this->textToNumber["max"][ $type ] && $out[3] <= $this->textToNumber["max"][ $type ] &&
                    $out[2] > $out[1] && $value >= $out[1] && $value <= $out[2] && ($value % (int) $out[3]) == 0) {
                    if (!isset($this->lastRun[ $id ]) || (isset($this->lastRun[ $id ]) && $this->lastRun[ $id ][ $type ] != $value)) {
                        continue;
                    }
                }
            }

            // exemple: 5,7,12
            $out = explode(",", $task[ $type ]);
            if (count($out) > 1 && in_array($value, $out)) {
                $key = array_search($value, $out);
                if (is_numeric($out[ $key ]) && (int) $out[ $key ] <= $this->textToNumber["max"][ $type ]) {
                    if (!isset($this->lastRun[ $id ]) || (isset($this->lastRun[ $id ]) && $this->lastRun[ $id ][ $type ] != $value)) {
                        continue;
                    }
                }
            }

            return false;
        }

        return true;
    }

    private function getLineTask()
    {
        if (isset($this->files) && count($this->files) > 0) {
            $cronLine = array();
            foreach ($this->files as $files) {
                $cronLine = array_merge($cronLine, file($files, FILE_SKIP_EMPTY_LINES));
            }

            if (count($cronLine) > 0) {
                $this->tasks = array();
                foreach ($cronLine as $key => $line) {
                    if ($arrTasks = $this->buildTask($line)) {
                        $this->tasks[ $key ] = $arrTasks;
                    }
                }
            }
        }
    }

    private function buildTask($line)
    {
        $result = false;

        $numbers = array(
            'min'  =>'[0-5]?\d',
            'hour' =>'[01]?\d|2[0-3]',
            'day'  =>'0?[1-9]|[12]\d|3[01]',
            'month'=>'[1-9]|1[012]',
            'dow'  =>'[0-7]'
        );

        foreach ($numbers as $field => $number) {
            $range = "(" . $number . ")(-(" . $number . ")(\/\d+)?)?";
            $fieldReg[ $field ]= "\*(\/\d+)?|" . $range . "(," . $range . ")*";
        }

        $fieldReg['month'] .= '|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec';
        $fieldReg['dow'] .= '|mon|tue|wed|thu|fri|sat|sun';

        $fieldReg = '/^(' . implode(')\s(', $fieldReg) . ')\s(.*)$/';

        if (preg_match($fieldReg, trim($line), $matches)) {
            $result = array(
                "min"   => $matches[1],
                "hour"  => $matches[12],
                "day"   => $matches[23],
                "month" => $matches[34],
                "dow"   => $matches[45],
                "cmd"   => $matches[56]
            );
        }

        if ($result["min"] == "*" && $result["hour"] == "*" && $result["day"] == "*" && $result["month"] == "*" && $result["dow"] == "*") {
            return false;
        }

        return $result;
    }

    private function scanFileTask($dir)
    {
        $not = array(".", "..");

        $folders = scandir($dir);

        if (is_array($folders) && count($folders) > 0) {
            foreach ($folders as $folder) {
                if (!in_array($folder, $not)) {
                    if (is_dir($dir . $folder)) {
                        $this->scanFileTask($dir . $folder . '/');
                    } elseif (!isset($this->files) || !in_array($dir . $folder, $this->files)) {
                        $this->files[] = $dir . $folder;
                    }
                }
            }
        }
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

    private function logger($type, $params)
    {
        switch ($type) {
            case 'debug':
                $duration = $params["time"];
                $hours = (int)($duration/60/60);
                $minutes = (int)($duration/60)-$hours*60;
                $seconds = (int)$duration-$hours*60*60-$minutes*60;
                $path = dirname(__FILE__) . '/../../logs/' . $this->settings['logs']['debug'];
                error_log("\n[" . $params["pid"] . "]\n" .
                        $params["point"] . "-" . $params["id"] . ": [" . $seconds . "сек] [" . $params["memory"] . "MB]", 3, $path);
                break;
        }
    }
}
