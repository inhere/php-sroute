<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/27
 * Time: 下午11:56
 *
 */

use Inhere\Route\Examples\Controllers\HomeController;
use Inhere\Route\SRouter;

SRouter::get('/', function() {
    echo 'hello, welcome';
});

/*
match: /blog /saying
 */
SRouter::get('/{name}', 'default_handler', [
    'params' => [
        'name' => 'blog|saying'
    ]
]);

SRouter::get('/hello/{name}', function($args) {
    echo "hello, {$args['name']}"; // 'john'
},[
    'params' => [
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

SRouter::get('/home', HomeController::class . '@index');

// can match '/home/test', but not match '/home'
// can also use defined patterns, @see SRouter::$patterns
SRouter::any('/home/{act}', HomeController::class);

// can match '/home' '/home/test'
//SRouter::any('/home[/{act}]', examples\HomeController::class);

// on notFound, output a message.
//SRouter::on('notFound', function ($path) {
//    echo "the page $path not found!";
//});

