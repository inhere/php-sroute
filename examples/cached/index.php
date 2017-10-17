<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午9:12
 *
 * you can test use:
 *  php -S 127.0.0.1:5673 -t examples/cached
 *
 * then you can access url: http://127.0.0.1:5673
 */

use Inhere\Route\Dispatcher;
use Inhere\Route\CachedRouter;

require dirname(__DIR__) . '/simple-loader.php';

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Asia/Shanghai');

$router = new CachedRouter([
    // 'ignoreLastSep' => true,
    // 'tmpCacheNumber' => 100,

//    'cacheFile' => '',
    'cacheFile' => __DIR__ . '/routes-cache.php',
    'cacheEnable' => 0,

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
$dispatcher->on(Dispatcher::ON_NOT_FOUND, function ($path) {
    echo "the page $path not found!";
});

// $dispatcher->dispatch();

// var_dump($router->getConfig(),$router);die;
$router->dispatch($dispatcher);
