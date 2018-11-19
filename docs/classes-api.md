# 关键方法参考

## 添加路由方法

```php
public function map(string|array $methods, string $path, mixed $handler, array $opts = [])
```

添加路由方法

> 其他的添加路由方法底层都是调用的 `map()` 方法，除了没有第一个参数外，其他参数都是一样的

- `$methods` string/array 请求的METHOD. e.g `GET` `['GET', 'POST]`
- `$path` string 定义的路由字符串 e.g `/user/login` `/article/{id}`
- `$handler` string/object 对应路由的处理者
- `$binds` array 路由参数匹配限制 eg `[ 'name' => '\w+' ]`
- `$opts` array 选项设置，可以添加自定义的数据。匹配成功会将选项数据返回(e.g middleware, domains),自己再做进一步验证等。下面是已使用的选项
    - `defaults` 有可选参数时，可以设置默认值

一个较为完整的示例：

> 提示： 若不在选项中设置参数匹配，那么参数将会匹配除了 '/' 之外的任何字符

```php
$router->map(['get', 'post'], '/im/{name}[/{age}]', function(array $params) {
    var_dump($params);
}, [ // 设置参数匹配
     'name' => '\w+',
     'age' => '\d+',
 ],[
     'defaults' => [
         'age' => 20, // 给可选参数 age 添加一个默认值
     ]
    
    // 可添加更多自定义设置
    'middleware' => ['AuthCheck'],
    ... ...
]);
```

Now, 访问 `/im/john/18` 或者 `/im/john` 查看效果

## 路由匹配

```php 
array public function match(string $path, string $method)
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
  - `FOUND` 路由信息对象 `Route`
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
    
    // 第三个元素
    // NOT_FOUND 匹配失败时为 null, 
    // METHOD_NOT_ALLOWED 返回的array, 是允许的 METHODs 
    // FOUND 时为匹配的路由信息对象，object(Route)
]
```
