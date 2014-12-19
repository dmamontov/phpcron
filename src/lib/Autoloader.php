<?php
/**
 * phpcron
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
 * @package   phpcron
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2014 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @since     File available since Release 1.0.0
 */

/**
 * Autoloader - Automatic connection classes
 *
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2014 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: @package_version@
 * @link      https://github.com/dmamontov/phpcron/blob/master/src/lib/Autoloader.php
 * @since     Class available since Release 1.0.0
 */
class Autoloader
{
    /*
     * Ways to automatically connect classes
     */
    static private $autoloadPaths = array(
        'classes/*',
        'interfaces/*'
    );

    /*
     * Automatic connection classes
     * @return boolean - The result of the connection
     */
    public static function register()
    {
        return spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    /*
     * Search class folders, with a successful search for a connection
     * @param $className string - The class name
     * @return boolean - The result of the connection
     */
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
