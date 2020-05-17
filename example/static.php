<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/28
 * Time: 上午12:00
 *
 * you can test use:
 *  php -S 127.0.0.1:5670 example/static.php
 *
 * then you can access url: http://127.0.0.1:5670
 */

use Inhere\Route\Dispatcher\Dispatcher;
use Inhere\Route\SRouter;

require dirname(__DIR__) . '/test/boot.php';

// set config
SRouter::config([
    'ignoreLastSlash' => true,
    // enable autoRoute
    // you can access '/demo' '/admin/user/info', Don't need to configure any route
    'autoRoute' => 1,
    'controllerNamespace' => 'Inhere\RouteTest\Controllers',
    'controllerSuffix' => 'Controller',
]);

/** @var array $routes */
$routes = require __DIR__ . '/some-routes.php';

foreach ($routes as $route) {
    // group
    if (is_array($route[1])) {
        $rs = $route[1];
        SRouter::group($route[0], function () use ($rs) {
            foreach ($rs as $r) {
                SRouter::map($r[0], $r[1], $r[2], $r[3] ?? [], $r[4] ?? []);
            }
        });

        continue;
    }

    SRouter::map($route[0], $route[1], $route[2], $route[3] ?? [], $route[4] ?? []);
}

SRouter::get('routes', function () {
    $string = SRouter::getRouter()->__toString();
    echo "<pre><code>{$string}</code></pre>";
});

$dispatcher = new Dispatcher([
    'dynamicAction' => true,
]);

// on notFound, output a message.
//$dispatcher->on(Dispatcher::ON_NOT_FOUND, function ($path) {
//    echo "the page $path not found!";
//});

// dispatch
SRouter::dispatch($dispatcher);
