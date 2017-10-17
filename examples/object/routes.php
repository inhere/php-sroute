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

// uri /50be3774f6/arg1/arg2/arg3/arg4/arg5/arg6/arg7/arg8/arg9/850726135a
//'regex' => '#^/803d2fad34/([^/]+)1/([^/]+)2/([^/]+)3/([^/]+)4/([^/]+)5/([^/]+)6/([^/]+)7/([^/]+)8/([^/]+)9/961751ae0c$#',
$router->get('/50be3774f6/{arg1}/{arg2}/{arg3}/{arg4}/{arg5}/{arg6}/{arg7}/{arg8}/{arg9}/850726135a', function() {
    echo 'hello, welcome. test';
});

$router->get('/', function() {
    echo 'hello, welcome';
});

$router->post('/post', function() {
    echo 'hello, welcome. only allow POST';
});

$router->map(['post', 'delete'], '/pd', function() {
    echo 'hello, welcome. only allow POST,DELETE';
});

$router->get('/test[/optional]', function() {
    echo 'hello, welcome. match: /test[/optional]';
});

$router->get('/routes', function() use($router) {
    var_dump(
        $router->getStaticRoutes(),
        $router->getRegularRoutes(),
        $router->getVagueRoutes()
    );
});

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
$router->get('/my[/{name}[/{age}]]', function(array $args) {
    $args = array_merge([
        'name' => 'NO',
        'age' => 10
    ], $args);

    echo "hello, my name: {$args['name']}, my age: {$args['age']}";
}, [
    'params' => [
        'age' => '\d+'
    ]
]);

/*
match:
    /hello/tom
    /hello
 */
$router->get('/hello[/{name}]', function($args) {
    echo "hello, {$args['name']}"; // 'john'
},[
    'params' => [
        'name' => '\w+'
    ]
]);

// match POST
$router->post('/user/signUp', function() {
    var_dump($_POST);
});

$router->group('/user', function ($router) {
    /** @var \Inhere\Route\ORouter $router */
    $router->get('', function () {
        echo 'hello. you access: /user';
    });

    $router->get('/', function () {
        echo 'hello. you access: /user/';
    });

    $router->get('/index', function () {
        echo 'hello. you access: /user/index';
    });
});

// match GET or POST
$router->map(['get', 'post'], '/user/login', function() {
    var_dump($_GET, $_POST);
});

$router->get('/home', HomeController::class . '@index');

// can match '/home/test', but not match '/home'
// can also use defined patterns, @see $router->patterns
$router->any('/home/{act}', HomeController::class);

// can match '/home' '/home/test'
//$router->arg('/home[/{act}]', examples\HomeController::class);


