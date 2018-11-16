<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午9:12
 *
 * you can test use:
 *  php -S 127.0.0.1:5675 example/parse.php
 *
 * then you can access url: http://127.0.0.1:5675
 */

use Inhere\Route\Dispatcher\Dispatcher;
use Inhere\Route\Example\Controllers\RestController;
use Inhere\Route\Router;

require dirname(__DIR__) . '/test/boot.php';

function default_handler(array $args = []) {
    echo 'hello, welcome. you request URI: ' . $_SERVER['REQUEST_URI'];

    var_dump($args);
}

$router = new Router;

$router->get('/', 'default_handler');

$router->get('/user/info[/id/{int}]', 'default_handler');

$router->get('/my[/{name}[/{age}]]', 'default_handler', [
    'params' => [
        'age' => '\d+'
    ],
    'defaults' => [
        'name' => 'God',
        'age' => 25,
    ]
]);

$router->dispatch();
