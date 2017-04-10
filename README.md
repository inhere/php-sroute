# php simple router

a very lightweight single file of the router.

> referrer the porject [noahbuscher\macaw](https://github.com/noahbuscher/Macaw) , but add some feature.

- supported request method: `GET` `POST` `PUT` `DELETE` `HEAD` `OPTIONS`
- support event: `found` `notFound`. you can do somthing on the trigger event.
- support custom the route founded handler: `SRoute::setFoundHandler(callable $foundHandler)`. you can custom how to call matched uri callback.
- allow some special config, by `SRoute::config()`. NOTICE: you must call it on before `SRoute::dispatch()`

## install

```
require: {
    "inhere/sroute": "dev-master"
}
```

## allow config

```
// there are default config.

    // stop On Matched. only match one
    'stopOnMatch'  => true,
    
    // Filter the `/favicon.ico` request.
    'filterFavicon' => false,
    
    // ignore last '/' char. If is true, will clear last '/'.
    'ignoreLastSep' => false,

    // enable dynamic action.
    // e.g
    // if set True;
    //  SRoute::any('/demo/(\w+)', app\controllers\Demo::class);
    //  you access '/demo/test' will call 'app\controllers\Demo::test()'
    'dynamicAction' => false,

    // action executor. will auto call controller's executor method to run all action.
    // e.g
    //  `run($action)`
    //  SRoute::any('/demo/(?<act>\w+)', app\controllers\Demo::class);
    //  you access `/demo/test` will call `app\controllers\Demo::run('test')`
    'actionExecutor' => '', // 'run'
```

## usage

first: 

```
use inhere\sroute\SRoute;
```

### now,add routes

```
// use Closure
SRoute::get('/', function() {
    echo 'hello';
});

// acces 'test/john'
SRoute::get('/test/(\w+)', function($arg) {
    echo $arg; // 'john'
});
```

assign action:

```
// if you config 'ignoreLastSep' => true, '/index' is equals to '/index/'
SRoute::get('/index', 'app\controllers\Home@index');

// dynamic action, config 'dynamicAction' => true
// you access '/dynamic/test' will call 'app\controllers\Home::test()'
SRoute::any('/dynamic/(\w+)', app\controllers\Home::class);

// will match '/dynamic' '/dynamic/test' 
SRoute::any('/dynamic(/\w+)?', app\controllers\Home::class);

// use action executor
// if you config 'actionExecutor' => 'run'
// access '/user', will call app\controllers\User::run('')
// access '/user/profile', will call app\controllers\User::run('profile')
SRoute::get('/user', 'app\controllers\User');
SRoute::get('/user/profile', 'app\controllers\User');

// if config 'actionExecutor' => 'run' and 'dynamicAction' => true,
// access '/user', will call app\controllers\User::run('')
// access '/user/profile', will call app\controllers\User::run('profile')
SRoute::get('/user(/\w+)?', 'app\controllers\User');

SRoute::any('/404', function() {
    echo "this page {$_GET['uri']} not found.";
});
```

### setting events(if you need)

```
// on found
SRoute::on(SRoute::FOUND, function ($uri, $cb) use ($app) {
    $app->logger->debug("Matched uri path: $uri, setting callback is: " . (string)$cb);
});

// on notFound, redirect to '/404'
SRoute::on('notFound', '/404');

// can also, on notFound, output a message.
SRoute::on('notFound', function ($uri) {
    echo "the page $uri not found!";
});
```

### setting config(if you need)

```
// set config
SRoute::config([
    'stopOnMatch' => true,
    'ignoreLastSep' => true,
    'dynamicAction' => true,
]);
```

### begin dispatch

```
SRoute::dispatch();
```

## License 

MIT
