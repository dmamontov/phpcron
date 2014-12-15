<?php
require_once 'lib/Autoloader.php';
Autoloader::register();
$cron = new CronDaemon($argv);
$cron->run();
