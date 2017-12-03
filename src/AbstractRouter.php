<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/17
 * Time: 下午11:37
 */

namespace Inhere\Route;

/**
 * Class AbstractRouter
 * @package Inhere\Route
 */
abstract class AbstractRouter implements RouterInterface
{
    /**
     * some available patterns regex
     * $router->get('/user/{num}', 'handler');
     * @var array
     */
    protected static $globalParams = [
        'any' => '[^/]+',   // match any except '/'
        'num' => '[0-9]+',  // match a number
        'id'  => '[1-9][0-9]*',  // match a ID number
        'act' => '[a-zA-Z][\w-]+', // match a action name
        'all' => '.*'
    ];

    /**
     * some setting for self
     * @var array
     */
    protected $config = [
        // the routes php file.
        'routesFile' => '',

        // ignore last '/' char. If is True, will clear last '/'.
        'ignoreLastSep' => false,

        // 'tmpCacheNumber' => 100,
        'tmpCacheNumber' => 0,

        // match all request.
        // 1. If is a valid URI path, will matchAll all request uri to the path.
        // 2. If is a closure, will matchAll all request then call it
        // eg: '/site/maintenance' or `function () { echo 'System Maintaining ... ...'; }`
        'matchAll' => false,

        // auto route match @like yii framework
        // If is True, will auto find the handler controller file.
        'autoRoute' => false,
        // The default controllers namespace, is valid when `'enable' = true`
        'controllerNamespace' => '', // eg: 'app\\controllers'
        // controller suffix, is valid when `'enable' = true`
        'controllerSuffix' => '',    // eg: 'Controller'
    ];

    /**
     * validate and format arguments
     * @param string|array $methods
     * @param mixed $handler
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function validateArguments($methods, $handler)
    {
        if (!$methods || !$handler) {
            throw new \InvalidArgumentException('The method and route handler is not allow empty.');
        }

        $allow = implode(',', self::SUPPORTED_METHODS) . ',';
        $methods = array_map(function ($m) use($allow) {
            $m = strtoupper(trim($m));

            if (!$m || false === strpos($allow, $m . ',')) {
                throw new \InvalidArgumentException("The method [$m] is not supported, Allow: $allow");
            }

            return $m;
        }, (array)$methods);

        if (!\is_string($handler) && !\is_object($handler)) {
            throw new \InvalidArgumentException('The route handler is not empty and type only allow: string,object');
        }

        if (\is_object($handler) && !\is_callable($handler)) {
            throw new \InvalidArgumentException('The route object handler must be is callable');
        }

        $methods = implode(',', $methods) . ',';

        if (false !== strpos($methods, self::ANY)) {
            return trim($allow, ',');
        }

        return trim($methods, ',');
    }

    /**
     * is Static Route
     * @param string $route
     * @return bool
     */
    protected static function isStaticRoute($route)
    {
        return strpos($route, '{') === false && strpos($route, '[') === false;
    }

    /**
     * @param string $path
     * @return string
     */
    protected function getFirstFromPath($path)
    {
        $tmp = trim($path, '/'); // clear first,end '/'

        // eg '/article/12'
        if (strpos($tmp, '/')) {
            return strstr($tmp, '/', true);
        }

        // eg '/about.html'
        if (strpos($tmp, '.')) {
            return strstr($tmp, '.', true);
        }

        return $tmp;
    }

