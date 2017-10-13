<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午8:03
 */

namespace Inhere\Route;

/**
 * Class ORouter- this is object version
 * @package Inhere\Route
 * @method get(string $route, mixed $handler, array $opts = [])
 * @method post(string $route, mixed $handler, array $opts = [])
 * @method put(string $route, mixed $handler, array $opts = [])
 * @method delete(string $route, mixed $handler, array $opts = [])
 * @method options(string $route, mixed $handler, array $opts = [])
 * @method head(string $route, mixed $handler, array $opts = [])
 * @method search(string $route, mixed $handler, array $opts = [])
 * @method trace(string $route, mixed $handler, array $opts = [])
 * @method any(string $route, mixed $handler, array $opts = [])
 */
class ORouter implements RouterInterface
{
    /**
     * @var int
     */
    private $routeCounter = 0;

    /**
     * some available patterns regex
     * $router->get('/user/{num}', 'handler');
     * @var array
     */
    private static $globalTokens = [
        'any' => '[^/]+',   // match any except '/'
        'num' => '[0-9]+',  // match a number
        'act' => '[a-zA-Z][\w-]+', // match a action name
        'all' => '.*'
    ];

    /** @var string */
    private $currentGroupPrefix;

    /** @var array */
    private $currentGroupOption;

    /** @var bool */
    private $initialized = false;

    /**
     * static Routes - no dynamic argument match
     * 整个路由 path 都是静态字符串 e.g. '/user/login'
     * @var array
     * [
     *     '/user/login' => [
     *         'GET' => [
     *              'handler' => 'handler',
     *              'option' => null,
     *          ],
     *         'POST' => [
     *              'handler' => 'handler',
     *              'option' => null,
     *          ],
     *          ...
     *      ]
     * ]
     */
    private $staticRoutes = [];

    /**
     * regular Routes - have dynamic arguments, but the first node is normal string.
     * 第一节是个静态字符串，称之为有规律的动态路由。按第一节的信息进行分组存储
     * e.g '/hello[/{name}]' '/user/{id}'
     * @var array[]
     * [
     *     // 先用第一个字符作为 key，进行分组
     *     'a' => [
     *          // 第一节只有一个字符, 使用关键字'_NO_'为 key 进行分组
     *         '_NO_' => [
     *              [
     *                  'prefix' => '/a',
     *                  'regex' => '/a/(\w+)',
     *                  'method' => 'GET',
     *                  'handler' => 'handler',
     *                  'option' => null,
     *              ]
     *          ],
     *          // 第一节有多个字符, 使用第二个字符 为 key 进行分组
     *         'd' => [
     *              [
     *                  'prefix' => '/add/',
     *                  'regex' => '/add/(\w+)',
     *                  'method' => 'GET',
     *                  'handler' => 'handler',
     *                  'option' => null,
     *              ],
     *              ... ...
     *          ],
     *          ... ...
     *      ],
     *     'b' => [
     *        'l' => [
     *              [
     *                  'prefix' => '/blog/',
     *                  'regex' => '/blog/(\w+)',
     *                  'method' => 'GET',
     *                  'handler' => 'handler',
     *                  'option' => null,
     *              ],
     *              ... ...
     *          ],
     *          ... ...
     *     ],
     * ]
     */
    private $regularRoutes = [];

    /**
     * vague Routes - have dynamic arguments,but the first node is exists regex.
     * 第一节就包含了正则匹配，称之为无规律/模糊的动态路由
     * e.g '/{name}/profile' '/{some}/{some2}'
     * @var array
     * [
     *     [
     *         // 必定包含的字符串
     *         'include' => '/profile',
     *         'regex' => '/(\w+)/profile',
     *         'method' => 'GET',
     *         'handler' => 'handler',
     *         'option' => null,
     *     ],
     *     [
     *         'include' => null,
     *         'regex' => '/(\w+)/(\w+)',
     *         'method' => 'GET',
     *         'handler' => 'handler',
     *         'option' => null,
     *     ],
     *      ... ...
     * ]
     */
    private $vagueRoutes = [];

    /**
     * There are last route caches
     * @var array
     * [
     *     'path' => [
     *         'GET' => [
     *              'handler' => 'handler',
     *              'option' => null,
     *          ],
     *         'POST' => [
     *              'handler' => 'handler',
     *              'option' => null,
     *          ],
     *         ... ...
     *     ]
     * ]
     */
    private $routeCaches = [];

