<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午7:52
 */

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
