<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午9:12
 *
 * you can test use:
 *  php -S 127.0.0.1:5671 -t examples/object
 *
 * then you can access url: http://127.0.0.1:5671
 */

use inhere\sroute\Dispatcher;
use inhere\sroute\ORouter;

require dirname(__DIR__) . '/simple-loader.php';

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Asia/Shanghai');

$router = new ORouter;

// set config
$router->setConfig([
    // 'ignoreLastSep' => true,
    // 'tmpCacheNumber' => 100,

//    'matchAll' => '/', // a route path
//    'matchAll' => function () { // a callback
//        echo 'System Maintaining ... ...';
//    },

    // enable autoRoute
    // you can access '/demo' '/admin/user/info', Don't need to configure any route
    'autoRoute' => 1,
    'controllerNamespace' => 'inhere\sroute\examples\controllers',
    'controllerSuffix' => 'Controller',
]);

require __DIR__ . '/routes.php';

// var_dump($router->getConfig(),$router);die;

$dispatcher = new Dispatcher([
    'dynamicAction' => true,
    // on notFound, output a message.
    Dispatcher::ON_NOT_FOUND => function ($path) {
        echo "the page $path not found!";
    }
]);

// OR register event by `Dispatcher::on()`
// $dispatcher->on(Dispatcher::ON_NOT_FOUND, function ($path) {
//     echo "the page $path not found!";
// });

/*
method 1

$dispatcher->setMatcher(function ($path, $method) use($router) {
    return $router->match($path, $method);
});
$dispatcher->dispatch();
 */

/*
method 2
 */
$router->dispatch($dispatcher);

/*
method 3

$router->dispatch([
    'dynamicAction' => true,
    Dispatcher::ON_NOT_FOUND => function ($path) {
        echo "the page $path not found!";
    }
]);
 */
