<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/17
 * Time: 下午10:19
 */

require dirname(__DIR__) . '/simple-loader.php';

$router = new \Inhere\Route\Router();

$router->get('/', function () {
    echo 'hello, welcome';
});

$router->get('/test', 'test_handler');

$router->get('/routes', function () use ($router) {
    var_dump(
        $router->getStaticRoutes()
    );
});

$router->get('/{name}', 'default_handler', [
    'params' => [
        'name' => 'blog|saying'
    ]
]);

$encoded = serialize($router->getStaticRoutes());

var_dump($encoded);

$decoded = unserialize($encoded, []);

var_dump($decoded);

// Fatal error: Uncaught Exception: Serialization of 'Closure' is not allowed in .../serialize.php on line 31
