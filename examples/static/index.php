<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/28
 * Time: 上午12:00
 *
 * you can test use:
 *  php -S 127.0.0.1:5670 -t examples/static
 *
 * then you can access url: http://127.0.0.1:5670
 */

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Asia/Shanghai');

use Inhere\Route\Dispatcher;
use Inhere\Route\SRouter;

require dirname(__DIR__) . '/simple-loader.php';

// set config
SRouter::setConfig([
    'ignoreLastSep' => true,

//    'intercept' => '/', // a route path
//    'intercept' => function () {
//        echo 'System Maintaining ... ...';
//    },

    // enable autoRoute
    // you can access '/demo' '/admin/user/info', Don't need to configure any route
    'autoRoute' =>  1,
    'controllerNamespace' => 'Inhere\Route\Examples\Controllers',
    'controllerSuffix' => 'Controller',
]);

require __DIR__ . '/routes.php';

$dispatcher = new Dispatcher([
    'dynamicAction' => true,
]);

// on notFound, output a message.
//$dispatcher->on(Dispatcher::ON_NOT_FOUND, function ($path) {
//    echo "the page $path not found!";
//});

// dispatch
SRouter::dispatch($dispatcher);
