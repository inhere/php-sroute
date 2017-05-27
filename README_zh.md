# php simple router

非常轻量级的单一文件的路由器。简洁、自定义性强

> 基础逻辑参考自项目 **[noahbuscher\macaw](https://github.com/noahbuscher/Macaw)** , 添加了一些功能。

- 支持请求方法: `GET` `POST` `PUT` `DELETE` `HEAD` `OPTIONS`
- 支持事件: `found` `notFound`. 你可以做一些事情当触发事件时(比如记录日志等)
- 支持设置匹配路由的解析器: `SRoute::setMatchedRouteParser()`. 你可以自定义如何调用匹配的路由处理程序.
- 支持自动匹配路由到控制器就像 yii 一样, 请参看配置项 `autoRoute`. 
- 支持手动调度一个路由通过方法 `SRoute::dispatchTo()`
- 你也可以不配置任何东西, 它也能很好的工作

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
git clone https://github.com/inhere/php-srouter.git
```

## 使用

首先, 导入类

```php
use inhere\sroute\SRoute;
```

## 添加路由

```php
// 匹配 GET 请求. 处理器是个闭包 Closure
SRoute::get('/', function() {
    echo 'hello';
});

// 匹配参数 'test/john'
SRoute::get('/test/(\w+)', function($arg) {
    echo $arg; // 'john'
});

// 匹配 POST 请求
SRoute::post('/user/login', function() {
    var_dump($_POST);
});

// 匹配 GET 或者 POST
SRoute::map(['get', 'post'], '/user/login', function() {
    var_dump($_GET, $_POST);
});

// 允许任何请求方法
SRoute::any('/home', function() {
    echo 'hello, you request page is /home';
});
```

> 如果配置了 `'ignoreLastSep' => true`, '/index' 等同于 '/index/'

### 使用控制器方法

```php
SRoute::get('/index', 'app\controllers\Home@index');
```

### 动态匹配控制器方法

动态匹配控制器方法, 需配置 `'dynamicAction' => true`

> NOTICE: 使用动态匹配控制器方法, 应当使用 `any()` 添加路由. 即此时无法限定请求方法 `REQUEST_METHOD`

```php
// 访问 '/home/test' 将会执行 'app\controllers\Home::test()'
SRoute::any('/home/(\w+)', app\controllers\Home::class);

// 可匹配 '/home', '/home/test' 等
SRoute::any('/home(/\w+)?', app\controllers\Home::class);
```

> 上面两个的区别是 第一个无法匹配 `/home`

### 使用方法执行器

配置 `actionExecutor` 为你需要的方法名，例如配置为 `'actionExecutor' => 'run'`，那所有的方法请求都会提交给此方法。
会将真实的 action 作为参数传入`run($action)`, 需要你在此方法中调度来执行真正的请求方法。

> 在你需要将路由器整合到自己的框架时很有用

示例：

```php
// 访问 '/user', 将会调用 app\controllers\User::run('')
SRoute::get('/user', 'app\controllers\User');

// 访问 '/user/profile', 将会调用 app\controllers\User::run('profile')
SRoute::get('/user/profile', 'app\controllers\User');

// 同时配置 'actionExecutor' => 'run' 和 'dynamicAction' => true,
// 访问 '/user', will call app\controllers\User::run('')
// 访问 '/user/profile', will call app\controllers\User::run('profile')
SRoute::get('/user(/\w+)?', 'app\controllers\User');
```

## 自动匹配路由到控制器

支持自动匹配路由到控制器就像 yii 一样, 需配置 `autoRoute`. 

```php 
    'autoRoute' => [
        'enable' => 1, // 启用
        'controllerNamespace' => 'app\\controllers', // 控制器类所在命名空间
        'controllerSuffix' => 'Controller', // 控制器类后缀
    ],
```

## 匹配所有

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
        echo 'System Maintaining ... ...';
    },
```

将会直接执行此回调后停止执行


## 设置事件处理(if you need)

```php
SRoute::any('/404', function() {
    echo "Sorry,This page {$_GET['path']} not found.";
});
```

```php
// 成功匹配路由
SRoute::on(SRoute::FOUND, function ($uri, $cb) use ($app) {
    $app->logger->debug("Matched uri path: $uri, setting callback is: " . is_string($cb) ? $cb : get_class($cb));
});

// 当匹配失败, 重定向到 '/404'
SRoute::on('notFound', '/404');

// 或者, 当匹配失败, 输出消息...
SRoute::on('notFound', function ($uri) {
    echo "the page $uri not found!";
});
```

## 设置配置(if you need)

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
    
    'autoRoute' => [
        'enable' => 1,
        'controllerNamespace' => 'examples\\controllers',
        'controllerSuffix' => 'Controller',
    ],
]);
```

- 默认配置如下

```php
// 所有的默认的配置
[
    // 是否成功匹配后停止。即只匹配一个
    'stopOnMatch' => true,
    
    // 是否过滤 /favicon.ico 请求
    'filterFavicon' => false,
    
    // 是否忽略最后的 '/' 分隔符. 如果是 true,将清除最后一个 '/', 此时请求 '/home' 和 '/home/' 效果相同
    'ignoreLastSep' => false,

    // 匹配所有请求
    // 1. 如果是一个有效的URI路径,将匹配所有请求到此URI路径。
    // 2. 如果是一个可用回调,将匹配所有请求然后调用它
    'matchAll' => '', // 例如: '/site/maintenance' 或者 `function () { echo 'System Maintaining ... ...'; }`

    // 自动匹配路由到控制器就像 yii 一样 
    'autoRoute' => [
        // 是否启用
        'enable' => false,
        // 默认控制器名称空间
        'controllerNamespace' => '', // eg: 'app\\controllers'
        // 控制器类后缀
        'controllerSuffix' => '',    // eg: 'Controller'
    ],

    // 默认的控制器方法名称
    'defaultAction' => 'index',

    // 启用动态action
    // e.g
    // 若设置为 True;
    //  SRoute::any('/demo/(\w+)', app\controllers\Demo::class);
    //  访问 '/demo/test' 将会调用 'app\controllers\Demo::test()'
    'dynamicAction' => false,

    // 方法执行器. 
    // e.g
    //  `run($action)`
    //  SRoute::any('/demo/(:act)', app\controllers\Demo::class);
    //  访问 `/demo/test` 将会调用 `app\controllers\Demo::run('test')`
    'actionExecutor' => '', // 'run'
]
```

> NOTICE: 必须在调用 `SRoute::dispatch()` 之前使用 `SRoute::config()` 来进行一些配置

## 开始路由分发

```php
SRoute::dispatch();
```

## 运行示例

示例代码在 `examples` 下。

你可以通过 `bash ./php_server` 来运行一个测试服务器, 现在你可以访问 http://127.0.0.1:5670

## License 

MIT
