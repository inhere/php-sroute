<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午9:12
 *
 * you can test use:
 *  php examples/object/swoole_svr.php
 *
 * then you can access url: http://127.0.0.1:5675
 */

use Inhere\Route\Dispatcher;
use Inhere\Route\ORouter;

require dirname(__DIR__) . '/simple-loader.php';

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Asia/Shanghai');

$router = new ORouter;

// set config
$router->setConfig([
    'ignoreLastSep' => true,
    'dynamicAction' => true,

    'tmpCacheNumber' => 100,

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


$router->get('/routes', function() use($router) {
    var_dump(
        $router->getStaticRoutes(),
        $router->getRegularRoutes(),
        $router->getVagueRoutes()
    );
});

require __DIR__ . '/routes.php';

$dispatcher = new Dispatcher([
    'dynamicAction' => true,
], function ($path, $method) use ($router) {
    return $router->match($path, $method);
});

// on notFound, output a message.
$dispatcher->on(Dispatcher::ON_NOT_FOUND, function ($path) {
    echo "the page $path not found!";
});

$server = new \Swoole\Http\Server('127.0.0.1', '5675', SWOOLE_BASE);
$server->set([

]);

$server->on('request', function($request, $response) use($dispatcher) {
    /** @var  \Swoole\Http\Response $response */
    $uri = $request->server['request_uri'];
    $method = $request->server['request_method'];

    fwrite(STDOUT, "request $method $uri\n");

    ob_start();
    $ret = $dispatcher->dispatch($uri, $method);
    $content = ob_get_clean();

    if (!$ret) {
        $ret = $content;
    }

    $response->end($ret);
});

echo "http server listen on http://127.0.0.1:5675\n";
$server->start();
