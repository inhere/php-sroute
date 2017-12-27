# 参数路由分析

> `AbstractRouter::parseParamRoute()`

## 无正则的字符串分析

```php
        // 分析路由字符串是否是有规律的
        $first = null;
        $conf['regex'] = '#^' . $route . '$#';

        // has optional param ?
        $waitParse = $noOptional ?: $bak;
        
        $waitParse = ltrim($waitParse, '/');
        $posSlash = strpos($waitParse, '/');
        $posBrace = strpos($waitParse, '{');
        
        // no fist node. '/{name}' '/hello[/{name}]' '/blog-{category}'
        if ($posSlash === false) {
            $conf['include'] = null;
        
            if ($posBrace === false) {
                $conf['include'] = '/' . $waitParse;
            } elseif ($posBrace > 0) {
                $conf['include'] = '/' . substr($waitParse, 0, $posBrace);
            }
        
            // eg '/hello/friend[/{name}]'
        } elseif ($posBrace === false) {
            $first = substr($waitParse, 0, $posSlash);
            $conf['start'] = '/' . $waitParse;
        
            // eg '/user/{id}'
        } elseif ($posBrace > $posSlash) {
            $first = substr($waitParse, 0, $posSlash);
            $conf['start'] = '/' . substr($waitParse, 0, $posBrace);
        
            // '/{name}/profile'
        } else {
            $lastPart = substr($waitParse, $posSlash);
            $conf['include'] = $lastPart && self::isStaticRoute($lastPart) ? $lastPart : null;
        }
```

## 正则的字符串分析

```php
        // 分析路由字符串是否是有规律的
        $first = null;
        $conf['regex'] = '#^' . $route . '$#';

        // e.g '/user/{id}' first: 'user', '/a/{post}' first: 'a'
        // first node is a normal string
        if (preg_match('#^/([\w-]+)/[\w-]*/?#', $bak, $m)) {
            $first = $m[1];
            $conf['start'] = $m[0];
            // first node contain regex param '/hello[/{name}]' '/{some}/{some2}/xyz'
        } else {
            $include = null;

            if ($noOptional) {
                if (strpos($noOptional, '{') === false) {
                    $include = $noOptional;
                } else {
                    $bak = $noOptional;
                }
            }

            if (!$include && preg_match('#/([\w-]+)/?[\w-]*#', $bak, $m)) {
                $include = $m[0];
            }

            $conf['include'] = $include;
        }
```
