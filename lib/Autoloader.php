<?php
class Autoloader
{
    static private $autoloadPaths = array(
        'classes/*',
        'interfaces/*'
    );

    public static function register()
    {
        return spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    private static function autoload($className)
    {
        foreach (self::$autoloadPaths as $path) {
            $path = dirname(__FILE__) . "/" . str_replace('*', $className, $path);
            if (file_exists($path . '.php')) {
                require_once($path . '.php');
                return class_exists($className, false) || interface_exists($className, false);
            }
        }

        return false;
    }
}
