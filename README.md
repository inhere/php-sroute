# php simple router

非常轻量级的路由器。无依赖、简洁、速度快、自定义性强

- 轻量级且速度快，查找速度不受路由数量的影响
- 支持路由参数定义，以及路由选项(比如设定 domains、schema等检查限制)
- 支持请求方法: `GET` `POST` `PUT` `DELETE` `HEAD` `OPTIONS` ...
- 支持事件: `found` `notFound` `execStart` `execEnd` `execError`. 当触发事件时你可以做一些事情(比如记录日志等)
- 支持动态获取action名。支持设置方法执行器(`actionExecutor`)，通过方法执行器来自定义调用真实请求方法. 
- 支持自动匹配路由到控制器就像 yii 一样, 请参看配置项 `autoRoute`. 
- 支持通过方法 `SRouter::dispatch($path, $method)` 手动调度一个路由
- 你也可以不配置任何东西, 它也能很好的工作

**[EN README](./README.md)**

## 项目地址

- **github** https://github.com/inhere/php-srouter.git
- **git@osc** https://git.oschina.net/inhere/php-srouter.git

## 安装

- composer

```json
{
    "require": {
        "inhere/sroute": "dev-master"
    }
}
```

- 直接拉取

```bash
git clone https://github.com/inhere/php-srouter.git // github
git clone https://git.oschina.net/inhere/php-srouter.git // git@osc
```

## 压测

自动生成了1000条路由，每条有9个参数位，分别测试1000次的 

- 第一条路由匹配
- 最后一条路由匹配
- 不会匹配到的路由

压测结果

## Worst-case matching

This benchmark matches the last route and unknown route. It generates a randomly prefixed and suffixed route in an attempt to thwart any optimization. 1,000 routes each with 9 arguments.

This benchmark consists of 14 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.

Test Name | Results | Time | + Interval | Change
--------- | ------- | ---- | ---------- | ------
**ORouter** - unknown route (1000 routes) | 988 | 0.0000120063 | +0.0000000000 | baseline
**ORouter** - last route (1000 routes) | 988 | 0.0000122867 | +0.0000002804 | 2% slower
**SRouter** - unknown route (1000 routes) | 983 | 0.0000123633 | +0.0000003570 | 3% slower
**SRouter** - last route (1000 routes) | 998 | 0.0000142205 | +0.0000022142 | 18% slower
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
**ORouter** - first route(1000) | 984 | 0.0000118334 | +0.0000012832 | 12% slower
**SRouter** - first route(1000) | 982 | 0.0000118473 | +0.0000012971 | 12% slower
FastRoute(cached) - first route(1000) | 999 | 0.0000143361 | +0.0000037859 | 36% slower
FastRoute - first route(1000) | 999 | 0.0000143980 | +0.0000038477 | 36% slower
Symfony2 Dumped - first route | 993 | 0.0000350874 | +0.0000245372 | 233% slower
Symfony2 - first route | 999 | 0.0000630564 | +0.0000525061 | 498% slower

## 使用

- `Inhere\Route\SRouter` 是静态类版本
- `Inhere\Route\ORouter` 是对象版本

两个类的方法名和参数都是一样的

首先, 导入类

```php
use Inhere\Route\SRouter;
```

## 添加路由

```php
// 匹配 GET 请求. 处理器是个闭包 Closure
SRouter::get('/', function() {
    echo 'hello';
});

// 匹配参数 'test/john'
SRouter::get('/test/{name}', function($params) {
    echo $params['name']; // 'john'
}, [
    'params' => [
        'name' => '\w+', // 添加参数匹配限制。若不添加对应的限制，将会自动设置为匹配除了'/'外的任何字符
    ]
]);

// 可选参数支持。匹配  'hello' 'hello/john'
SRouter::get('/hello[/{name}]', function() {
    echo $params['name'] ?? 'No input'; // 'john'
}, [
    'params' => [
        'name' => '\w+', // 添加参数匹配限制
    ]
]);

// 匹配 POST 请求
SRouter::post('/user/login', function() {
    var_dump($_POST);
});

// 匹配 GET 或者 POST
SRouter::map(['get', 'post'], '/user/login', function() {
    var_dump($_GET, $_POST);
});

// 允许任何请求方法
SRouter::any('/home', function() {
    echo 'hello, you request page is /home';
});
SRouter::any('/404', function() {
    echo "Sorry,This page not found.";
});

// 路由组
SRouter::group('/user', function () {
    SRouter::get('/', function () {
        echo 'hello. you access: /user/';
    });
    SRouter::get('/index', function () {
        echo 'hello. you access: /user/index';
    });
});

// 使用 控制器
SRouter::get('/', app\controllers\HomeController::class);
SRouter::get('/index', 'app\controllers\HomeController@index');
```