    /**
     * some setting for self
     * @var array
     */
    private $config = [
        // the routes php file.
        'routesFile' => null,

        // ignore last '/' char. If is True, will clear last '/'.
        'ignoreLastSep' => false,

        // 'tmpCacheNumber' => 100,
        'tmpCacheNumber' => 0,

        // intercept all request.
        // 1. If is a valid URI path, will intercept all request uri to the path.
        // 2. If is a closure, will intercept all request then call it
        // eg: '/site/maintenance' or `function () { echo 'System Maintaining ... ...'; }`
        'intercept' => '',

        // auto route match @like yii framework
        // If is True, will auto find the handler controller file.
        'autoRoute' => false,
        // The default controllers namespace, is valid when `'enable' = true`
        'controllerNamespace' => '', // eg: 'app\\controllers'
        // controller suffix, is valid when `'enable' = true`
        'controllerSuffix' => '',    // eg: 'Controller'
    ];

    /** @var DispatcherInterface */
    private $dispatcher;

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
        if ($file = $this->config['routesFile']) {
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
     * @return ORouter
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    public function __call($method, array $args)
    {
        if (count($args) < 2) {
            throw new \InvalidArgumentException("The method [$method] parameters is missing.");
        }

        return $this->map($method, $args[0], $args[1], isset($args[2]) ? $args[2] : []);
    }

    /**
     * Create a route group with a common prefix.
     * All routes created in the passed callback will have the given group prefix prepended.
     * @from package 'nikic/fast-route'
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
     * @param string|array $method The match request method.
     * e.g
     *  string: 'get'
     *  array: ['get','post']
     * @param string $route The route path string. eg: '/user/login'
     * @param callable|string $handler
     * @param array $opts some option data
     * [
     *     'tokens' => [ 'id' => '[0-9]+', ],
     *     'domains'  => [ 'a-domain.com', '*.b-domain.com'],
     *     'schemes' => ['https'],
     * ]
     * @return static
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    public function map($method, $route, $handler, array $opts = [])
    {
        if (!$this->initialized) {
            $this->initialized = true;
        }

        // array
        if (is_array($method)) {
            foreach ((array)$method as $m) {
                $this->map($m, $route, $handler, $opts);
            }

            return $this;
        }

        // string - register route and callback

        $method = strtoupper($method);
        $hasPrefix = (bool)$this->currentGroupPrefix;

        // validate arguments
        static::validateArguments($method, $handler);

        if ($route = trim($route)) {
            // always add '/' prefix.
            $route = $route{0} === '/' ? $route : '/' . $route;
        } elseif (!$hasPrefix) {
            $route = '/';
        }

        $route = $this->currentGroupPrefix . $route;

        // setting 'ignoreLastSep'
        if ($route !== '/' && $this->config['ignoreLastSep']) {
            $route = rtrim($route, '/');
        }

        $this->routeCounter++;
        $opts = array_replace([
            'tokens' => null,
            'domains' => null,
            'schemes' => null, // ['http','https'],
            // route event. custom design ...
            // 'enter' => null,
            // 'leave' => null,
        ], $this->currentGroupOption, $opts);

        $conf = [
            'method' => $method,
            'handler' => $handler,
            'option' => $opts,
        ];

        // no dynamic param tokens
        if (strpos($route, '{') === false) {
            $this->staticRoutes[$route][$method] = $conf;

            return $this;
        }

        // have dynamic param tokens

        // replace token name To pattern regex
        list($first, $conf) = static::parseParamRoute(
            $route,
            static::getAvailableTokens(self::$globalTokens, $opts['tokens']),
            $conf
        );

        // route string have regular
        if ($first) {
            $twoLevelKey = isset($first{1}) ? $first{1} : self::DEFAULT_TWO_LEVEL_KEY;
            $this->regularRoutes[$first{0}][$twoLevelKey][] = $conf;
        } else {
            $this->vagueRoutes[] = $conf;
        }

        return $this;
    }

    /**
     * @param $method
     * @param $handler
     * @throws \InvalidArgumentException
     */
    public static function validateArguments($method, $handler)
    {
        $supStr = implode('|', self::SUPPORTED_METHODS);

        if (false === strpos('|' . $supStr . '|', '|' . $method . '|')) {
            throw new \InvalidArgumentException("The method [$method] is not supported, Allow: $supStr");
        }

        if (!$handler || (!is_string($handler) && !is_object($handler))) {
            throw new \InvalidArgumentException('The route handler is not empty and type only allow: string,object');
        }

        if (is_object($handler) && !is_callable($handler)) {
            throw new \InvalidArgumentException('The route object handler must be is callable');
        }
    }

    /**
     * @param string $route
     * @param array $tokens
     * @param array $conf
     * @return array
     * @throws \LogicException
     */
    public static function parseParamRoute($route, array $tokens, array $conf)
    {
        $tmp = $route;

        // 解析可选参数位
        // '/hello[/{name}]'      match: /hello/tom   /hello
        // '/my[/{name}[/{age}]]' match: /my/tom/78  /my/tom
        if (false !== strpos($route, ']')) {
            $withoutClosingOptionals = rtrim($route, ']');
            $optionalNum = strlen($route) - strlen($withoutClosingOptionals);

            if ($optionalNum !== substr_count($withoutClosingOptionals, '[')) {
                throw new \LogicException('Optional segments can only occur at the end of a route');
            }

            // '/hello[/{name}]' -> '/hello(?:/{name})?'
            $route = str_replace(['[', ']'], ['(?:', ')?'], $route);
        }

        // 解析参数，替换为对应的 正则
        if (preg_match_all('#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#', $route, $m)) {
            /** @var array[] $m */
            $replacePairs = [];

            foreach ($m[1] as $name) {
                $key = '{' . $name . '}';
                // 匹配定义的 token  , 未匹配到的使用默认 self::DEFAULT_REGEX
                $regex = isset($tokens[$name]) ? $tokens[$name] : self::DEFAULT_REGEX;

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
        if (preg_match('#^/([\w-]+)/?[\w-]*#', $tmp, $m)) {
            $first = $m[1];
            $info = [
                'regex'  => $regex,
                'prefix' => $m[0],
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
            ];
        }

        return [$first, array_merge($info, $conf)];
    }

    /**
     * @param array $tokens
     * @param array $tmpTokens
     * @return array
     */
    public static function getAvailableTokens(array $tokens, $tmpTokens)
    {
        if ($tmpTokens) {
            foreach ($tmpTokens as $name => $pattern) {
                $key = trim($name, '{}');
                $tokens[$key] = $pattern;
            }
        }

        return $tokens;
    }

    /*******************************************************************************
     * route match
     ******************************************************************************/

    /**
     * find the matched route info for the given request uri path
     * @param string $method
     * @param string $path
     * @return array
     */
    public function match($path, $method)
    {
        // if enable 'intercept'
        if ($intercept = $this->config['intercept']) {
            if (is_string($intercept) && $intercept{0} === '/') {
                $path = $intercept;
            } elseif (is_callable($intercept)) {
                return [self::FOUND, $path, [
                    'handler' => $intercept,
                    'option' => [],
                ]];
            }
        }

        // clear '//', '///' => '/'
        $path = rawurldecode(preg_replace('#\/\/+#', '/', $path));
        $method = strtoupper($method);
        $number = $this->config['tmpCacheNumber'];

        // setting 'ignoreLastSep'
        if ($path !== '/' && $this->config['ignoreLastSep']) {
            $path = rtrim($path, '/');
        }

        // find in route caches.
        if ($this->routeCaches && isset($this->routeCaches[$path])) {
            if (isset($this->routeCaches[$path][$method])) {
                return [self::FOUND, $path, $this->routeCaches[$path][$method]];
            }

            if (isset($this->routeCaches[$path][self::ANY_METHOD])) {
                return [self::FOUND, $path, $this->routeCaches[$path][self::ANY_METHOD]];
            }

            // method not allowed
            return [self::METHOD_NOT_ALLOWED, $path, $this->routeCaches[$path]];
        }

        // is a static route path
        if ($this->staticRoutes && isset($this->staticRoutes[$path])) {
            if (isset($this->staticRoutes[$path][$method])) {
                return [self::FOUND, $path, $this->staticRoutes[$path][$method]];
            }

            if (isset($this->staticRoutes[$path][self::ANY_METHOD])) {
                return [self::FOUND, $path, $this->staticRoutes[$path][self::ANY_METHOD]];
            }

            // method not allowed
            return [self::METHOD_NOT_ALLOWED, $path, $this->staticRoutes[$path]];
        }

        $tmp = trim($path, '/'); // clear first '/'

        // is a regular dynamic route(the first char is 1th level index key).
        if ($this->regularRoutes && isset($this->regularRoutes[$tmp{0}])) {
            /** @var array[] $twoLevelArr */
            $twoLevelArr = $this->regularRoutes[$tmp{0}];
            $twoLevelKey = isset($tmp{1}) ? $tmp{1} : self::DEFAULT_TWO_LEVEL_KEY;

            if (isset($twoLevelArr[$twoLevelKey])) {
                foreach ($twoLevelArr[$twoLevelKey] as $conf) {
                    if (0 === strpos($path, $conf['prefix']) && preg_match($conf['regex'], $path, $matches)) {
                        // method not allowed
                        if ($method !== $conf['method'] && self::ANY_METHOD !== $conf['method']) {
                            return [self::METHOD_NOT_ALLOWED, $path, $conf];
                        }

                        // first node is $path
                        $conf['matches'] = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                        // cache latest $number routes.
                        if ($number > 0) {
                            if (count($this->routeCaches) === $number) {
                                array_shift($this->routeCaches);
                            }

                            $this->routeCaches[$path][$conf['method']] = $conf;
                        }

                        return [self::FOUND, $path, $conf];
                    }
                }
            }
        }

        // is a irregular dynamic route
        foreach ($this->vagueRoutes as $conf) {
            if (
                (!$conf['include'] || strpos($path, $conf['include']) > 0) &&
                preg_match($conf['regex'], $path, $matches)
            ) {
                // method not allowed
                if ($method !== $conf['method'] && self::ANY_METHOD !== $conf['method']) {
                    return [self::METHOD_NOT_ALLOWED, $path, $conf];
                }

                // first node is $path
                // array_shift($matches);
                $conf['matches'] = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // cache last $number routes.
                if ($number > 0) {
                    if (count($this->routeCaches) === $number) {
                        array_shift($this->routeCaches);
                    }

                    $this->routeCaches[$path][$conf['method']] = $conf;
                }

                return [self::FOUND, $path, $conf];
            }
        }

        // handle Auto Route
        if (
            $this->config['autoRoute'] &&
            ($handler = self::matchAutoRoute($path, $this->config['controllerNamespace'], $this->config['controllerSuffix']))
        ) {
            return [self::FOUND, $path, [
                'handler' => $handler,
                'option' => [],
            ]];
        }

        // oo ... not found
        return [self::NOT_FOUND, $path, null];
    }

    /**
     * handle auto route match, when config `'autoRoute' => true`
     * @param string $path The route path
     * @param string $controllerNamespace controller namespace. eg: 'app\\controllers'
     * @param string $controllerSuffix controller suffix. eg: 'Controller'
     * @return bool|callable
     */
    public static function matchAutoRoute($path, $controllerNamespace, $controllerSuffix = '')
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
        $cnt = count($ary);

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

    /*******************************************************************************
     * route callback handler dispatch
     ******************************************************************************/

    /**
     * Runs the callback for the given request
     * @param DispatcherInterface|array $dispatcher
     * @param null|string $path
     * @param null|string $method
     * @return mixed
     */
    public function dispatch($dispatcher = null, $path = null, $method = null)
    {
        if ($dispatcher) {
            if ($dispatcher instanceof DispatcherInterface) {
                $this->dispatcher = $dispatcher;
            } elseif (is_array($dispatcher)) {
                $this->dispatcher = new Dispatcher($dispatcher);
            }
        }

        if (!$this->dispatcher) {
            $this->dispatcher = new Dispatcher;
        }

        return $this->dispatcher->setMatcher(function ($p, $m) {
            return $this->match($p, $m);
        })->dispatch($path, $method);
    }

    /*******************************************************************************
     * helper methods
     ******************************************************************************/

    /**
     * @param array $tokens
     */
    public function addTokens(array $tokens)
    {
        foreach ($tokens as $name => $pattern) {
            $this->addToken($name, $pattern);
        }
    }

    /**
     * @param $name
     * @param $pattern
     */
    public function addToken($name, $pattern)
    {
        $name = trim($name, '{} ');
        self::$globalTokens[$name] = $pattern;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->routeCounter;
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

    /**
     * @return array
     */
    public function getGlobalTokens()
    {
        return self::$globalTokens;
    }

    /**
     * @return array
     */
    public static function getSupportedMethods()
    {
        return self::SUPPORTED_METHODS;
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
     * @return DispatcherInterface
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @param DispatcherInterface $dispatcher
     * @return $this
     */
    public function setDispatcher(DispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }
}
