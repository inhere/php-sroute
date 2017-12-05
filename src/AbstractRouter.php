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
 *
 * @method get(string $route, mixed $handler, array $opts = [])
 * @method post(string $route, mixed $handler, array $opts = [])
 * @method put(string $route, mixed $handler, array $opts = [])
 * @method delete(string $route, mixed $handler, array $opts = [])
 * @method options(string $route, mixed $handler, array $opts = [])
 * @method head(string $route, mixed $handler, array $opts = [])
 * @method search(string $route, mixed $handler, array $opts = [])
 * @method connect(string $route, mixed $handler, array $opts = [])
 * @method trace(string $route, mixed $handler, array $opts = [])
 * @method any(string $route, mixed $handler, array $opts = [])
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

    /** @var bool */
    protected $initialized = false;

    /** @var string */
    protected $currentGroupPrefix;

    /** @var array */
    protected $currentGroupOption;

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
     * static Routes - no dynamic argument match
     * 整个路由 path 都是静态字符串 e.g. '/user/login'
     * @var array[]
     * [
     *     '/user/login' => [
     *          // METHOD => [...] // 这里 key 和 value里的 'methods' 是一样的
     *          'GET' => [
     *              'handler' => 'handler',
     *              'option' => [...],
     *          ],
     *          'PUT' => [
     *              'handler' => 'handler',
     *              'option' => [...],
     *          ],
     *          ...
     *      ],
     *      ... ...
     * ]
     */
    protected $staticRoutes = [];

    /**
     * regular Routes - have dynamic arguments, but the first node is normal string.
     * 第一节是个静态字符串，称之为有规律的动态路由。按第一节的信息进行分组存储
     * e.g '/hello[/{name}]' '/user/{id}'
     * @var array[]
     * [
     *     // 使用完整的第一节作为key进行分组
     *     'a' => [
     *          [
     *              'start' => '/a/',
     *              'regex' => '/a/(\w+)',
     *              'methods' => 'GET,POST',
     *              'handler' => 'handler',
     *              'option' => [...],
     *          ],
     *          ...
     *      ],
     *     'add' => [
     *          [
     *              'start' => '/add/',
     *              'regex' => '/add/(\w+)',
     *              'methods' => 'GET',
     *              'handler' => 'handler',
     *              'option' => [...],
     *          ],
     *          ...
     *      ],
     *     'blog' => [
     *        [
     *              'start' => '/blog/post-',
     *              'regex' => '/blog/post-(\w+)',
     *              'methods' => 'GET',
     *              'handler' => 'handler',
     *              'option' => [...],
     *        ],
     *        ...
     *     ],
     *     ... ...
     * ]
     */
    protected $regularRoutes = [];

    /**
     * vague Routes - have dynamic arguments,but the first node is exists regex.
     * 第一节就包含了正则匹配，称之为无规律/模糊的动态路由
     * e.g '/{name}/profile' '/{some}/{some2}'
     * @var array
     * [
     *     // 使用 HTTP METHOD 作为 key进行分组
     *     'GET' => [
     *          [
     *              // 必定包含的字符串
     *              'include' => '/profile',
     *              'regex' => '/(\w+)/profile',
     *              'handler' => 'handler',
     *              'option' => [...],
     *          ],
     *          ...
     *     ],
     *     'POST' => [
     *          [
     *              'include' => null,
     *              'regex' => '/(\w+)/(\w+)',
     *              'handler' => 'handler',
     *              'option' => [...],
     *          ],
     *          ...
     *     ],
     *      ... ...
     * ]
     */
    protected $vagueRoutes = [];

    /**
     * There are last route caches
     * @see $staticRoutes
     * @var array[]
     */
    protected $routeCaches = [];

    /**
     * object creator.
     * @param array $config
     * @return self
     * @throws \LogicException
     */
    public static function make(array $config = [])
    {
        return new static($config);
    }

    /**
     * object constructor.
     * @param array $config
     * @throws \LogicException
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);

        $this->currentGroupPrefix = '';
        $this->currentGroupOption = [];

        // load routes
        if (($file = $this->config['routesFile']) && is_file($file)) {
            require $file;
        }
    }

    /**
     * @param array $config
     * @throws \LogicException
     */
    public function setConfig(array $config)
    {
        if ($this->initialized) {
            throw new \LogicException('Routing has been added, and configuration is not allowed!');
        }

        foreach ($config as $name => $value) {
            if (isset($this->config[$name])) {
                $this->config[$name] = $value;
            }
        }
    }

    /*******************************************************************************
     * route collection
     ******************************************************************************/

    /**
     * Defines a route callback and method
     * @param string $method
     * @param array $args
     * @return static
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    public function __call($method, array $args)
    {
        if (\in_array(strtoupper($method), self::SUPPORTED_METHODS, true)) {
            if (\count($args) < 2) {
                throw new \InvalidArgumentException("The method [$method] parameters is missing.");
            }

            return $this->map($method, ...$args);
        }

        throw new \InvalidArgumentException("The method [$method] not exists in the class.");
    }

    /**
     * Create a route group with a common prefix.
     * All routes created in the passed callback will have the given group prefix prepended.
     * @ref package 'nikic/fast-route'
     * @param string $prefix
     * @param \Closure $callback
     * @param array $opts
     */
    public function group($prefix, \Closure $callback, array $opts = [])
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix = $previousGroupPrefix . '/' . trim($prefix, '/');

        $previousGroupOption = $this->currentGroupOption;
        $this->currentGroupOption = $opts;

        $callback($this);

        $this->currentGroupPrefix = $previousGroupPrefix;
        $this->currentGroupOption = $previousGroupOption;
    }

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
        // if (strpos($tmp, '.')) {
        //     return strstr($tmp, '.', true);
        // }

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
        $bak = $route;
        // $noOptional = null;
        // $hasOptional = false;

        // 解析可选参数位
        // '/hello[/{name}]'      match: /hello/tom   /hello
        // '/my[/{name}[/{age}]]' match: /my/tom/78  /my/tom
        if (false !== ($pos = strpos($route, '['))) {
            // $hasOptional = true;
            // $noOptional = substr($route, 0, $pos);
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
        // if (preg_match('#^/([\w-]+)#', $bak, $m)) {
        // if (preg_match('#^/([\w-]+)/?[\w-]*#', $bak, $m)) {
        if (preg_match('#^/([\w-]+)/[\w-]*/?#', $bak, $m)) {
            $first = $m[1];
            $info = [
                'regex'  => $regex,
                'start' => $m[0],
                'original' => $bak,
            ];
            // first node contain regex param '/{some}/{some2}/xyz'
        } else {
            $include = null;

            if (preg_match('#/([\w-]+)/?[\w-]*#', $bak, $m)) {
                $include = $m[0];
            }

            $info = [
                'regex' => $regex,
                'include' => $include,
                'original' => $bak,
            ];
        }

        return [$first, array_merge($info, $conf)];
    }

    /**
     * @param array $routesData
     * @param string $path
     * @param string $method
     * @return array
     */
    abstract protected function findInRegularRoutes(array $routesData, $path, $method);

    /**
     * @param array $routesData
     * @param string $path
     * @param string $method
     * @return array
     */
    abstract protected function findInVagueRoutes(array $routesData, $path, $method);

    /**
     * @param string $path
     * @param string $method
     * @param array $conf
     */
    abstract protected function cacheMatchedParamRoute($path, $method, array $conf);

    /**
     * handle auto route match, when config `'autoRoute' => true`
     * @param string $path The route path
     * @internal string $cnp controller namespace. eg: 'app\\controllers'
     * @internal string $sfx controller suffix. eg: 'Controller'
     * @return bool|callable
     */
    public function matchAutoRoute($path)
    {
        $cnp = trim($this->config['controllerNamespace']);
        $sfx = trim($this->config['controllerSuffix']);
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

    /**
     * @param array $staticRoutes
     */
    public function setStaticRoutes(array $staticRoutes)
    {
        $this->staticRoutes = $staticRoutes;
    }

    /**
     * @return array
     */
    public function getStaticRoutes()
    {
        return $this->staticRoutes;
    }

    /**
     * @param \array[] $regularRoutes
     */
    public function setRegularRoutes(array $regularRoutes)
    {
        $this->regularRoutes = $regularRoutes;
    }

    /**
     * @return \array[]
     */
    public function getRegularRoutes()
    {
        return $this->regularRoutes;
    }

    /**
     * @param array $vagueRoutes
     */
    public function setVagueRoutes($vagueRoutes)
    {
        $this->vagueRoutes = $vagueRoutes;
    }

    /**
     * @return array
     */
    public function getVagueRoutes()
    {
        return $this->vagueRoutes;
    }

    /**
     * @return array
     */
    public function getRouteCaches()
    {
        return $this->routeCaches;
    }
}