> 如果配置了 `'ignoreLastSep' => true`, '/index' 等同于 '/index/'

### 添加路由方法

```php
public function map(string|array $methods, string $route, mixed $handler, array $opts = [])
```

添加路由方法

> 其他的添加路由方法底层都是调用的 `map()` 方法，除了没有第一个参数外，其他参数都是一样的

- `$methods` string|array 请求的METHOD. e.g `GET` `['GET', 'POST]`
- `$route` string 定义的路由字符串 e.g `/user/login` `/article/{id}`
- `$handler` string|object 对应路由的处理者
- `$opts` array 选项设置，可以添加自定义的数据。匹配成功会将选项数据返回(e.g middleware, domains),自己再做进一步验证等。下面是已使用的选项
    - `params` 添加路由时设置的参数匹配信息, 若有的话 e.g `'name' => '\w+'`
    - `defaults` 有可选参数时，可以设置默认值

一个较为完整的示例：

> 提示： 若不在选项中设置参数匹配，那么参数将会匹配除了 '/' 之外的任何字符

```php
$router->map(['get', 'post'], '/im/{name}[/{age}]', function(array $params) {
    var_dump($params);
}, [
    // 设置参数匹配
    'params' => [
        'name' => '\w+',
        'age' => '\d+',
    ],
    'defaults' => [
        'age' => 20, // 给可选参数 age 添加一个默认值
    ]
    
    // 可添加更多自定义设置
    'middleware' => ['AuthCheck'],
    'domains' => ['localhost'],
    ... ...
]);
```

Now, 访问 `/im/john/18` 或者 `/im/john` 查看效果

### 自动匹配路由

支持根据请求的URI自动匹配路由(就像 yii 一样), 需配置 `autoRoute`. 

```php 
    'autoRoute' => 1, // 启用
    'controllerNamespace' => 'App\\Controllers', // 控制器类所在命名空间
    'controllerSuffix' => 'Controller', // 控制器类后缀
```

> 请参看示例 `example` 中的使用

此时请求没有配置路由的 `/demo` `/demo/test`。将会自动尝试从 `App\\Controllers` 命名空间下去查找 `DemoController`

查找逻辑是 

- 只有一节的(如`/demo`)，直接定义它为控制器类名进行查找
- 大于等于两节的默认先认为最后一节是控制器类名，进行查找
- 若失败，再尝试将倒数第二节认为是控制器名，最后一节是action名

### 匹配所有

配置 `intercept` 可用于拦截所有请求。 （例如网站维护时）

可允许配置 `intercept` 的值为 

- 路由path

```php
    'intercept' => '/about', // a route path
```

将会直接执行此路由后停止执行

- 回调

```php 
    'intercept' => function () {
        echo '系统维护中 :)';
    },
```

将会直接执行此回调后停止执行

## 设置路由配置

```php
// set config
SRouter::setConfig([
    'ignoreLastSep' => true,    
    'autoRoute' => 1,
    'controllerNamespace' => 'app\\controllers',
    'controllerSuffix' => 'Controller',
]);
```

> NOTICE: 必须在添加路由之前调用 `SRouter::setConfig()` 

## 路由匹配

```php 
array public function match($path, $method)
```

- `$path` string 请求的URI path
- `$method` string 请求的request method
- 返回 `array` 返回匹配结果信息

### 示例

根据请求的 URI path 和 请求 METHOD 查找匹配我们定义的路由信息。

```php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$route = SRouter::match($path, $method);
```

将会返回如下格式的信息. 可以根据此信息进行 判断匹配是否成功 -> 路由调度

```php
[
    // 路由匹配结果状态. 
    // 可能为： RouterInterface::FOUND, RouterInterface::NOT_FOUND, RouterInterface::METHOD_NOT_ALLOWED
    INT, 
    
    // 格式化后的 $path 的返回(会去除多余的空白,'/'等字符)
    'URI PATH', 
    
    // 路由信息。 匹配失败时(RouterInterface::NOT_FOUND)为 null 
    [
        // (可能存在)配置的请求 METHOD。 自动匹配时无此key
        'method' => 'GET', 
        
        // 此路由的 handler callback
        'handler' => 'handler', 
        
        // (可能存在) 有参数匹配的路由匹配成功后，会将参数值放入这里
        'matches' => ['name' => value ],
        
        // 此路由的自定义选项信息. 可能为空
        // - params - 来自添加路由时设置的参数匹配信息, 若有的话
        // - defaults - 有可选参数时，可以设置默认值
        // 还可以自定义追加此路由的选项：如下经供参考
        // - domains 允许访问路由的域名
        // - schemas 允许访问路由的schema
        // - enter 进入路由的事件回调
        // ... ...
        'option' => [
            'params' => [],
            'defaults' => [],

            // 'domains' => null,
            // 'schemas' => null, // ['http','https'],
            // route event. custom design ...
            // 'enter' => null,
            // 'leave' => null,
        ], 
        
    ],
]
```

