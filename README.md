Cron Daemon
===========

Cron Daemon is a daemon to run tasks scheduled crowns written in php, works similar to crontab

## Mandatory requirements
* PHP version 5.0 or higher.
* Module installed "pcntl".
* All functions "pcntl" removed from the directive "disable_functions".

## Installation
1. [Download the archive](https://github.com/dmamontov/crondaemon/archive/master.zip) and extract.
2. Copy the folder "src" to the root of your project.

## Entries cron
Create an entry in the file tasks/main.cron analogously to Example

```sh
*/2 * * * * php /var/www/data/public/cron/test.php test1
*/5 * * * * php /var/www/data/public/cron/test.php test2
```
More information about cron entry can be found [here](http://www.codenet.ru/webmast/php/cron.php)

## Running the daemon
To start the daemon requires the console to run the script:
```sh
php /path/to/crondaemon.php parameter
```
or
```sh
/path/to/crondaemon parameter
```
### Valid parameters
* start
* stop
* restart
* help

## Connecting and starting the demon in your code
```php
require_once 'lib/Autoloader.php';
Autoloader::register();
$cron = new CronDaemon($argv);
$cron->run();
```