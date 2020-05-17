<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/27
 * Time: 下午11:56
 *
 */

use Inhere\RouteTest\Controllers\HomeController;
use Inhere\Route\RouterInterface;

function handler0()
{
    echo 'hello, welcome';
}

function main_handler()
{
    echo 'hello, welcome. METHOD: ' . $_SERVER['REQUEST_METHOD'] . '. you request URI: ' . $_SERVER['REQUEST_URI'];
}

function post_handler()
{
    echo 'hello, welcome. only allow POST';
}

function multi_method_handler()
{
    echo 'hello, welcome. only allow POST,DELETE';
}

function default_handler()
{
    echo 'hello, welcome. you request URI: ' . $_SERVER['REQUEST_URI'];
}

function my_handler(array $args)
{
    echo "hello, my name: {$args['name']}, my age: {$args['age']}";
}

$routes = [
// uri /50be3774f6/arg1/arg2/arg3/arg4/arg5/arg6/arg7/arg8/arg9/850726135a
//'regex' => '#^/803d2fad34/([^/]+)1/([^/]+)2/([^/]+)3/([^/]+)4/([^/]+)5/([^/]+)6/([^/]+)7/([^/]+)8/([^/]+)9/961751ae0c$#',
    [
        'GET',
        '/50be3774f6/{arg1}/{arg2}/{arg3}/{arg4}/{arg5}/{arg6}/{arg7}/{arg8}/{arg9}/850726135a',
        'handler0'
    ],
    [
        'GET',
        '/',
        'handler0'
    ],
    [
        'GET',
        '/home',
        HomeController::class . '@index'
    ],
    [
        'POST',
        '/post',
        'post_handler'
    ],
    [
        'PUT',
        '/put',
        'main_handler'
    ],
    [
        'DELETE',
        '/del',
        'main_handler'
    ],
    [
        'GET',
        '/user/{id}/followers',
        'main_handler'
    ],
    [
        'GET',
        '/user/detail/{id}',
        'main_handler'
    ],
    [
        'PUT',
        '/user/detail/{id}',
        'main_handler'
    ],
    [
        'GET',
        '/user/{id}',
        'main_handler'
    ],
    [
        'POST',
        '/user/{id}',
        'main_handler'
    ],
    [
        'PUT',
        '/user/{id}',
        'main_handler'
    ],
    [
        'DELETE',
        '/user/{id}',
        'main_handler'
    ],
    [
        'DELETE',
        '/del/{uid}',
        'main_handler'
    ],
    [
        ['post', 'delete'],
        '/pd',
        'multi_method_handler'
    ],
    [
        ['get', 'post'],
        '/user/login',
        'default_handler'
    ],
    [
        ['get', 'post'],
        '/admin/manage/getInfo[/id/{int}]',
        'default_handler'
    ],
    /*
    match: /blog /saying
    */
    [
        'GET',
        '/{name}',
        'default_handler',
        [
            'name' => 'blog|saying'
        ]
    ],
    // optional param
    [
        'GET',
        '/about[.html]',
        HomeController::class . '@about'
    ],
    [
        'GET',
        '/test[/optional]',
        'default_handler'
    ],
    [
        'GET',
        '/blog-{post}',
        'default_handler'
    ],
    [
        'GET',
        '/blog[/index]',
        'default_handler'
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
        'my_handler',
        [
            'age' => '\d+'
        ],
        [
            'defaults' => [
                'name' => 'God',
                'age' => 25,
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
            $n = $args['name'] ?? 'NO';
            echo "hello, {$n}"; // 'john'
        },
        [
            'name' => '\w+'
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
            [
                'GET',
                '/{some}',
                'default_handler'
            ],
        ]
    ]

];

// has router instance
if (isset($hasRouter) && $hasRouter) {
    foreach ($routes as $route) {
        // group
        if (is_array($route[1])) {
            $rs = $route[1];
            $router->group($route[0], function (RouterInterface $router) use ($rs) {
                foreach ($rs as $r) {
                    $router->map($r[0], $r[1], $r[2], $r[3] ?? [], $r[4] ?? []);
                }
            });

            continue;
        }

        $router->map($route[0], $route[1], $route[2], $route[3] ?? [], $route[4] ?? []);
    }
} else {
    return $routes;
}
