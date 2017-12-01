# php simple router

[![License](https://img.shields.io/packagist/l/inhere/console.svg?style=flat-square)](LICENSE.md)
[![Php Version](https://img.shields.io/badge/php-%3E=5.6-brightgreen.svg?maxAge=2592000)](https://packagist.org/packages/inhere/sroute)
[![Latest Stable Version](http://img.shields.io/packagist/v/inhere/sroute.svg)](https://packagist.org/packages/inhere/sroute)

a very lightweight and fast speed router.

- Lightweight and fast speed, the search speed is not affected by the routing number
- supported request methods: `GET` `POST` `PUT` `DELETE` `HEAD` `OPTIONS`
- support event: `found` `notFound`. Some things you can do when the triggering event (such as logging, etc.)
- support manual dispatch a URI route by `SRouter::dispatch($path, $method)`, you can dispatch a URI in your logic.
- Support automatic matching routing like yii framework, by config `autoRoute`. 
- more interesting config, please see `SRouter::setConfig`
- You can also do not have to configure anything, it can also work very well

## [中文README](./README_zh.md)更详细

## project

- **github** https://github.com/inhere/php-srouter.git
- **git@osc** https://git.oschina.net/inhere/php-srouter.git

## install

- by composer

```json
{
    "require": {
        "inhere/sroute": "dev-master"
    }
}
```

- fetch code

```bash
git clone https://github.com/inhere/php-srouter.git // github
git clone https://git.oschina.net/inhere/php-srouter.git // git@osc
```

## benchmark

## Worst-case matching

This benchmark matches the last route and unknown route. It generates a randomly prefixed and suffixed route in an attempt to thwart any optimization. 1,000 routes each with 9 arguments.

This benchmark consists of 14 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.

Test Name | Results | Time | + Interval | Change
--------- | ------- | ---- | ---------- | ------
ORouter - unknown route (1000 routes) | 988 | 0.0000120063 | +0.0000000000 | baseline
ORouter - last route (1000 routes) | 988 | 0.0000122867 | +0.0000002804 | 2% slower
SRouter - unknown route (1000 routes) | 983 | 0.0000123633 | +0.0000003570 | 3% slower
SRouter - last route (1000 routes) | 998 | 0.0000142205 | +0.0000022142 | 18% slower
Symfony2 Dumped - last route (1000 routes) | 990 | 0.0000468579 | +0.0000348516 | 290% slower
Symfony2 Dumped - unknown route (1000 routes) | 995 | 0.0000490268 | +0.0000370205 | 308% slower
FastRoute - unknown route (1000 routes) | 968 | 0.0001358227 | +0.0001238164 | 1031% slower
FastRoute(cached) - last route (1000 routes) | 999 | 0.0001397746 | +0.0001277683 | 1064% slower
FastRoute(cached) - unknown route (1000 routes) | 960 | 0.0001424064 | +0.0001304001 | 1086% slower
FastRoute - last route (1000 routes) | 999 | 0.0001659009 | +0.0001538946 | 1282% slower
Pux PHP - unknown route (1000 routes) | 964 | 0.0013507533 | +0.0013387470 | 11150% slower
Pux PHP - last route (1000 routes) | 999 | 0.0014749475 | +0.0014629412 | 12185% slower
Symfony2 - unknown route (1000 routes) | 979 | 0.0038350259 | +0.0038230196 | 31842% slower
Symfony2 - last route (1000 routes) | 999 | 0.0040060059 | +0.0039939995 | 33266% slower


## First route matching

This benchmark tests how quickly each router can match the first route. 1,000 routes each with 9 arguments.

This benchmark consists of 7 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.


Test Name | Results | Time | + Interval | Change
--------- | ------- | ---- | ---------- | ------
Pux PHP - first route(1000) | 993 | 0.0000105502 | +0.0000000000 | baseline
ORouter - first route(1000) | 984 | 0.0000118334 | +0.0000012832 | 12% slower
SRouter - first route(1000) | 982 | 0.0000118473 | +0.0000012971 | 12% slower
FastRoute(cached) - first route(1000) | 999 | 0.0000143361 | +0.0000037859 | 36% slower
FastRoute - first route(1000) | 999 | 0.0000143980 | +0.0000038477 | 36% slower
Symfony2 Dumped - first route | 993 | 0.0000350874 | +0.0000245372 | 233% slower
Symfony2 - first route | 999 | 0.0000630564 | +0.0000525061 | 498% slower

## usage

first, import the class

```php
use Inhere\Route\SRouter;
```

## add some routes

```php
// match GET. handler use Closure
SRouter::get('/', function() {
    echo 'hello';
});

// access 'test/john'
SRouter::get('/test/{name}', function($params) {
    echo $params['name']; // 'john'
});

// match POST
SRouter::post('/user/login', function() {
    var_dump($_POST);
});

// match GET or POST
SRouter::map(['get', 'post'], '/user/login', function() {
    var_dump($_GET, $_POST);
});

// match any method
SRouter::any('/home', function() {
    echo 'hello, you request page is /home';
});

// route group
SRouter::group('/user', function () {
    SRouter::get('/', function () {
        echo 'hello. you access: /user/';
    });
    SRouter::get('/index', function () {
        echo 'hello. you access: /user/index';
    });
});
```

### use controller action

```php
// if you config 'ignoreLastSep' => true, '/index' is equals to '/index/'
SRouter::get('/index', 'app\controllers\Home@index');
```

### dynamic action

match dynamic action, config `'dynamicAction' => true`

> NOTICE: use dynamic action, should be use `any()`.

```php
// access '/home/test' will call 'app\controllers\Home::test()'
SRouter::any('/home/{name}', app\controllers\Home::class);

// can match '/home', '/home/test'
SRouter::any('/home[/{name}]', app\controllers\Home::class);
```

### use action executor

if you config `'actionExecutor' => 'run'`

```php
// access '/user', will call app\controllers\User::run('')
// access '/user/profile', will call app\controllers\User::run('profile')
SRouter::get('/user', 'app\controllers\User');
SRouter::get('/user/profile', 'app\controllers\User');

// if config 'actionExecutor' => 'run' and 'dynamicAction' => true,
// access '/user', will call app\controllers\User::run('')
// access '/user/profile', will call app\controllers\User::run('profile')
SRouter::get('/user[/{name}]', 'app\controllers\User');
```


### Automatic matching is routed to the controller

Support automatic matching like yii routed to the controller, need config `autoRoute`. 

```php 
    'autoRoute' => 1, // enanbled
    'controllerNamespace' => 'examples\\controllers', // The controller class in the namespace
    'controllerSuffix' => 'Controller', // The controller class suffix
```

### match all requests

you can config 'matchAll', All requests for matchAlling。 (eg. web site maintenance)

you can config 'matchAll' as

- route path

```php
    'matchAll' => '/about', // a route path
```

Will be executed directly the route.

- callback

```php 
    'matchAll' => function () {
        echo 'System Maintaining ... ...';
    },
```

Will directly execute the callback

### setting config

```php
// set config
SRouter::setConfig([
    'ignoreLastSep' => true,
    
//    'matchAll' => '/', // a route path
//    'matchAll' => function () {
//        echo 'System Maintaining ... ...';
//    },
    
    // enable autoRoute, work like yii framework
    // you can access '/demo' '/admin/user/info', Don't need to configure any route
    'autoRoute' => 1,
    'controllerNamespace' => 'examples\\controllers',
    'controllerSuffix' => 'Controller',
]);
```

- default config

```php
// there are default config.
[
    // ignore last '/' char. If is True, will clear last '/', so '/home' equals to '/home/'
    'ignoreLastSep' => false,

    // matchAll all request.
    // 1. If is a valid URI path, will matchAll all request uri to the path.
    // 2. If is a closure, will matchAll all request then call it
    'matchAll' => '', // eg: '/site/maintenance' or `function () { echo 'System Maintaining ... ...'; }`

    // auto route match @like yii framework
    // If is True, will auto find the handler controller file.
    'autoRoute' => false,
    // The default controllers namespace, is valid when `'enable' = true`
    'controllerNamespace' => '', // eg: 'app\\controllers'
    // controller suffix, is valid when `'enable' = true`
    'controllerSuffix' => '',    // eg: 'Controller'
]
```

> NOTICE: you must call `SRouter::setConfig()` on before the add route.

## route dispatcher

```php
use Inhere\Route\Dispatcher;

$dispatcher = new Dispatcher([
    'dynamicAction' => true,
]);
```

## events 

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

## begin dispatch

```php
SRouter::dispatch($dispatcher);
```

## examples

please the `examples` folder's codes.

you can run a test server by `php -S 127.0.0.1:5670 -t examples/static`, now please access http://127.0.0.1:5670

## License 

MIT
