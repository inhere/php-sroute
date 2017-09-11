<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午7:52
 */

spl_autoload_register(function($class)
{
    if (0 === strpos($class,'Inhere\Route\Examples\\')) {
        $path = str_replace('\\', '/', substr($class, strlen('Inhere\Route\Examples\\')));
        $file = __DIR__ . "/{$path}.php";

        if (is_file($file)) {
            include $file;
        }

    } elseif (0 === strpos($class,'Inhere\Route\\')) {
        $path = str_replace('\\', '/', substr($class, strlen('Inhere\Route\\')));
        $file = dirname(__DIR__) . "/src/{$path}.php";

        if (is_file($file)) {
            include $file;
        }
    }
});
