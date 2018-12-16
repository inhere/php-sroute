# php simple router

[![License](https://img.shields.io/packagist/l/inhere/sroute.svg?style=flat-square)](LICENSE.md)
[![PHP Version](https://img.shields.io/packagist/php-v/inhere/sroute.svg?colorB=green)](https://packagist.org/packages/inhere/sroute)
[![Latest Stable Version](http://img.shields.io/packagist/v/inhere/sroute.svg)](https://packagist.org/packages/inhere/sroute)
[![Build Status](https://travis-ci.org/inhere/php-srouter.svg?branch=master)](https://travis-ci.org/inhere/php-srouter)
[![Coverage Status](https://coveralls.io/repos/github/inhere/php-srouter/badge.svg?branch=master)](https://coveralls.io/github/inhere/php-srouter?branch=master)

A very lightweight and fast speed router.

- Lightweight and fast speed, the search speed is not affected by the routing number
- supported request methods: `GET` `POST` `PUT` `DELETE` `HEAD` `OPTIONS`
- support event: `found` `notFound`. Some things you can do when the triggering event (such as logging, etc.)
- support manual dispatch a URI route by `$router->dispatch($path, $method)`, you can dispatch a URI in your logic.
- Support automatic matching routing like yii framework, by config `autoRoute`. 
- more interesting config, please see `$router->config`
- You can also do not have to configure anything, it can also work very well

## [中文README](./README.md)更详细

## Project

- **github** https://github.com/inhere/php-srouter.git
- **git@osc** https://git.oschina.net/inhere/php-srouter.git

## Install

- by `composer.json`

```json
{
    "require": {
        "inhere/sroute": "dev-master"
    }
}
```

- by `composer require`

```bash
composer require inhere/sroute
```

## Benchmark

> Test time: `2018.11.19`

- test codes: https://github.com/ulue/php-router-benchmark

## Worst-case matching

This benchmark matches the last route and unknown route. It generates a randomly prefixed and suffixed route in an attempt to thwart any optimization. 1,000 routes each with 9 arguments.

This benchmark consists of 14 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.

Test Name | Results | Time(ms) | + Interval | Change
------------------ | ------- | ------- | ---------- | -----------
**inhere/sroute(Router)** - unknown route(1000 routes)  | 990 | 0.002031 | +0.000871 | 75% slower
inhere/sroute(SRouter) - unknown route(1000 routes)     | 994 | 0.002895 | +0.001736 | 150% slower
**inhere/sroute(Router)** - last route(1000 routes)     | 997 | 0.005300 | +0.004141 | 357% slower
inhere/sroute(SRouter) - last route(1000 routes)        | 997 | 0.006467 | +0.005308 | 458% slower
symfony/routing(cached) - unknown route(1000 routes)    | 976 | 0.012777 | +0.011618 | 1002% slower
symfony/routing(cached) - last route(1000 routes)       | 996 | 0.013608 | +0.012449 | 1074% slower
mindplay/timber - last route(1000 routes)               | 998 | 0.017211 | +0.016052 | 1385% slower
FastRoute - unknown route(1000 routes)                  | 991 | 0.039429 | +0.038270 | 3302% slower
FastRoute(cached) - unknown route(1000 routes)          | 990 | 0.040800 | +0.039641 | 3420% slower
FastRoute(cached) - last route(1000 routes)             | 999 | 0.045065 | +0.043906 | 3788% slower
FastRoute - last route(1000 routes)                     | 999 | 0.064694 | +0.063535 | 5481% slower
Pux PHP - unknown route(1000 routes)                    | 978 | 0.316016 | +0.314857 | 27163% slower
symfony/routing - unknown route(1000 routes)            | 992 | 0.359482 | +0.358323 | 30912% slower
symfony/routing - last route(1000 routes)               | 999 | 0.418813 | +0.417654 | 36031% slower
Pux PHP - last route(1000 routes)                       | 999 | 0.440489 | +0.439330 | 37901% slower
Macaw - unknown route(1000 routes)                      | 991 | 1.687441 | +1.686282 | 145475% slower
Macaw - last route(1000 routes)                         | 999 | 1.786542 | +1.785383 | 154024% slower

## First route matching

This benchmark tests how quickly each router can match the first route. 1,000 routes each with 9 arguments.

This benchmark consists of 7 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.

Test Name | Results | Time | + Interval | Change
--------- | ------- | ---- | ---------- | ------
nikic/fast-route - first route(1000)                    | 998 | 0.002929 | +0.001571 | 116% slower
corneltek/pux(php) - first route(1000)                  | 996 | 0.002971 | +0.001613 | 119% slower
inhere/sroute(Router) - first(1000)                     | 979 | 0.006202 | +0.004844 | 357% slower
inhere/sroute(SRouter) - first(1000)                    | 999 | 0.006627 | +0.005269 | 388% slower
symfony/routing(cached) - first route(1000)             | 985 | 0.006858 | +0.005501 | 405% slower
symfony/routing - first route(1000)                     | 995 | 0.023105 | +0.021747 | 1601% slower
nikic/fast-route(cached) - first route(1000)            | 999 | 0.041133 | +0.039775 | 2929% slower
Macaw - first route (1000 routes)                       | 999 | 1.782017 | +1.780659 | 131128% slower

## Usage

first, import the class

```php
use Inhere\Route\Router;

$router = new Router();
```

## add some routes

```php
// match GET. handler use Closure
$router->get('/', function() {
    echo 'hello';
});

// access 'test/john'
$router->get('/test/{name}', function($params) {
    echo $params['name']; // 'john'
}, ['name' => '\w+']); 

// match POST
$router->post('/user/login', function() {
    var_dump($_POST);
});

// match GET or POST
$router->map(['get', 'post'], '/user/login', function() {
    var_dump($_GET, $_POST);
});

// match any method
$router->any('/home', function() {
    echo 'hello, you request page is /home';
});

// route group
$router->group('/user', function () {
    $router->get('/', function () {
        echo 'hello. you access: /user/';
    });
    $router->get('/index', function () {
        echo 'hello. you access: /user/index';
    });
});
```

### Use controller action

```php
// if you config 'ignoreLastSlash' => true, '/index' is equals to '/index/'
$router->get('/index', 'app\controllers\Home@index');
```

### Dynamic action

match dynamic action, config `'dynamicAction' => true`

> NOTICE: use dynamic action, should be use `any()`.

```php
// access '/home/test' will call 'app\controllers\Home::test()'
$router->any('/home/{name}', app\controllers\Home::class);

// can match '/home', '/home/test'
$router->any('/home[/{name}]', app\controllers\Home::class);
```

### Use action executor

if you config `'actionExecutor' => 'run'`

```php
// access '/user', will call app\controllers\User::run('')
// access '/user/profile', will call app\controllers\User::run('profile')
$router->get('/user', 'app\controllers\User');
$router->get('/user/profile', 'app\controllers\User');

// if config 'actionExecutor' => 'run' and 'dynamicAction' => true,
// access '/user', will call app\controllers\User::run('')
// access '/user/profile', will call app\controllers\User::run('profile')
$router->get('/user[/{name}]', 'app\controllers\User');
```


### Automatic matching is routed to the controller

Support automatic matching like yii routed to the controller, need config `autoRoute`. 

```php 
    'autoRoute' => 1, // enanbled
    'controllerNamespace' => 'Example\\controllers', // The controller class in the namespace
    'controllerSuffix' => 'Controller', // The controller class suffix
```

### setting config

```php
// set config
$router->config([
    'ignoreLastSlash' => true,
    
    // enable autoRoute, work like yii framework
    // you can access '/demo' '/admin/user/info', Don't need to configure any route
    'autoRoute' => 1,
    'controllerNamespace' => 'Example\\controllers',
    'controllerSuffix' => 'Controller',
]);
```

- default config

```php
// there are default config.
[
    // ignore last '/' char. If is True, will clear last '/', so '/home' equals to '/home/'
    'ignoreLastSlash' => false,

    // auto route match @like yii framework
    // If is True, will auto find the handler controller file.
    'autoRoute' => false,
    // The default controllers namespace, is valid when `'enable' = true`
    'controllerNamespace' => '', // eg: 'app\\controllers'
    // controller suffix, is valid when `'enable' = true`
    'controllerSuffix' => '',    // eg: 'Controller'
]
```

> NOTICE: you must call `$router->config()` on before the add route.

## Route dispatcher

```php
use Inhere\Route\Dispatcher;

$dispatcher = new Dispatcher([
    'dynamicAction' => true,
]);
```

### Events 

```php
$dispatcher->on(Dispatcher::ON_FOUND, function ($uri, $route) use ($app) {
    $app->logger->debug("Matched uri path: $uri");
});

// on notFound, redirect to '/404'
$dispatcher->on('notFound', '/404');
// can also, on notFound, output a message.
$dispatcher->on('notFound', function ($uri) {
    echo "the page $uri not found!";
});
```

### begin dispatch

```php
$router->dispatch($dispatcher);
```

## example

please the `example` folder's codes.

you can run a test server by `php -S 127.0.0.1:5670 -t example/static`, now please access http://127.0.0.1:5670

## License 

MIT
