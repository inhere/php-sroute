<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午9:12
 *
 * you can test use:
 *  php -S 127.0.0.1:5673 example/cached.php
 *
 * then you can access url: http://127.0.0.1:5673
 */

use Inhere\Route\CachedRouter;
use Inhere\Route\Dispatcher\Dispatcher;
use Inhere\Route\RouterInterface;

require dirname(__DIR__) . '/test/boot.php';

$router = new CachedRouter([
    // 'ignoreLastSlash' => true,

    // 'cacheFile' => '',
    'cacheFile' => __DIR__ . '/cached/routes-cache.php',
    'cacheEnable' => 1,

    // enable autoRoute
    // you can access '/demo' '/admin/user/info', Don't need to configure any route
    'autoRoute' => 1,
    'controllerNamespace' => 'Inhere\RouteTest\Controllers',
    'controllerSuffix' => 'Controller',
]);

function dump_routes()
{
    global $router;
    echo "<pre><code>{$router->__toString()}</code></pre>";
}

$router->get('/routes', 'dump_routes');
$router->any('*', 'main_handler');

/** @var array $routes */
$routes = require __DIR__ . '/some-routes.php';
foreach ($routes as $route) {
    // group
    if (is_array($route[1])) {
        $rs = $route[1];
        $router->group($route[0], function (RouterInterface $router) use ($rs) {
            foreach ($rs as $r) {
                // cannot cache the \Closure
                if (is_object($r[2])) {
                    continue;
                }
                $router->map($r[0], $r[1], $r[2], $r[3] ?? [], $r[4] ?? []);
            }
        });

        continue;
    }

    // cannot cache the \Closure
    if (is_object($route[2])) {
        continue;
    }

    $router->map($route[0], $route[1], $route[2], $route[3] ?? [], $route[4] ?? []);
}
$router->completed();

$dispatcher = new Dispatcher([
    'dynamicAction' => true,
]);

// on notFound, output a message.
$dispatcher->on(Dispatcher::ON_NOT_FOUND, function ($path) {
    echo "the page $path not found!";
});

$dispatcher->setRouter($router);

// var_dump($router->getConfig(),$router);die;
try {
    // $router->dispatch($dispatcher);
    $dispatcher->dispatchUri();
} catch (Throwable $e) {
    var_dump($e);
}
