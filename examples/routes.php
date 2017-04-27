<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/27
 * Time: 下午11:56
 *
 */

use inhere\sroute\SRoute;

SRoute::get('/', function() {
    echo 'hello';
});

SRoute::get('/test/(\w+)', function($arg) {
    echo "hello, $arg"; // 'john'
});

// match POST
SRoute::post('/user/signUp', function() {
    var_dump($_POST);
});

// match GET or POST
SRoute::map(['get', 'post'], '/user/login', function() {
    var_dump($_GET, $_POST);
});

// match any method
SRoute::get('/home', function() {
    echo 'hello';
});

SRoute::get('/index', 'examples\HomeController@index');

// access '/home/test' will call 'examples\HomeController::test()'
SRoute::any('/home/(\w+)', 'examples\HomeController');

// can match '/home' '/home/test'
SRoute::any('/home(/\w+)?', examples\HomeController::class);

// on notFound, output a message.
//SRoute::on('notFound', function ($path) {
//    echo "the page $path not found!";
//});

// set config
SRoute::config([
    'stopOnMatch' => true,
    'ignoreLastSep' => true,
    'dynamicAction' => true,
]);

// dispatch
SRoute::dispatch();
