<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/28
 * Time: 上午12:00
 *
 * you can test use:
 *  php -S 127.0.0.1:5670 -t examples
 * Or use:
 * ./php_server
 *
 * then you can access url: http://127.0.0.1:5670
 */

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Asia/Shanghai');

spl_autoload_register(function($class)
{
    if (0 === strpos($class,'inhere\sroute\examples\\')) {
        $path = str_replace('\\', '/', substr($class, strlen('inhere\sroute\examples\\')));
        $file = __DIR__ . "/{$path}.php";

        if (is_file($file)) {
            include $file;
        }

    } elseif (0 === strpos($class,'inhere\sroute\\')) {
        $path = str_replace('\\', '/', substr($class, strlen('inhere\sroute\\')));
        $file = dirname(__DIR__) . "/{$path}.php";

        if (is_file($file)) {
            include $file;
        }
    }
});

require __DIR__ . '/routes.php';
