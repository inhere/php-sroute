<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/27
 * Time: 下午11:56
 *
 * @var \inhere\sroute\SRouter $router
 */

use inhere\sroute\examples\controllers\HomeController;

$router->get('/', function() {
    echo 'hello, welcome';
});

$router->get('/hello/(\w+)', function($arg) {
    echo "hello, $arg"; // 'john'
});

// match POST
$router->post('/user/signUp', function() {
    var_dump($_POST);
});

$router->group('/user', function ($router) {
    /** @var \inhere\sroute\SRouter $router */
    $router->get('/', function () {
        echo 'hello. you access: /user';
    });
    $router->get('/index', function () {
        echo 'hello. you access: /user/index';
    });
});

// match GET or POST
$router->map(['get', 'post'], '/user/login', function() {
    var_dump($_GET, $_POST);
});

$router->get('/home', 'inhere\sroute\examples\controllers\HomeController@index');

// can match '/home/test', but not match '/home'
//$router->any('/home/(\w+)', 'examples\HomeController');
// can also use defined patterns, @see $router->patterns
$router->any('/home/(:act)', HomeController::class);

// can match '/home' '/home/test'
//$router->any('/home(/\w+)?', examples\HomeController::class);

// on notFound, output a message.
//$router->on('notFound', function ($path) {
//    echo "the page $path not found!";
//});

