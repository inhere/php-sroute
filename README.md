# php simple router

[![License](https://img.shields.io/packagist/l/inhere/sroute.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/inhere/sroute.svg?colorB=green)](https://packagist.org/packages/inhere/sroute)
[![Latest Stable Version](http://img.shields.io/packagist/v/inhere/sroute.svg)](https://packagist.org/packages/inhere/sroute)
[![Build Status](https://travis-ci.org/inhere/php-srouter.svg?branch=master)](https://travis-ci.org/inhere/php-srouter)
[![Coverage Status](https://coveralls.io/repos/github/inhere/php-srouter/badge.svg?branch=master)](https://coveralls.io/github/inhere/php-srouter?branch=master)

非常快速且轻量的请求匹配路由器。

  - 无依赖、简洁、速度快、功能完善
  - 轻量级且速度快，查找速度不受路由数量的影响
  - 支持路由组, 支持路由参数定义，以及丰富的自定义路由选项
  - 支持给指定的路由命名，可根据名称拿到注册的路由对象
  - 支持请求方法: `GET` `POST` `PUT` `DELETE` `HEAD` `OPTIONS` ...
  - 支持自动匹配路由到控制器就像 Yii 一样, 请参看配置项 `autoRoute` (不推荐)
  - 压测对比数据请看[路由测试](#ab-test)

**多个版本：**

> 不同的版本有稍微的区别以适应不同的场景

- `Router` 通用版本，也是后几个版本的基础类，适用于所有的情况。
- `SRouter` 静态类版本。`Router` 的简单包装，通过静态方法使用(方便小应用快速使用)
- `CachedRouter` 继承自`Router`，支持路由缓存的版本，可以 **缓存路由信息到文件**
  - 适合php-fpm 环境使用(有缓存将会省去每次的路由收集和解析消耗) 
- `PreMatchRouter` 继承自`Router`，预匹配路由器。**当应用的静态路由较多时，将拥有更快的匹配速度**
  - 适合php-fpm 环境，php-fpm 情形下，实际上我们在收集路由之前，已经知道了路由path和请求动作METHOD
- `ServerRouter` 继承自`Router`，服务器路由。内置支持**动态路由临时缓存**. 适合 `swoole` 等**常驻内存应用**使用
  - 最近请求过的动态路由将会缓存为一个静态路由信息，下次相同路由将会直接匹配命中

**内置调度器：**

- 支持事件: `found` `notFound` `execStart` `execEnd` `execError`. 当触发事件时你可以做一些事情(比如记录日志等)
- 支持动态获取`action`名。支持设置方法执行器(`actionExecutor`)，通过方法执行器来自定义调用真实请求方法. 
- 支持通过方法 `$router->dispatch($path, $method)` 手动调度一个路由
- 你即使不配置任何东西, 它也能很好的工作

**路由器管理**

`RouterManager` 当需要在一个项目里处理多个域名下的请求时，方便的根据不同域名配置多个路由器

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
```

<a name="ab-test"></a>
## 压测

自动生成了1000条路由，每条有9个参数位，分别测试1000次的 

- 第一条路由匹配
- 最后一条路由匹配
- 不存在的路由匹配

详细的测试代码请看仓库 https://github.com/ulue/php-router-benchmark

- 压测日期 **2018.11.19**
- An example route: `/9b37eef21e/{arg1}/{arg2}/{arg3}/{arg4}/{arg5}/{arg6}/{arg7}/{arg8}/{arg9}/bda37e9f9b`

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

## 使用说明

> 各个版本的方法名和参数都是一样的

首先, 需要导入类

```php
use Inhere\Route\Router;

$router = new Router();
```

### 快速开始

创建一个简单的 `public/index.php` 文件:

```php
use Inhere\Route\Router;

// 需要先加载 autoload 文件
require dirname(__DIR__) . '/vendor/autoload.php';

$router = new Router();

$router->get('/', function() {
    echo 'hello';
});

// 开始调度运行
$router->dispatch();
```

使用php启动一个测试server： `php -S 127.0.0.1:8080 -t ./public`

好了，现在你可以访问 http://127.0.0.1:8080 可以看到输出 `hello`

- 不使用 Composer

如果是直接下载的包代码，可以加载 `test/boot.php` 文件，也可以加载到 `Inhere\Route` 命名空间.

用如下的语句替换上面的 `autoload.php` 加载语句即可：

```php
require dirname(__DIR__) . '/test/boot.php';
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
      'name' => '\w+', // 添加参数匹配限制。若不添加对应的限制，将会自动设置为匹配除了'/'外的任何字符
]);

// 可选参数支持。匹配  'hello' 'hello/john'
$router->get('/hello[/{name}]', function() {
    echo $params['name'] ?? 'No input'; // 'john'
}, [
     'name' => '\w+', // 添加参数匹配限制
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
```

### 使用路由组

```php
// 路由组
$router->group('/user', function ($router) {
    $router->get('/', function () {
        echo 'hello. you access: /user/';
    });
    $router->get('/index', function () {
        echo 'hello. you access: /user/index';
    });
});
```

### 使用控制器

```php
// 使用 控制器
$router->get('/', App\Controllers\HomeController::class);
$router->get('/index', 'App\Controllers\HomeController@index');
```

### 备用路由处理

可以注册一个备用路由处理。当没匹配到时，就会使用它

```php
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

## 设置路由配置

```php
// set config
$router->config([
    'ignoreLastSlash' => true,    
    'autoRoute' => 1,
    'controllerNamespace' => 'app\\controllers',
    'controllerSuffix' => 'Controller',
]);
```

> NOTICE: 必须在添加路由之前调用 `$router->config()` 

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

$routeInfo = $router->match($path, $method);
```

根据返回的路由信息，我们就可以自由的决定如何调用对应的处理。

> 关于返回的数据结构，可以查看 [关键方法参考](docs/classes-api.md)

## 路由调度

如果你不想自己实现路由调度，可以使用内置的路由调度器 `Inhere\Route\Dispatcher\Dispatcher`

```php
use Inhere\Route\Dispatcher\Dispatcher;

$dispatcher = new Dispatcher([
    // default action method name
    'defaultAction' => 'index',

    'actionPrefix' => '',

    'actionSuffix' => 'Action',

    'dynamicAction' => true,
    // @see Router::$globalParams['act']
    'dynamicActionVar' => 'act',
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

动态匹配控制器方法, 需配置 

```php
'dynamicAction' => true,  // 启用
// action 方法名匹配参数名称，符合条件的才会当做action名称
// @see Router::$globalParams['act'] 匹配 '[a-zA-Z][\w-]+'
'dynamicActionVar' => 'act',
```

> NOTICE: 使用动态匹配控制器方法, 应当使用 `any()` 添加路由. 即此时不能限定请求方法 `REQUEST_METHOD`

```php
// 访问 '/home/test' 将会执行 'App\Controllers\HomeController::test()'
$router->any('/home/{act}', App\Controllers\HomeController::class);

// 可匹配 '/home', '/home/test' 等
$router->any('/home[/{act}]', App\Controllers\HomeController::class);
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
 
