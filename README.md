Cron Daemon
===========

Cron Daemon is a daemon to run tasks scheduled cron written in php, works similar to crontab

## Requirements
* PHP version **5.0** or **higher**.
* Module installed "**pcntl**" and "**posix**".
* All functions "**pcntl**" and "**posix**" removed from the directive "**disable_functions**".

## Installation
Download the archive and extract.
```sh
wget https://github.com/dmamontov/crondaemon/archive/master.zip
unzip master.zip
```
or
```sh
git-clone https://github.com/dmamontov/crondaemon.git
```
Copy the folder "src" to the root of your project.

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
* start [-f]
* stop
* restart
* status
* help

## Connecting and starting the demon in your code
```php
require_once 'lib/Autoloader.php';
Autoloader::register();
$cron = new CronDaemon($argv);
$cron->run();
```
