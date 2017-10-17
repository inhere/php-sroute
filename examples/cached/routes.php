<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/27
 * Time: 下午11:56
 *
 * @var \Inhere\Route\ORouter $router
 */

use Inhere\Route\Examples\Controllers\HomeController;

function home_handler() {
    echo 'hello, welcome';
}

function default_handler() {
    echo 'hello, welcome. you request URI: ' . $_SERVER['REQUEST_URI'];
}

function my_handler(array $args) {
    $args = array_merge([
        'name' => 'NO',
        'age' => 10
    ], $args);

    echo "hello, my name: {$args['name']}, my age: {$args['age']}";
}

$router->get('/', 'home_handler');

/*
match: /blog /saying
 */
$router->get('/{name}', 'default_handler', [
    'params' => [
        'name' => 'blog|saying'
    ]
]);

/*
match:
    /my/tom/78
    /my/tom
    /my
 */
$router->get('/my[/{name}[/{age}]]', 'my_handler', [
    'params' => [
        'age' => '\d+'
    ]
]);

/*
match:
    /hello/tom
    /hello
 */
$router->get('/hello[/{name}]', 'handler1',[
    'params' => [
        'name' => '\w+'
    ]
]);

// match POST
$router->post('/user/signUp', 'handler2');

$router->group('/user', function ($router) {
    /** @var \Inhere\Route\ORouter $router */
    $router->get('', 'handler3');

    $router->get('/index', 'handler4');
});

// match GET or POST
$router->map(['get', 'post'], '/user/login', 'handler5');

$router->get('/home', HomeController::class . '@index');

// can match '/home/test', but not match '/home'
// can also use defined patterns, @see $router->patterns
$router->any('/home/{act}', HomeController::class);

// can match '/home' '/home/test'
//$router->arg('/home[/{act}]', examples\HomeController::class);


