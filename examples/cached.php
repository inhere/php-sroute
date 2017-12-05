<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午9:12
 *
 * you can test use:
 *  php -S 127.0.0.1:5673 examples/cached.php
 *
 * then you can access url: http://127.0.0.1:5673
 */

use Inhere\Route\Dispatcher;
use Inhere\Route\CachedRouter;
use Inhere\Route\Examples\Controllers\RestController;

require __DIR__ . '/simple-loader.php';

$router = new CachedRouter([
    // 'ignoreLastSep' => true,
    // 'tmpCacheNumber' => 100,

//    'cacheFile' => '',
    'cacheFile' => __DIR__ . '/cached/routes-cache.php',
    'cacheEnable' => 0,

//    'matchAll' => '/', // a route path
//    'matchAll' => function () {
//        echo 'System Maintaining ... ...';
//    },

    // enable autoRoute
    // you can access '/demo' '/admin/user/info', Don't need to configure any route
    'autoRoute' =>  1,
    'controllerNamespace' => 'Inhere\Route\Examples\Controllers',
    'controllerSuffix' => 'Controller',
]);

function dump_routes() {
    global $router;
    $count = $router->count();
    echo "<h1>All Routes($count).</h1><h2>StaticRoutes:</h2><pre><code>\n";
    print_r($router->getStaticRoutes());
    echo "</code></pre><h2>RegularRoutes:</h2><pre><code>\n";
    print_r($router->getRegularRoutes());
    echo "</code></pre><h2>VagueRoutes:</h2>\n<pre><code>";
    print_r($router->getVagueRoutes());
    echo '</code></pre>';
}

$router->get('/routes', 'dump_routes');
$router->rest('/rest', RestController::class);

$router->any('*', 'main_handler');

/** @var array $routes */
$routes = require __DIR__ . '/some-routes.php';

foreach ($routes as $route) {
    // group
    if (is_array($route[1])) {
        $rs = $route[1];
        $router->group($route[0], function (CachedRouter $router) use($rs){
            foreach ($rs as $r) {
                // cannot cache the \Closure
                if (is_object($r[2])) {
                    continue;
                }
                $router->map($r[0], $r[1], $r[2], isset($r[3]) ? $r[3] : []);
            }
        });

        continue;
    }

    // cannot cache the \Closure
    if (is_object($route[2])) {
        continue;
    }

    $router->map($route[0], $route[1], $route[2], isset($route[3]) ? $route[3] : []);
}

$dispatcher = new Dispatcher([
    'dynamicAction' => true,
]);

// on notFound, output a message.
$dispatcher->on(Dispatcher::ON_NOT_FOUND, function ($path) {
    echo "the page $path not found!";
});

// $dispatcher->dispatch();

// var_dump($router->getConfig(),$router);die;
try {
    $router->dispatch($dispatcher);
} catch (Throwable $e) {
    var_dump($e);
}
