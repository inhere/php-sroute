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
    echo 'hello, welcome';
});

SRoute::get('/hello/(\w+)', function($arg) {
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

SRoute::get('/home', 'inhere\sroute\examples\controllers\HomeController@index');

// can match '/home/test', but not match '/home'
//SRoute::any('/home/(\w+)', 'examples\HomeController');
// can also use defined patterns, @see SRoute::$patterns
 SRoute::any('/home/(:act)', 'inhere\sroute\examples\controllers\HomeController');

// can match '/home' '/home/test'
//SRoute::any('/home(/\w+)?', examples\HomeController::class);

// on notFound, output a message.
//SRoute::on('notFound', function ($path) {
//    echo "the page $path not found!";
//});

// set config
SRoute::config([
    'stopOnMatch' => true,
    'ignoreLastSep' => true,
    'dynamicAction' => true,

//    'matchAll' => '/', // a route path
//    'matchAll' => function () {
//        echo 'System Maintaining ... ...';
//    },

    // enable autoRoute
    // you can access '/demo' '/admin/user/info', Don't need to configure any route
    'autoRoute' => [
        'enable' => 1,
        'controllerNamespace' => 'inhere\sroute\examples\controllers',
        'controllerSuffix' => 'Controller',
    ],
]);

// dispatch
SRoute::dispatch();
