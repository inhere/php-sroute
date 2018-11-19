# dispatcher - 路由调度器

路由调度器 - 按匹配到的路由信息规则，去调度对应的handler，并返回执行结果。

## 配置

```php
[
    // 是否过滤 /favicon.ico 请求
    'filterFavicon' => false,
    
    // 默认的控制器方法名称
    'defaultAction' => 'index',

    // 启用动态action
    // e.g
    // 若设置为 True;
    //  SRouter::any('/demo/(\w+)', app\controllers\Demo::class);
    //  访问 '/demo/test' 将会调用 'app\controllers\Demo::test()'
    'dynamicAction' => false,

    // 方法执行器. 
    // e.g
    //  `run($action)`
    //  SRouter::any('/demo/(:act)', app\controllers\Demo::class);
    //  访问 `/demo/test` 将会调用 `app\controllers\Demo::run('test')`
    'actionExecutor' => '', // 'run'
]
```

## 路由调度

### 使用控制器方法

通过`@`符号连接控制器类和方法名可以指定执行方法。

```php
SRouter::get('/', app\controllers\Home::class);
SRouter::get('/index', 'app\controllers\Home@index');
SRouter::get('/about', 'app\controllers\Home@about');
```

> NOTICE: 若第二个参数仅仅是个 类，将会尝试执行通过 `defaultAction` 配置的默认方法

### 动态匹配控制器方法

动态匹配控制器方法, 需配置 `'dynamicAction' => true`

> NOTICE: 使用动态匹配控制器方法, 应当使用 `any()` 添加路由. 即此时无法限定请求方法 `REQUEST_METHOD`

```php
// 访问 '/home/test' 将会执行 'app\controllers\Home::test()'
SRouter::any('/home/{any}', app\controllers\Home::class);

// 可匹配 '/home', '/home/test' 等
SRouter::any('/home[/{name}]', app\controllers\Home::class);
```

> NOTICE: 上面两个的区别是 第一个无法匹配 `/home`

### 使用方法执行器

配置 `actionExecutor` 为你需要的方法名，例如配置为 `'actionExecutor' => 'run'`，那所有的方法请求都会提交给此方法。
会将真实的 action 作为参数传入`run($action)`, 需要你在此方法中调度来执行真正的请求方法。

> NOTICE: 在你需要将路由器整合到自己的框架时很有用

示例：

```php
// 访问 '/user', 将会调用 app\controllers\User::run('')
SRouter::get('/user', 'app\controllers\User');

// 访问 '/user/profile', 将会调用 app\controllers\User::run('profile')
SRouter::get('/user/profile', 'app\controllers\User');

// 同时配置 'actionExecutor' => 'run' 和 'dynamicAction' => true,
// 访问 '/user', 将会调用 app\controllers\User::run('')
// 访问 '/user/profile', 将会调用 app\controllers\User::run('profile')
SRouter::any('/user[/{name}]', 'app\controllers\User');
```

## 开始调度

1. 先创建调度器

```php
use Inhere\Route\Dispatcher;

$dispatcher = new Dispatcher([
    'dynamicAction' => true,
]);
```

2. 设置路由匹配器

- 使用 `SRouter`

```php
$dispatcher->setMatcher(function ($path, $method) {
    return SRouter::match($path, $method);
});
```

- 使用 `Router`

```php
$dispatcher->setMatcher(function ($path, $method) use ($router) {
    return $router->match($path, $method);
});
```

3. 开始路由匹配和调度

```php
// 根据请求的 URI 和 METHOD 请行调度
$dispatcher->dispatch();

// 也可以无需设置上面的匹配器(省去第二步)。直接将 $dispatcher 传入路由器中，内部会自动设置匹配器
// SRouter::dispatch($dispatcher);
// $router->dispatch($dispatcher);
```

## 示例

示例代码在 `example` 下。

- SRouter

你可以通过 `php -S 127.0.0.1:5670 -t example/static` 来运行一个测试服务器, 现在你可以访问 http://127.0.0.1:5670

- Router

你可以通过 `php -S 127.0.0.1:5671 -t example/object` 来运行一个测试服务器, 现在你可以访问 http://127.0.0.1:5671