    /**
     * @param string $path
     * @param bool $ignoreLastSep
     * @return string
     */
    protected function formatUriPath($path, $ignoreLastSep)
    {
        // clear '//', '///' => '/'
        $path = rawurldecode(preg_replace('#\/\/+#', '/', $path));

        // setting 'ignoreLastSep'
        if ($path !== '/' && $ignoreLastSep) {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    /**
     * @param array $matches
     * @param array $conf
     * @return array
     */
    protected static function filterMatches(array $matches, array $conf)
    {
        // clear all int key
        $matches = array_filter($matches, '\is_string', ARRAY_FILTER_USE_KEY);

        // apply some default param value
        if (isset($conf['option']['defaults'])) {
            $matches = array_merge($conf['option']['defaults'], $matches);
        }

        // decode ...
        // foreach ($matches as $k => $v) {
        //     $matches[$k] = urldecode($v);
        // }

        return $matches;
    }

    /**
     * @param string $route
     * @param array $params
     * @param array $conf
     * @return array
     * @throws \LogicException
     */
    public function parseParamRoute($route, array $params, array $conf)
    {
        $tmp = $route;

        // 解析可选参数位
        // '/hello[/{name}]'      match: /hello/tom   /hello
        // '/my[/{name}[/{age}]]' match: /my/tom/78  /my/tom
        if (false !== strpos($route, ']')) {
            $withoutClosingOptionals = rtrim($route, ']');
            $optionalNum = \strlen($route) - \strlen($withoutClosingOptionals);

            if ($optionalNum !== substr_count($withoutClosingOptionals, '[')) {
                throw new \LogicException('Optional segments can only occur at the end of a route');
            }

            // '/hello[/{name}]' -> '/hello(?:/{name})?'
            $route = str_replace(['[', ']'], ['(?:', ')?'], $route);
        }

        // quote '.','/' to '\.','\/'
        // $route = preg_quote($route, '/');
        $route = str_replace('.', '\.', $route);

        // 解析参数，替换为对应的 正则
        if (preg_match_all('#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#', $route, $m)) {
            /** @var array[] $m */
            $replacePairs = [];

            foreach ($m[1] as $name) {
                $key = '{' . $name . '}';
                // 匹配定义的 param  , 未匹配到的使用默认 self::DEFAULT_REGEX
                $regex = isset($params[$name]) ? $params[$name] : self::DEFAULT_REGEX;

                // 将匹配结果命名 (?P<arg1>[^/]+)
                $replacePairs[$key] = '(?P<' . $name . '>' . $regex . ')';
                // $replacePairs[$key] = '(' . $regex . ')';
            }

            $route = strtr($route, $replacePairs);
        }

        // 分析路由字符串是否是有规律的
        $first = null;
        $regex = '#^' . $route . '$#';

        // e.g '/hello[/{name}]' first: 'hello', '/user/{id}' first: 'user', '/a/{post}' first: 'a'
        // first node is a normal string
        // if (preg_match('#^/([\w-]+)#', $tmp, $m)) {
        // if (preg_match('#^/([\w-]+)/?[\w-]*#', $tmp, $m)) {
        if (preg_match('#^/([\w-]+)/[\w-]*#', $tmp, $m)) {
            $first = $m[1];
            $info = [
                'regex'  => $regex,
                'start' => $m[0],
                'original' => $tmp,
            ];
            // first node contain regex param '/{some}/{some2}/xyz'
        } else {
            $include = null;

            if (preg_match('#/([\w-]+)/?[\w-]*#', $tmp, $m)) {
                $include = $m[0];
            }

            $info = [
                'regex' => $regex,
                'include' => $include,
                'original' => $tmp,
            ];
        }

        return [$first, array_merge($info, $conf)];
    }

    /**
     * handle auto route match, when config `'autoRoute' => true`
     * @param string $path The route path
     * @param string $controllerNamespace controller namespace. eg: 'app\\controllers'
     * @param string $controllerSuffix controller suffix. eg: 'Controller'
     * @return bool|callable
     */
    public function matchAutoRoute($path, $controllerNamespace, $controllerSuffix = '')
    {
        $cnp = trim($controllerNamespace);
        $sfx = trim($controllerSuffix);
        $tmp = trim($path, '/- ');

        // one node. eg: 'home'
        if (!strpos($tmp, '/')) {
            $tmp = self::convertNodeStr($tmp);
            $class = "$cnp\\" . ucfirst($tmp) . $sfx;

            return class_exists($class) ? $class : false;
        }

        $ary = array_map([self::class, 'convertNodeStr'], explode('/', $tmp));
        $cnt = \count($ary);

        // two nodes. eg: 'home/test' 'admin/user'
        if ($cnt === 2) {
            list($n1, $n2) = $ary;

            // last node is an controller class name. eg: 'admin/user'
            $class = "$cnp\\$n1\\" . ucfirst($n2) . $sfx;

            if (class_exists($class)) {
                return $class;
            }

            // first node is an controller class name, second node is a action name,
            $class = "$cnp\\" . ucfirst($n1) . $sfx;

            return class_exists($class) ? "$class@$n2" : false;
        }

        // max allow 5 nodes
        if ($cnt > 5) {
            return false;
        }

        // last node is an controller class name
        $n2 = array_pop($ary);
        $class = sprintf('%s\\%s\\%s', $cnp, implode('\\', $ary), ucfirst($n2) . $sfx);

        if (class_exists($class)) {
            return $class;
        }

        // last second is an controller class name, last node is a action name,
        $n1 = array_pop($ary);
        $class = sprintf('%s\\%s\\%s', $cnp, implode('\\', $ary), ucfirst($n1) . $sfx);

        return class_exists($class) ? "$class@$n2" : false;
    }

    /**
     * @param array $tmpParams
     * @return array
     */
    public function getAvailableParams(array $tmpParams)
    {
        $params = self::$globalParams;

        if ($tmpParams) {
            foreach ($tmpParams as $name => $pattern) {
                $key = trim($name, '{}');
                $params[$key] = $pattern;
            }
        }

        return $params;
    }

    /**
     * convert 'first-second' to 'firstSecond'
     * @param $str
     * @return mixed|string
     */
    public static function convertNodeStr($str)
    {
        $str = trim($str, '-');

        // convert 'first-second' to 'firstSecond'
        if (strpos($str, '-')) {
            $str = preg_replace_callback('/-+([a-z])/', function ($c) {
                return strtoupper($c[1]);
            }, trim($str, '- '));
        }

        return $str;
    }

    /**
     * @param array $params
     */
    public function addGlobalParams(array $params)
    {
        foreach ($params as $name => $pattern) {
            $this->addGlobalParam($name, $pattern);
        }
    }

    /**
     * @param $name
     * @param $pattern
     */
    public function addGlobalParam($name, $pattern)
    {
        $name = trim($name, '{} ');
        self::$globalParams[$name] = $pattern;
    }

    /**
     * @param null|string $name
     * @param null|mixed $default
     * @return array|string
     */
    public function getConfig($name = null, $default = null)
    {
        if ($name) {
            return isset($this->config[$name]) ? $this->config[$name] : $default;
        }

        return $this->config;
    }

    /**
     * @return array
     */
    public static function getGlobalParams()
    {
        return self::$globalParams;
    }

    /**
     * @return array
     */
    public static function getSupportedMethods()
    {
        return self::SUPPORTED_METHODS;
    }
}
