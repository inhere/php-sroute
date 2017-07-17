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

// uri /50be3774f6/arg1/arg2/arg3/arg4/arg5/arg6/arg7/arg8/arg9/850726135a
//'regex' => '#^/803d2fad34/([^/]+)1/([^/]+)2/([^/]+)3/([^/]+)4/([^/]+)5/([^/]+)6/([^/]+)7/([^/]+)8/([^/]+)9/961751ae0c$#',
$router->get('/50be3774f6/{arg1}/{arg2}/{arg3}/{arg4}/{arg5}/{arg6}/{arg7}/{arg8}/{arg9}/850726135a', function() {
    echo 'hello, welcome. test';
});

$router->get('/', function() {
    echo 'hello, welcome';
});

/*
match:
    /my/tom/78
    /my/tom
    /my
 */
$router->get('/my[/{name}[/{age}]]', function($name='NO', $age = 10) {
    echo "hello, my name: $name, my age: $age"; // 'john'
}, [
    'tokens' => [
        'age' => '\d+'
    ]
]);

/*
match:
    /hello/tom
    /hello
 */
$router->get('/hello[/{name}]', function($name='NO') {
    echo "hello, $name"; // 'john'
},[
    'tokens' => [
        'name' => '\w+'
    ]
]);

// match POST
$router->post('/user/signUp', function() {
    var_dump($_POST);
});

$router->group('/user', function ($router) {
    /** @var \inhere\sroute\ORouter $router */
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

$router->get('/home', 'inhere\sroute\examples\controllers\HomeController@index');

// can match '/home/test', but not match '/home'
// can also use defined patterns, @see $router->patterns
$router->any('/home/{act}', HomeController::class);

// can match '/home' '/home/test'
//$router->arg('/home[/{act}]', examples\HomeController::class);


