<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/27
 * Time: 下午11:56
 *
 */

use inhere\sroute\SRouter;

SRouter::get('/', function() {
    echo 'hello, welcome';
});

SRouter::get('/hello/{name}', function($arg) {
    echo "hello, $arg"; // 'john'
},[
    'tokens' => [
        'name' => '\w+'
    ]
]);

// match POST
SRouter::post('/user/signUp', function() {
    var_dump($_POST);
});

SRouter::group('/user', function () {
    SRouter::get('/', function () {
        echo 'hello. you access: /user';
    });
    SRouter::get('/index', function () {
        echo 'hello. you access: /user/index';
    });
});

// match GET or POST
SRouter::map(['get', 'post'], '/user/login', function() {
    var_dump($_GET, $_POST);
});

SRouter::get('/home', 'inhere\sroute\examples\controllers\HomeController@index');

// can match '/home/test', but not match '/home'
//SRouter::any('/home/(\w+)', 'examples\HomeController');
// can also use defined patterns, @see SRouter::$patterns
SRouter::any('/home/(:act)', 'inhere\sroute\examples\controllers\HomeController');

// can match '/home' '/home/test'
//SRouter::any('/home(/\w+)?', examples\HomeController::class);

// on notFound, output a message.
//SRouter::on('notFound', function ($path) {
//    echo "the page $path not found!";
//});