## 路由调度

已内置了一个路由调度器 `Inhere\Route\Dispatcher`

```php
use Inhere\Route\Dispatcher;

$dispatcher = new Dispatcher([
    'dynamicAction' => true,
]);
```

### 设置事件处理

```php
// 成功匹配路由
$dispatcher->on(Dispatcher::ON_FOUND, function ($uri, $cb) use ($app) {
    $app->logger->debug("Matched uri path: $uri, setting callback is: " . is_string($cb) ? $cb : get_class($cb));
});

// 当匹配失败, 重定向到 '/404'
$dispatcher->on('notFound', '/404');
// 或者, 当匹配失败, 输出消息...
$dispatcher->on('notFound', function ($uri) {
    echo "the page $uri not found!";
});
```

### 使用控制器方法

通过`@`符号连接控制器类和方法名可以指定执行方法。

```php
SRouter::get('/', app\controllers\HomeController::class);

SRouter::get('/index', 'app\controllers\HomeController@index');
SRouter::get('/about', 'app\controllers\HomeController@about');
```

> NOTICE: 若第二个参数仅仅是个 类，将会尝试执行通过 `defaultAction` 配置的默认方法

### 动态匹配控制器方法

动态匹配控制器方法, 需配置 `'dynamicAction' => true`

> NOTICE: 使用动态匹配控制器方法, 应当使用 `any()` 添加路由. 即此时无法限定请求方法 `REQUEST_METHOD`

```php
// 访问 '/home/test' 将会执行 'app\controllers\HomeController::test()'
SRouter::any('/home/{any}', app\controllers\HomeController::class);

// 可匹配 '/home', '/home/test' 等
SRouter::any('/home[/{name}]', app\controllers\HomeController::class);
```

> NOTICE: 上面两个的区别是 第一个无法匹配 `/home`

### 使用方法执行器

配置 `actionExecutor` 为你需要的方法名，例如配置为 `'actionExecutor' => 'run'`，那所有的方法请求都会提交给此方法。
会将真实的 action 作为参数传入`run($action)`, 需要你在此方法中调度来执行真正的请求方法。

> NOTICE: 在你需要将路由器整合到自己的框架时很有用

示例：

```php
// 访问 '/user', 将会调用 app\controllers\UserController::run('')
SRouter::get('/user', 'app\controllers\UserController');

// 访问 '/user/profile', 将会调用 app\controllers\UserController::run('profile')
SRouter::get('/user/profile', 'app\controllers\UserController');

// 同时配置 'actionExecutor' => 'run' 和 'dynamicAction' => true,
// 访问 '/user', 将会调用 app\controllers\UserController::run('')
// 访问 '/user/profile', 将会调用 app\controllers\UserController::run('profile')
SRouter::any('/user[/{name}]', 'app\controllers\UserController');
```

## 开始路由匹配和调度

```php
SRouter::dispatch($dispatcher);
// $router->dispatch($dispatcher);
```

## 运行示例

示例代码在 `examples` 下。

- 静态版本

你可以通过 `php -S 127.0.0.1:5670 examples/static.php` 来运行一个测试服务器, 现在你可以访问 http://127.0.0.1:5670

- 对象版本

你可以通过 `php -S 127.0.0.1:5670 examples/object.php` 来运行一个测试服务器, 现在你可以访问 http://127.0.0.1:5671

## License 

MIT

## 我的其他项目

### `inhere/console` [github](https://github.com/inhere/php-console) [git@osc](https://git.oschina.net/inhere/php-console)

功能丰富的命令行应用，命令行工具库

### `inhere/redis` [github](https://github.com/inhere/php-redis) [git@osc](https://git.oschina.net/inhere/php-redis)

简单的redis操作客户端包装库

### `inhere/php-validate` [github](https://github.com/inhere/php-validate)  [git@osc](https://git.oschina.net/inhere/php-validate)
 
 一个简洁小巧且功能完善的php验证库。仅有几个文件，无依赖。
 
### `inhere/http` [github](https://github.com/inhere/php-http) [git@osc](https://git.oschina.net/inhere/php-http)

http 工具库(`request` 请求 `response` 响应 `curl` curl请求库，有简洁、完整和并发请求三个版本的类)
