<?php
if (version_compare(phpversion(), '5.3.0', '>=')) {
    pcntl_signal_dispatch();
} else {
    declare (ticks=1);
}
require_once 'lib/Autoloader.php';
Autoloader::register();
$cron = new CronDaemon();
$cron->run();
