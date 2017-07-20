# router 路由器

路由器 - 负责路由收集，路由匹配，返回匹配到的路由信息。

> 匹配速度快，查找匹配速度基本上不受路由数量和路由的复杂度的影响

- `inhere\sroute\SRouter` 是静态类版本. 
- `inhere\sroute\ORouter` 是对象版本

压测： [benchmark](./benchmark.md)

## 路由收集

```php
use inhere\sroute\SRouter;

// 匹配 GET 请求. 处理器是个闭包 Closure
SRouter::get('/', function() {
    echo 'hello';
});

// 匹配参数 'test/john'
SRouter::get('/test/{name}', function($arg) {
    echo $arg; // 'john'
}, [
    'tokens' => [
        'name' => '\w+', // 添加参数匹配限制。若不添加对应的限制，将会自动设置为匹配除了'/'外的任何字符
    ]
]);

// 可选参数支持。匹配  'hello' 'hello/john'
SRouter::get('/hello[/{name}]', function($name = 'No') {
    echo $name; // 'john'
}, [
    'tokens' => [
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

// 路由组
SRouter::group('/user', function () {
    SRouter::get('/', function () {
        echo 'hello. you access: /user/';
    });
    SRouter::get('/index', function () {
        echo 'hello. you access: /user/index';
    });
});
```

使用 `ORouter` 则需先创建对象：

```php
use inhere\sroute\ORouter;

$router = new ORouter;

// 添加路由
// $router->get();
// $router->post();
// $router->put();
// ... ...
$router->group('/user', function ($router) {
    /** @var \inhere\sroute\ORouter $router */
    $router->get('', function () {
        echo 'hello. you access: /user';
    });

    //$router->get('/', function () {
    //    echo 'hello. you access: /user/';
    //});

    $router->get('/index', function () {
        echo 'hello. you access: /user/index';
    });
});
```

> 如果配置了 `'ignoreLastSep' => true`, '/index' 等同于 '/index/'

### 如何收集

#### 静态路由
#### 动态路由

- 有规律的动态路由
- 无规律的动态路由

## 路由匹配

```php 
array|false public function match($path, $method)
```

- `$path` string 请求的URI path
- `$method` string 请求的request method
- 返回 `array|false`
    - `false` 匹配失败。没有找到匹配的路由 
    - `array` 匹配成功。返回匹配到的路由信息, 然后你就可以根据此信息进行自定义的路由调度了。

根据请求的 URI path 和 请求 METHOD 查找匹配我们定义的路由信息。

```php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$route = SRouter::match($path, $method);
```

匹配成功，将会返回如下格式的信息.可以根据此信息进行路由调度

```php
[
    'URI PATH', // 格式化后的 $path 的返回(会去除多余的空白,'/'等字符)
    // 路由信息
    [
        'method' => 'GET', // 配置的请求 METHOD
        'handler' => 'handler', // 此路由的 handler
        // 此路由的自定义选项信息. 
        // tokens - 来自添加路由时设置的参数匹配信息, 若有的话
        // 可以自定义此路由的选项：如下经供参考
        // - domains 允许访问路由的域名
        // - schema 允许访问路由的schema
        // - enter 进入路由的事件回调
        // ... ...
        'option' => [
            'tokens' => [],

            // 'domains' => null,
            // 'schema' => null, // ['http','https'],
            // route event. custom design ...
            // 'enter' => null,
            // 'leave' => null,
        ], 
    ]
]
```

### 匹配原理

todo ...

## 路由配置

```php
// set config
SRouter::setConfig([
    'ignoreLastSep' => true,    
    'autoRoute' => [
        'enable' => 1,
        'controllerNamespace' => 'app\\controllers',
        'controllerSuffix' => 'Controller',
    ],
]);
```

### 默认配置如下

```php
// 所有的默认的配置
[   
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
    ]
]
```

> NOTICE: 必须在添加路由之前调用 `SRouter::setConfig()` 
