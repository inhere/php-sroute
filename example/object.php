<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午9:12
 *
 * you can test use:
 *  php -S 127.0.0.1:5671 example/object.php
 *
 * then you can access url: http://127.0.0.1:5671
 */

use Inhere\Route\Dispatcher\Dispatcher;
use Inhere\Route\Example\Controllers\RestController;
use Inhere\Route\ORouter;

require dirname(__DIR__) . '/test/boot.php';

$router = new ORouter;

// set config
$router->setConfig([
    // 'ignoreLastSlash' => true,
    // 'tmpCacheNumber' => 100,

    // 'matchAll' => '/', // a route path
    // 'matchAll' => function () { // a callback
    //     echo 'System Maintaining ... ...';
    // },

    // enable autoRoute
    // you can access '/demo' '/admin/user/info', Don't need to configure any route
    'autoRoute' => 1,
    'controllerNamespace' => 'Inhere\Route\Example\Controllers',
    'controllerSuffix' => 'Controller',
]);

$router->get('/routes', function() {
    global $router;

    echo "<h1>All Routes.</h1><pre><h2>StaticRoutes:</h2>\n";
    print_r($router->getStaticRoutes());
    echo "<h2>RegularRoutes:</h2>\n";
    print_r($router->getRegularRoutes());
    echo "<h2>VagueRoutes:</h2>\n";
    print_r($router->getVagueRoutes());
    echo '</pre>';
});

/** @var array $routes */
$routes = require __DIR__ . '/some-routes.php';

foreach ($routes as $route) {
    // group
    if (is_array($route[1])) {
        $rs = $route[1];
        $router->group($route[0], function (ORouter $router) use($rs){
            foreach ($rs as $r) {
                $router->map($r[0], $r[1], $r[2], $r[3] ?? []);
            }
        });

        continue;
    }

    $router->map($route[0], $route[1], $route[2], $route[3] ?? []);
}

$router->rest('/rest', RestController::class);

// $router->any('*', function () {
//     echo "This is fallback handler\n";
// });

// var_dump($router);die;

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

$dispatcher->setRouter($router);
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
