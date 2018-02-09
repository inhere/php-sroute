# php simple router

[![License](https://img.shields.io/packagist/l/inhere/sroute.svg?style=flat-square)](LICENSE)
[![Php Version](https://img.shields.io/badge/php-%3E=7.0-brightgreen.svg?maxAge=2592000)](https://packagist.org/packages/inhere/sroute)
[![Latest Stable Version](http://img.shields.io/packagist/v/inhere/sroute.svg)](https://packagist.org/packages/inhere/sroute)

非常快速且轻量的请求匹配路由器。

- 无依赖、简洁、速度快、功能完善
- 轻量级且速度快，查找速度不受路由数量的影响
- 支持路由组, 支持路由参数定义，以及丰富的自定义路由选项(比如设定 默认值、domains、schemas等检查限制)
- 支持请求方法: `GET` `POST` `PUT` `DELETE` `HEAD` `OPTIONS` ...
- 支持自动匹配路由到控制器就像 Yii 一样, 请参看配置项 `autoRoute` (不推荐)
- 压测对比数据请看[路由测试](#ab-test)

多个版本：

> 不同的版本有稍微的区别以适应不同的场景

- `ORouter` 基础版本，也是后几个版本的基础类。
- `SRouter` 静态类版本。 `ORouter` 的简单包装，通过静态方法使用(方便小应用快速使用)
- `CachedRouter` 继承自`ORouter`，支持路由缓存的版本. 适合fpm使用(有缓存将会省去每次的路由收集和解析消耗) 
- `PreMatchRouter` 继承自`ORouter`，预匹配路由器。当应用的静态路由较多时，将拥有更快的匹配速度
    - fpm 应用中，实际上我们在收集路由之前，已经知道了路由path和请求动作METHOD
- `ServerRouter` 继承自`ORouter`，服务器路由。内置支持动态路由临时缓存. 适合swoole等常驻内存应用使用
    - 最近请求过的动态路由将会缓存为一个静态路由信息，下次相同路由将会直接匹配命中

内置调度器：

- 支持事件: `found` `notFound` `execStart` `execEnd` `execError`. 当触发事件时你可以做一些事情(比如记录日志等)
- 支持动态获取`action`名。支持设置方法执行器(`actionExecutor`)，通过方法执行器来自定义调用真实请求方法. 
- 支持通过方法 `$router->dispatch($path, $method)` 手动调度一个路由
- 你即使不配置任何东西, 它也能很好的工作

**[EN README](README_en.md)**

## 项目地址

- **github** https://github.com/inhere/php-srouter.git
- **gitee** https://gitee.com/inhere/php-srouter.git

## 安装

- composer 命令

```php
composer require inhere/sroute
```

- composer.json

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
git clone https://gitee.com/inhere/php-srouter.git // git@osc
```
<a name="ab-test"></a>
## 压测

自动生成了1000条路由，每条有9个参数位，分别测试1000次的 

- 第一条路由匹配
- 最后一条路由匹配
- 不存在的路由匹配

详细的测试代码请看仓库 https://github.com/ulue/php-router-benchmark

- 压测日期 **2017.12.3**
- An example route: `/9b37eef21e/{arg1}/{arg2}/{arg3}/{arg4}/{arg5}/{arg6}/{arg7}/{arg8}/{arg9}/bda37e9f9b`

压测结果

## Worst-case matching

Test Name | Results | Time(ms) | + Interval | Change
--------- | ------- | ---- | ---------- | ------
inhere/sroute(ORouter) - unknown route (1000 routes) | 987 | 0.010222 | +0.000000 | baseline
inhere/sroute(SRouter) - unknown route (1000 routes) | 984 | 0.012239 | +0.002017 | 20% slower
inhere/sroute(SRouter) - last route (1000 routes) | 999 | 0.024386 | +0.014820 | 155% slower
inhere/sroute(ORouter) - last route (1000 routes) | 975 | 0.024554 | +0.014989 | 157% slower
Symfony Cached - last route (1000 routes) | 997 | 0.029091 | +0.019525 | 204% slower
Symfony Cached - unknown route (1000 routes) | 985 | 0.037226 | +0.027661 | 289% slower
FastRoute - unknown route (1000 routes) | 988 | 0.089904 | +0.080338 | 840% slower
FastRoute(cached) - unknown route (1000 routes) | 988 | 0.091358 | +0.081792 | 855% slower
FastRoute(cached) - last route (1000 routes) | 999 | 0.092567 | +0.083001 | 868% slower
FastRoute - last route (1000 routes) | 999 | 0.113668 | +0.104103 | 1088% slower
phroute/phroute - unknown route (1000 routes) | 987 | 0.168871 | +0.159305 | 1665% slower
phroute/phroute - last route (1000 routes) | 999 | 0.169914 | +0.160348 | 1676% slower
Pux PHP - unknown route (1000 routes) | 981 | 0.866280 | +0.856714 | 8956% slower
Pux PHP - last route (1000 routes) | 999 | 0.941322 | +0.931757 | 9741% slower
AltoRouter - unknown route (1000 routes) | 982 | 2.245384 | +2.235819 | 23373% slower
AltoRouter - last route (1000 routes) | 979 | 2.281995 | +2.272429 | 23756% slower
Symfony - unknown route (1000 routes) | 984 | 2.488247 | +2.478681 | 25912% slower
Symfony - last route (1000 routes) | 999 | 2.540170 | +2.530605 | 26455% slower
Macaw - unknown route (1000 routes) | 982 | 2.617635 | +2.608069 | 27265% slower
Macaw - last route (1000 routes) | 999 | 2.700128 | +2.690562 | 28127% slower


## First route matching

Test Name | Results | Time(ms) | + Interval | Change
--------- | ------- | ---- | ---------- | ------
Pux PHP - first route(1000) | 997 | 0.006587 | +0.000000 | baseline
FastRoute - first route(1000) | 999 | 0.008751 | +0.002165 | 33% slower
phroute/phroute - first route (1000 routes) | 999 | 0.021902 | +0.015315 | 233% slower
Symfony Dumped - first route | 997 | 0.022254 | +0.015667 | 238% slower
ORouter - first route(1000) | 993 | 0.025026 | +0.018440 | 280% slower
SRouter - first route(1000) | 997 | 0.025553 | +0.018967 | 288% slower
noodlehaus/dispatch - first route (1000 routes) | 989 | 0.030126 | +0.023540 | 357% slower
AltoRouter - first route (1000 routes) | 994 | 0.041488 | +0.034902 | 530% slower
Symfony - first route | 991 | 0.047335 | +0.040748 | 619% slower
FastRoute(cached) - first route(1000) | 999 | 0.092703 | +0.086117 | 1307% slower
Macaw - first route (1000 routes) | 999 | 2.710132 | +2.703545 | 41047% slower

## 使用

> 各个版本的方法名和参数都是一样的

首先, 导入类

```php
use Inhere\Route\ORouter;

$router = new ORouter();
```

## 添加路由

```php
// 匹配 GET 请求. 处理器是个闭包 Closure
$router->get('/', function() {
    echo 'hello';
});

// 匹配参数 'test/john'
$router->get('/test/{name}', function($params) {
    echo $params['name']; // 'john'
}, [
    'params' => [
        'name' => '\w+', // 添加参数匹配限制。若不添加对应的限制，将会自动设置为匹配除了'/'外的任何字符
    ]
]);

// 可选参数支持。匹配  'hello' 'hello/john'
$router->get('/hello[/{name}]', function() {
    echo $params['name'] ?? 'No input'; // 'john'
}, [
    'params' => [
        'name' => '\w+', // 添加参数匹配限制
    ]
]);

// 匹配 POST 请求
$router->post('/user/login', function() {
    var_dump($_POST);
});

// 匹配 GET 或者 POST
$router->map(['get', 'post'], '/user/login', function() {
    var_dump($_GET, $_POST);
});

// 允许任何请求方法
$router->any('/home', function() {
    echo 'hello, you request page is /home';
});
$router->any('/404', function() {
    echo "Sorry,This page not found.";
});

// 路由组
$router->group('/user', function () {
    $router->get('/', function () {
        echo 'hello. you access: /user/';
    });
    $router->get('/index', function () {
        echo 'hello. you access: /user/index';
    });
});

// 使用 控制器
$router->get('/', App\Controllers\HomeController::class);
$router->get('/index', 'App\Controllers\HomeController@index');

// 使用 rest() 可以快速将一个控制器类注册成一组 RESTful 路由
$router->rest('/users', App\Controllers\UserController::class);

// 可以注册一个备用路由处理。 当没匹配到时，就会使用它
$router->any('*', 'fallback_handler');
```

> 如果配置了 `'ignoreLastSlash' => true`, '/index' 等同于 '/index/'

#### 注意

可选参数 - 只能是在路由path的最后

正确的：

```php
/hello[/{name}]      // match: /hello/tom   /hello
/my[/{name}[/{age}]] // match: /my/tom/78  /my/tom
```

错误的：

```php
/my[/{name}]/{age}
```

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

配置 `matchAll` 可用于拦截所有请求。 （例如网站维护时）

可允许配置 `matchAll` 的值为 

- 路由path

```php
    'matchAll' => '/about', // a route path
```

将会直接执行此路由后停止执行

- 回调

```php 
    'matchAll' => function () {
        echo '系统维护中 :)';
    },
```

将会直接执行此回调后停止执行

## 设置路由配置

```php
// set config
$router->setConfig([
    'ignoreLastSlash' => true,    
    'autoRoute' => 1,
    'controllerNamespace' => 'app\\controllers',
    'controllerSuffix' => 'Controller',
]);
```

> NOTICE: 必须在添加路由之前调用 `$router->setConfig()` 

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

$route = $router->match($path, $method);
```

<a name="#matched-route-info"></a>
将会返回如下格式的信息. 可以根据此信息进行 判断匹配是否成功 -> 路由调度

> 始终是三个元素的数组。第一二个元素是固定的, 第三个根据状态有所变化

- 第一个 匹配结果状态. 只有三个 `FOUND`, `NOT_FOUND`, `METHOD_NOT_ALLOWED`
- 第二个 格式化后的 $path 的返回(会去除多余的空白,'/'等字符)
- 第三个 根据状态有所不同： 
  - `FOUND` 路由信息 `array`
  - `NOT_FOUND` 为空 `null`
  - `METHOD_NOT_ALLOWED` 返回的是允许的 METHODs `array`
- 结构信息如下:

```php
[
    // 路由匹配结果状态. 
    // 可能为： RouterInterface::FOUND, RouterInterface::NOT_FOUND, RouterInterface::METHOD_NOT_ALLOWED
    INT, 
    
    // 格式化后的 $path 的返回(会去除多余的空白,'/'等字符)
    'URI PATH', 
    
    // NOT_FOUND 匹配失败时为 null, 
    // METHOD_NOT_ALLOWED 返回的是允许的 METHODs
    // FOUND 时为下面的路由信息
    [
        // (可能存在)配置的请求 METHOD。 自动匹配时无此key
        'method' => 'GET', 
        
        // (必定存在)此路由的 handler callback
        'handler' => 'handler', 
        
        // (可能存在)此路由的 原始path。 仅动态路由有
        'original' => '/hi/{name}', 
        
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

已内置了一个路由调度器 `Inhere\Route\Dispatcher\Dispatcher`

```php
use Inhere\Route\Dispatcher\Dispatcher;

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
$router->get('/', App\Controllers\HomeController::class);

$router->get('/index', 'App\Controllers\HomeController@index');
$router->get('/about', 'App\Controllers\HomeController@about');
```

> NOTICE: 若第二个参数仅仅是个 类，将会尝试执行通过 `defaultAction` 配置的默认方法

### 动态匹配控制器方法

动态匹配控制器方法, 需配置 `'dynamicAction' => true`

> NOTICE: 使用动态匹配控制器方法, 应当使用 `any()` 添加路由. 即此时无法限定请求方法 `REQUEST_METHOD`

```php
// 访问 '/home/test' 将会执行 'App\Controllers\HomeController::test()'
$router->any('/home/{any}', App\Controllers\HomeController::class);

// 可匹配 '/home', '/home/test' 等
$router->any('/home[/{name}]', App\Controllers\HomeController::class);
```

> NOTICE: 上面两个的区别是 第一个无法匹配 `/home`

### 使用方法执行器

配置 `actionExecutor` 为你需要的方法名，例如配置为 `'actionExecutor' => 'run'`，那所有的方法请求都会提交给此方法。
会将真实的 action 作为参数传入`run($action)`, 需要你在此方法中调度来执行真正的请求方法。

> NOTICE: 在你需要将路由器整合到自己的框架时很有用

示例：

```php
// 访问 '/user', 将会调用 App\Controllers\UserController::run('')
$router->get('/user', 'App\Controllers\UserController');

// 访问 '/user/profile', 将会调用 App\Controllers\UserController::run('profile')
$router->get('/user/profile', 'App\Controllers\UserController');

// 同时配置 'actionExecutor' => 'run' 和 'dynamicAction' => true,
// 访问 '/user', 将会调用 App\Controllers\UserController::run('')
// 访问 '/user/profile', 将会调用 App\Controllers\UserController::run('profile')
$router->any('/user[/{name}]', 'App\Controllers\UserController');
```

## 开始路由匹配和调度

```php
$router->dispatch($dispatcher);
```

## 运行示例

示例代码在 `example` 下。

- 对象版本

你可以通过 `php -S 127.0.0.1:5670 example/object.php` 来运行一个测试服务器, 现在你可以访问 http://127.0.0.1:5671

## 测试 

```bash
phpunit
```

- simple benchmark

```bash
php example/benchmark.php
```

## License 

MIT

## 我的其他项目

### `inhere/console` [github](https://github.com/inhere/php-console) [git@osc](https://git.oschina.net/inhere/php-console)

功能丰富的命令行应用，命令行工具库

### `inhere/php-validate` [github](https://github.com/inhere/php-validate)  [git@osc](https://git.oschina.net/inhere/php-validate)
 
 一个简洁小巧且功能完善的php验证库。仅有几个文件，无依赖。
 