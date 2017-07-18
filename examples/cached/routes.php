<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/27
 * Time: 下午11:56
 *
 * @var \inhere\sroute\ORouter $router
 */

use inhere\sroute\examples\controllers\HomeController;

function home_handler() {
    echo 'hello, welcome';
}

function my_handler($name='NO', $age = 10) {
    echo "hello, my name: $name, my age: $age"; // 'john'
}

$router->get('/', 'home_handler');

/*
match:
    /my/tom/78
    /my/tom
    /my
 */
$router->get('/my[/{name}[/{age}]]', 'my_handler', [
    'tokens' => [
        'age' => '\d+'
    ]
]);

/*
match:
    /hello/tom
    /hello
 */
$router->get('/hello[/{name}]', 'handler1',[
    'tokens' => [
        'name' => '\w+'
    ]
]);

// match POST
$router->post('/user/signUp', 'handler2');

$router->group('/user', function ($router) {
    /** @var \inhere\sroute\ORouter $router */
    $router->get('', 'handler3');

    $router->get('/index', 'handler4');
});

// match GET or POST
$router->map(['get', 'post'], '/user/login', 'handler5');

$router->get('/home', 'inhere\sroute\examples\controllers\HomeController@index');

// can match '/home/test', but not match '/home'
// can also use defined patterns, @see $router->patterns
$router->any('/home/{act}', HomeController::class);

// can match '/home' '/home/test'
//$router->arg('/home[/{act}]', examples\HomeController::class);


