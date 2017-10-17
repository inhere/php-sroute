<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/27
 * Time: 下午11:56
 *
 */

use Inhere\Route\Examples\Controllers\HomeController;

$routes = [
// uri /50be3774f6/arg1/arg2/arg3/arg4/arg5/arg6/arg7/arg8/arg9/850726135a
//'regex' => '#^/803d2fad34/([^/]+)1/([^/]+)2/([^/]+)3/([^/]+)4/([^/]+)5/([^/]+)6/([^/]+)7/([^/]+)8/([^/]+)9/961751ae0c$#',
    [
        'GET',
        '/50be3774f6/{arg1}/{arg2}/{arg3}/{arg4}/{arg5}/{arg6}/{arg7}/{arg8}/{arg9}/850726135a',
        function () {
            echo 'hello, welcome. test';
        }
    ],
    [
        'GET',
        '/',
        function () {
            echo 'hello, welcome';
        }
    ],
    [
        'GET',
        '/home',
        HomeController::class . '@index'
    ],
    [
        'POST',
        '/post',
        function () {
            echo 'hello, welcome. only allow POST';
        }
    ],
    [
        ['post', 'delete'],
        '/pd',
        function () {
            echo 'hello, welcome. only allow POST,DELETE';
        }
    ],
    [
        ['get', 'post'],
        '/user/login',
        function () {
            var_dump($_GET, $_POST);
        }
    ],
    /*
    match: /blog /saying
    */
    [
        'GET',
        '/{name}',
        'default_handler',
        [
            'params' => [
                'name' => 'blog|saying'
            ]
        ]
    ],
    // optional param
    [
        'GET',
        '/test[/optional]',
        function () {
            echo 'hello, welcome. match: /test[/optional]';
        }
    ],
    /*
    match:
        /my/tom/78
        /my/tom
        /my
     */
    [
        'GET',
        '/my[/{name}[/{age}]]',
        function (array $args) {
            $args = array_merge([
                'name' => 'NO',
                'age' => 10
            ], $args);

            echo "hello, my name: {$args['name']}, my age: {$args['age']}";
        },
        [
            'params' => [
                'age' => '\d+'
            ]
        ]
    ],
    /*
    match:
        /hello/tom
        /hello
     */
    [
        'GET',
        '/hello[/{name}]',
        function ($args) {
            $n = isset($args['name']) ? $args['name'] : 'NO';
            echo "hello, {$n}"; // 'john'
        },
        [
            'params' => [
                'name' => '\w+'
            ],
            'default' => [
                'name' => 'default val'
            ]
        ]
    ],
    // can match '/home/test', but not match '/home'
    // can also use defined patterns, @see $router->globalParams
    [
        'ANY',
        '/home/{act}',
        HomeController::class
    ],
// group
    [
        '/user',
        [
            [
                'GET',
                '',
                function () {
                    echo 'hello. you access: /user';
                }
            ],
            [
                'GET',
                '/index',
                function () {
                    echo 'hello. you access: /user/index';
                }
            ],
        ]
    ]

];

return $routes;
