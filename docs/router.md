# router 路由器

路由器 - 负责路由收集，路由匹配，返回匹配到的路由信息。

> 匹配速度快，查找匹配速度基本上不受路由数量和路由的复杂度的影响

- `Inhere\Route\SRouter` 是静态类版本. 
- `Inhere\Route\Router` 是对象版本

压测： [benchmark](./benchmark.md)

## 路由收集

```php
use Inhere\Route\Router;

$router = new Router();

// 匹配 GET 请求. 处理器是个闭包 Closure
$router->get('/', function() {
    echo 'hello';
});

// 匹配参数 'test/john'
$router->get('/test/{name}', function($arg) {
    echo $arg; // 'john'
}, [
    'params' => [
        'name' => '\w+', // 添加参数匹配限制。若不添加对应的限制，将会自动设置为匹配除了'/'外的任何字符
    ]
]);

// 可选参数支持。匹配  'hello' 'hello/john'
$router->get('/hello[/{name}]', function($name = 'No') {
    echo $name; // 'john'
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

// 路由组
$router->group('/user', function ($router) {
    /** @var \Inhere\Route\Router $router */
    $router->get('/', function () {
        echo 'hello. you access: /user/';
    });
    $router->get('/index', function () {
        echo 'hello. you access: /user/index';
    });
});
```

> 如果配置了 `'ignoreLastSlash' => true`, '/index' 等同于 '/index/'

### 实现原理(如何收集)

添加的路由将会分为三类 `静态路由` `(有规律的)动态路由` `(无规律的)动态路由`

#### 1. 静态路由

整个路由 path 都是静态字符串 e.g. '/user/login'

例如：

```php
$router->post('/user/signUp', 'handler2');
```

- 存储结构

```php
array (
  '/' => array (
    'GET' => array (
      'handler' => 'handler0',
      'option' => array (
      ),
    ),
  ),
  '/home' => array (
    'GET' => array (
      'handler' => 'Inhere\\Route\\Example\\Controllers\\HomeController@index',
      'option' => array (
      ),
    ),
  ),
  '/post' => array (
    'POST' => array (
      'handler' => 'post_handler',
      'option' => array (
      ),
    ),
  ),
  '/put' => array (
    'PUT' => array (
      'handler' => 'main_handler',
      'option' => array (
      ),
    ),
  ),
  '/del' => array (
    'DELETE' => array (
      'handler' => 'main_handler',
      'option' => array (
      ),
    ),
  ),
  '/pd' => array (
    'POST' => array (
      'handler' => 'multi_method_handler',
      'option' => array (
      ),
    ),
    'DELETE' => array (
      'handler' => 'multi_method_handler',
      'option' => array (
      ),
    ),
  ),
);
```

#### 2. (有规律的)动态路由

第一节是个静态字符串，称之为有规律的动态路由。按第一节的信息进行分组存储

例如：

```php
/*
match:
    /hello/tom
 */
$router->get('/hello/{name}', function($name='NO') {
    echo "hello, $name"; // 'john'
},[
    'params' => [
        'name' => '\w+'
    ]
]);
```

- 存储结构

```php
  'user' => array (
    0 => array (
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)$#',
      'start' => '/user/',
      'original' => '/user/{id}',
      'handler' => 'main_handler',
      'option' => array (
      ),
      'methods' => 'GET',
    ),
    1 => array (
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)$#',
      'start' => '/user/',
      'original' => '/user/{id}',
      'handler' => 'main_handler',
      'option' => array (
      ),
      'methods' => 'POST',
    ),
   ),
  'home' => array (
    0 => array (
      'regex' => '#^/home/(?P<act>[a-zA-Z][\\w-]+)$#',
      'start' => '/home/',
      'original' => '/home/{act}',
      'handler' => 'Inhere\\Route\\Example\\Controllers\\HomeController',
      'option' => array (
      ),
      'methods' => 'GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD,SEARCH,CONNECT,TRACE',
    ),
  ),
)
```

#### 3. (无规律的)动态路由

第一节就包含了正则匹配，称之为无规律/模糊的动态路由

例如： 

```php
$router->get('/{name}', 'default_handler', [
    'params' => [
        'name' => 'blog|saying'
    ]
]);
```

- 存储结构

