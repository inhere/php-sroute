# php simple router

a very lightweight single file of the router.

- supported request methods: `GET` `POST` `PUT` `DELETE` `HEAD` `OPTIONS`
- support event: `found` `notFound`. Some things you can do when the triggering event (such as logging, etc.)
- support manual dispatch a URI route by `SRoute::dispatchTo()`, you can dispatch a URI in your logic.
- support custom the matched route parser: `SRoute::setMatchedRouteParser()`. you can custom how to call the matched route handler.
- Support automatic matching routing like yii framework, by config `autoRoute`. 
- more interesting config, please see `SRoute::$_config`
- You can also do not have to configure anything, it can also work very well


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

[benchmark](./benchmark/result.md)

## usage

first, import the class

```php
use inhere\sroute\SRoute;
```

## add some routes

```php
// match GET. handler use Closure
SRoute::get('/', function() {
    echo 'hello';
});

// access 'test/john'
SRoute::get('/test/(\w+)', function($arg) {
    echo $arg; // 'john'
});

// match POST
SRoute::post('/user/login', function() {
    var_dump($_POST);
});

// match GET or POST
SRoute::map(['get', 'post'], '/user/login', function() {
    var_dump($_GET, $_POST);
});

// match any method
SRoute::any('/home', function() {
    echo 'hello, you request page is /home';
});
```

### use controller action

```php
// if you config 'ignoreLastSep' => true, '/index' is equals to '/index/'
SRoute::get('/index', 'app\controllers\Home@index');
```

### dynamic action

match dynamic action, config `'dynamicAction' => true`

> NOTICE: use dynamic action, should be use `any()`.

```php
// access '/home/test' will call 'app\controllers\Home::test()'
SRoute::any('/home/(\w+)', app\controllers\Home::class);

// can match '/home', '/home/test'
SRoute::any('/home(/\w+)?', app\controllers\Home::class);
```

### use action executor

if you config `'actionExecutor' => 'run'`

```php
// access '/user', will call app\controllers\User::run('')
// access '/user/profile', will call app\controllers\User::run('profile')
SRoute::get('/user', 'app\controllers\User');
SRoute::get('/user/profile', 'app\controllers\User');

// if config 'actionExecutor' => 'run' and 'dynamicAction' => true,
// access '/user', will call app\controllers\User::run('')
// access '/user/profile', will call app\controllers\User::run('profile')
SRoute::get('/user(/\w+)?', 'app\controllers\User');
```


## Automatic matching is routed to the controller

Support automatic matching like yii routed to the controller, need config `autoRoute`. 

```php 
    'autoRoute' => [
        'enable' => 1, // 启用
        'controllerNamespace' => 'examples\\controllers', // The controller class in the namespace
        'controllerSuffix' => 'Controller', // The controller class suffix
    ],
```

## Match all requests

you can config 'matchAll', All requests for intercepting。 (eg. web site maintenance)

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

## setting events(if you need)

```php
SRoute::any('/404', function() {
    echo "Sorry,This page {$_GET['path']} not found.";
});
```

```php
// on found
SRoute::on(SRoute::FOUND, function ($uri, $cb) use ($app) {
    $app->logger->debug("Matched uri path: $uri, setting callback is: " . is_string($cb) ? $cb : get_class($cb));
});

// on notFound, redirect to '/404'
SRoute::on('notFound', '/404');

// can also, on notFound, output a message.
SRoute::on('notFound', function ($uri) {
    echo "the page $uri not found!";
});
```

## setting config(if you need)

```php
// set config
SRoute::config([
    'stopOnMatch' => true,
    'ignoreLastSep' => true,
    'dynamicAction' => true,
    
//    'matchAll' => '/', // a route path
//    'matchAll' => function () {
//        echo 'System Maintaining ... ...';
//    },
    
    // enable autoRoute, work like yii framework
    // you can access '/demo' '/admin/user/info', Don't need to configure any route
    'autoRoute' => [
        'enable' => 1,
        'controllerNamespace' => 'examples\\controllers',
        'controllerSuffix' => 'Controller',
    ],
]);
```

- default config

```php
// there are default config.
[
    // stop on matched. only match one
    'stopOnMatch' => true,
    // Filter the `/favicon.ico` request.
    'filterFavicon' => false,
    // ignore last '/' char. If is True, will clear last '/', so '/home' equals to '/home/'
    'ignoreLastSep' => false,

    // match all request.
    // 1. If is a valid URI path, will match all request uri to the path.
    // 2. If is a closure, will match all request then call it
    'matchAll' => '', // eg: '/site/maintenance' or `function () { echo 'System Maintaining ... ...'; }`

    // auto route match @like yii framework
    'autoRoute' => [
        // If is True, will auto find the handler controller file.
        'enable' => false,
        // The default controllers namespace, is valid when `'enable' = true`
        'controllerNamespace' => '', // eg: 'app\\controllers'
        // controller suffix, is valid when `'enable' = true`
        'controllerSuffix' => '',    // eg: 'Controller'
    ],

    // default action method name
    'defaultAction' => 'index',

    // enable dynamic action.
    // e.g
    // if set True;
    //  SRoute::any('/demo/(\w+)', app\controllers\Demo::class);
    //  you access '/demo/test' will call 'app\controllers\Demo::test()'
    'dynamicAction' => false,

    // action executor. will auto call controller's executor method to run all action.
    // e.g
    //  `run($action)`
    //  SRoute::any('/demo/(:act)', app\controllers\Demo::class);
    //  you access `/demo/test` will call `app\controllers\Demo::run('test')`
    'actionExecutor' => '', // 'run'
]
```

> NOTICE: you must call `SRoute::config()` on before the `SRoute::dispatch()`

## begin dispatch

```php
SRoute::dispatch();
```

## examples

please the `examples` folder's codes.

you can run a test server by `php -S 127.0.0.1:5670 -t examples`, now please access http://127.0.0.1:5670

## License 

MIT
