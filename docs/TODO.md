# todo

- 调整参数路由的解析，由现在的正则分析 -> 使用字符串分析 
- 增加属性 `$routesData` 存储路由中不用于匹配的数据，减轻现有路由数据的复杂度
  - 现有的变量 `$routesData` 改成 `$routesInfo`


- 解析参数，替换为对应的 正则

```php
// method 1 - it is faster
if (\preg_match_all('#\{([a-zA-Z_][\w-]*)\}#', $route, $m)) {
    /** @var array[] $m */
    $pairs = [];

    foreach ($m[1] as $name) {
        $key = '{' . $name . '}';
        $regex = $params[$name] ?? self::DEFAULT_REGEX;

        // Name the match (?P<arg1>[^/]+)
        $pairs[$key] = '(?P<' . $name . '>' . $regex . ')';
        // $pairs[$key] = '(' . $regex . ')';
    }

    $route = \strtr($route, $pairs);
}

// method 2
$route = (string)\preg_replace_callback('#\{[a-zA-Z_][\w-]*\}#', function (array $m) use($params) {
    $name = \trim($m[0], '{}');
    $regex = $params[$name] ?? self::DEFAULT_REGEX;
    $pair = '(?P<' . $name . '>' . $regex . ')';
    // $pair = '(' . $regex . ')';

    return $pair;
}, $route);
```