```php
array (
  'GET' => array (
    0 => array (
      'regex' => '#^/about(?:\\.html)?$#',
      'start' => '/about',
      'original' => '/about[.html]',
      'handler' => 'Inhere\\Route\\Example\\Controllers\\HomeController@about',
      'option' => array (
      ),
    ),
    1 => array (
      'regex' => '#^/(?P<name>blog|saying)$#',
      'start' => NULL,
      'original' => '/{name}',
      'handler' => 'default_handler',
      'option' => array (
        'params' => array (
          'name' => 'blog|saying',
        ),
      ),
    ),
    2 => array (
      'regex' => '#^/test(?:/optional)?$#',
      'start' => '/test',
      'original' => '/test[/optional]',
      'handler' => 'default_handler',
      'option' => array (
      ),
    ),
    3 => array (
      'regex' => '#^/blog-(?P<post>[^/]+)$#',
      'start' => '/blog-',
      'original' => '/blog-{post}',
      'handler' => 'default_handler',
      'option' => array (
      ),
    ),
  ),
  'POST' => array( ... ),
  'PUT' => array( ... )
)
```

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

$route = $router->match($path, $method);
```

匹配失败，返回 `false`
匹配成功，将会返回如下格式的信息. 可以根据此信息进行路由调度

```php
[
    // 路由匹配结果状态. 
    // 可能为： RouterInterface::FOUND, RouterInterface::NOT_FOUND, RouterInterface::METHOD_NOT_ALLOWED
    INT, 
    
    // 格式化后的 $path 的返回(会去除多余的空白,'/'等字符)
    'URI PATH', 
    
    // 路由信息
    [
        // (可能存在)配置的请求 METHOD。 自动匹配时无此key
        'method' => 'GET', 
        
        // 此路由的 handler callback
        'handler' => 'handler', 
        
        // (可能存在)此路由的 原始path。 仅动态路由有
        'original' => '/hi/{name}', 
        
        // (可能存在) 有参数匹配的路由匹配成功后，会将参数值放入这里。 仅动态路由有
        'matches' => ['name' => value ],
        
        // 此路由的自定义选项信息. 可能为空
        // - params - 来自添加路由时设置的参数匹配信息, 若有的话
        // 还可以自定义追加此路由的选项：如下经供参考
        // - domains 允许访问路由的域名
        // - schema 允许访问路由的schema
        // - enter 进入路由的事件回调
        // ... ...
        'option' => [
            'params' => [],

            // 'domains' => null,
            // 'schema' => null, // ['http','https'],
            // route event. custom design ...
            // 'enter' => null,
            // 'leave' => null,
        ], 
        
        // (可能存在) 有参数匹配的路由匹配成功后，会将参数值放入这里
        'matches' => []
    ],
]
```

### 匹配原理

匹配优先级按 `cached -> static -> regular -> vague -> autoRoute(if it is enabled)`

todo ...

## 路由配置

```php
// set config
$router->config([
    'ignoreLastSlash' => true,    
    'autoRoute' => 1,
    'controllerNamespace' => 'app\\controllers',
    'controllerSuffix' => 'Controller',
]);
```

### 默认配置如下

```php
// 所有的默认的配置

    // 是否忽略最后的 '/' 分隔符. 如果是 true,将清除最后一个 '/', 此时请求 '/home' 和 '/home/' 效果相同
    'ignoreLastSlash' = false,

    // 是否启用, 自动匹配路由到控制器就像 yii 一样. 
    'autoRoute' => false,
    // 默认控制器名称空间
    'controllerNamespace' => '', // eg: 'app\\controllers'
    // 控制器类后缀
    'controllerSuffix' => '',    // eg: 'Controller'

```

> NOTICE: 必须在添加路由之前调用 `$router->config()` 

### 自动匹配路由

支持根据请求的URI自动匹配路由(就像 yii 一样), 需配置 `autoRoute`. 

```php 
    'autoRoute' => 1, // 启用
    'controllerNamespace' => 'app\\controllers', // 控制器类所在命名空间
    'controllerSuffix' => 'Controller', // 控制器类后缀
```

> 请参看示例 `example` 中的使用

此时请求没有配置路由的 `/demo` `/demo/test`。将会自动尝试从 `app\\controllers` 命名空间下去查找 `DemoController`

查找逻辑是 

- 只有一节的(如`/demo`)，直接定义它为控制器类名进行查找
- 大于等于两节的默认先认为最后一节是控制器类名，进行查找
- 若失败，再尝试将倒数第二节认为是控制器名，最后一节是action名

