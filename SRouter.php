<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午8:03
 */

namespace inhere\sroute;

/**
 * Class SRoute - this is static class version
 * @package inhere\sroute
 *
 * @method static get(string $route, mixed $handler, array $opts = [])
 * @method static post(string $route, mixed $handler, array $opts = [])
 * @method static put(string $route, mixed $handler, array $opts = [])
 * @method static delete(string $route, mixed $handler, array $opts = [])
 * @method static options(string $route, mixed $handler, array $opts = [])
 * @method static head(string $route, mixed $handler, array $opts = [])
 * @method static search(string $route, mixed $handler, array $opts = [])
 * @method static trace(string $route, mixed $handler, array $opts = [])
 * @method static any(string $route, mixed $handler, array $opts = [])
 */
class SRouter implements RouterInterface
{
    private static $routeCounter = 0;

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

    /**
     * supported Methods
     * @var array
     */
    private static $supportedMethods = [
        'ANY',
        'GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'HEAD', 'SEARCH', 'CONNECT', 'TRACE',
    ];

    /**
     * event handlers
     * @var array
     */
    private static $events = [];

    /** @var string  */
    private static $currentGroupPrefix = '';

    /** @var array  */
    private static $currentGroupOption = [];

    /** @var bool  */
    private static $initialized = false;

    /**
     * static Routes - no dynamic argument match
     * 整个路由 path 都是静态字符串
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
    private static $staticRoutes = [];

    /**
     * regular Routes - have dynamic arguments, but the first node is normal.
     * 第一节是个静态字符串，称之为有规律的动态路由。按第一节的信息进行存储
     *
     * @var array[]
     * [
     *     // 先用第一个字符作为 key，进行分组
     *     'a' => [
     *          // 第一节只有一个字符, 使用关键字'__NO__'为 key 进行分组
     *         '__NO__' => [
     *              [
     *                  'first' => '/a',
     *                  'regex' => '/a/(\w+)',
     *                  'method' => 'GET',
     *                  'handler' => 'handler',
     *                  'option' => null,
     *              ]
     *          ],
     *          // 第一节有多个字符, 使用第二个字符 为 key 进行分组
     *         'd' => [
     *              [
     *                  'first' => '/add',
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
     *                  'first' => '/blog',
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
    private static $regularRoutes = [];

    /**
     * vague Routes - have dynamic arguments,but the first node is exists regex.
     *
     * 第一节就包含了正则匹配，称之为无规律/模糊的动态路由
     *
     * @var array
     * [
     *     [
     *         'regex' => '/(\w+)/some',
     *         'method' => 'GET',
     *         'handler' => 'handler',
     *         'option' => null,
     *     ],
     *      ... ...
     * ]
     */
    private static $vagueRoutes = [];

    /**
     * There are last route caches
     * @var array
     * [
     *     'path' => [
     *         'GET' => [
     *              'method' => 'GET',
     *              'handler' => 'handler',
     *              'option' => null,
     *          ],
     *         'POST' => [
     *              'method' => 'POST',
     *              'handler' => 'handler',
     *              'option' => null,
     *          ],
     *         ... ...
     *     ]
     * ]
     */
    private static $routeCaches = [];

    /**
     * some setting for self
     * @var array
     */
    private static $config = [
        // Filter the `/favicon.ico` request.
        'filterFavicon' => false,
        // ignore last '/' char. If is True, will clear last '/'.
        'ignoreLastSep' => false,

        // 'tmpCacheNumber' => 100,
        'tmpCacheNumber' => 0,

        // match all request.
        // 1. If is a valid URI path, will match all request uri to the path.
        // 2. If is a closure, will match all request then call it
        // eg: '/site/maintenance' or `function () { echo 'System Maintaining ... ...'; }`
        'matchAll' => '',

        // auto route match @like yii framework
        'autoRoute' => [
            // If is True, will auto find the handler controller file.
            'enable' => false,
            // The default controllers namespace, is valid when `'enable' = true`
            'controllerNamespace' => '', // eg: 'app\\controllers'
            // controller suffix, is valid when `'enable' = true`
            'controllerSuffix' => '',    // eg: 'Controller'
        ],

        // default action method name
        'defaultAction' => 'index',

        // enable dynamic action.
        // e.g
        // if set True;
        //  SRoute::any('/demo/(\w+)', app\controllers\Demo::class);
        //  you access '/demo/test' will call 'app\controllers\Demo::test()'
        'dynamicAction' => false,

        // action executor. will auto call controller's executor method to run all action.
        // e.g: 'actionExecutor' => 'run'`
        //  SRoute::any('/demo/(:act)', app\controllers\Demo::class);
        //  you access `/demo/test` will call `app\controllers\Demo::run('test')`
        'actionExecutor' => '', // 'run'
    ];

    /**
     * @param array $config
     * @throws \LogicException
     */
    public static function config(array $config)
    {
        if (self::$initialized) {
            throw new \LogicException('Routing has been added, and configuration is not allowed!');
        }

        foreach ($config as $name => $value) {
            if ($name === 'autoRoute') {
                static::$config['autoRoute'] = array_merge(static::$config['autoRoute'], (array)$value);
            } elseif (isset(static::$config[$name])) {
                static::$config[$name] = $value;
            }
        }
    }

//////////////////////////////////////////////////////////////////////
/// route collection
//////////////////////////////////////////////////////////////////////

    /**
     * Defines a route callback and method
     * @param string $method
     * @param array $args
     * @throws \InvalidArgumentException
     */
    public static function __callStatic($method, array $args)
    {
        if (count($args) < 2) {
            throw new \InvalidArgumentException("The method [$method] parameters is required.");
        }

        self::map($method, $args[0], $args[1], isset($args[2]) ? $args[2] : null);
    }

    /**
     * Create a route group with a common prefix.
     * All routes created in the passed callback will have the given group prefix prepended.
     *
     * @from package 'nikic/fast-route'
     * @param string $prefix
     * @param \Closure $callback
     * @param array $opts
     */
    public static function group($prefix, \Closure $callback, array $opts = [])
    {
        $previousGroupPrefix = self::$currentGroupPrefix;
        self::$currentGroupPrefix = $previousGroupPrefix . $prefix;

        $previousGroupOption = self::$currentGroupOption;
        self::$currentGroupOption = $opts;

        $callback();

        self::$currentGroupPrefix = $previousGroupPrefix;
        self::$currentGroupOption = $previousGroupOption;
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
     *     'hosts'  => [ 'a-domain.com', '*.b-domain.com'],
     *     'schema' => 'https',
     * ]
     * @return true
     * @throws \InvalidArgumentException
     */
    public static function map($method, $route, $handler, array $opts = [])
    {
        if (!self::$initialized) {
            self::$initialized = true;
        }

        // array
        if (is_array($method)) {
            foreach ((array)$method as $m) {
                self::map($m, $route, $handler, $opts);
            }

            return true;
        }

        // string - register route and callback

        $method = strtoupper($method);

        // validate arguments
        self::validateArguments($method, $handler);

        if ($route = trim($route)) {
            // always add '/' prefix.
            $route = $route{0} === '/' ? $route : '/' . $route;

            // setting 'ignoreLastSep'
            if ($route !== '/' && self::$config['ignoreLastSep']) {
                $route = rtrim($route, '/');
            }
        } else {
            $route = '/';
        }

        self::$routeCounter++;
        $route = self::$currentGroupPrefix . $route;
        $opts = array_replace([
           'tokens' => null,
           'domains'  => null,
           'schema' => null, // ['http','https'],
            // route event
           'enter' => null,
           'leave' => null,
        ], self::$currentGroupOption, $opts);

        $conf = [
            'method' => $method,
            'handler' => $handler,
            'option' => $opts,
        ];

        // no dynamic param tokens
        if (strpos($route, '{') === false) {
            self::$staticRoutes[$route][$method] = $conf;

            return true;
        }

        // have dynamic param tokens

        $route = $tmp = self::replaceTokenToPattern($route, $opts);

        list($first,) = explode('/', trim($tmp, '/'), 2);

        // first node is a normal string '/user/:id', '/a/:post'
        if (preg_match('#^(?|[\w-]+)$#', $first)) {
            $conf = [
                'first' => '/' . $first,
                'regex' => '#^' . $route . '$#',
            ] + $conf;

            $twoLevelKey = isset($first{1}) ? $first{1} : '__NO__';
            self::$regularRoutes[$first{0}][$twoLevelKey][] = $conf;

            // first node contain regex param '/:some/:some2'
        } else {
            $conf['regex'] = '#^' . $route . '$#';
            self::$vagueRoutes[] = $conf;
        }

        return true;
    }

    /**
     * @param $method
     * @param $handler
     * @throws \InvalidArgumentException
     */
    private static function validateArguments($method, $handler)
    {
        $supStr = implode('|', self::$supportedMethods);

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
     * @param array $opts
     * @return string
     */
    private static function replaceTokenToPattern($route, array $opts)
    {
        /** @var array $tokens */
        $tokens = self::$globalTokens;

        if ($opts['tokens']) {
            foreach ((array)$opts['tokens'] as $name => $pattern) {
                $key = trim($name, '{}');
                $tokens[$key] = $pattern;
            }
        }

        if (preg_match_all('#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#', $route, $m)) {
            /** @var array[] $m */
            $replacePairs = [];

            foreach ($m[1] as $name) {
                $key = '{' . $name . '}';
                // 匹配定义的 token  , 未匹配到的使用默认 self::DEFAULT_REGEX
                $regex = isset($tokens[$name]) ? $tokens[$name] : self::DEFAULT_REGEX;

                // 将匹配结果命名 (?P<arg1>[^/]+)
                // $replacePairs[$key] = '(?P<' . $name . '>' . $pattern . ')';
                $replacePairs[$key] = '(' . $regex . ')';
            }

            $route = strtr($route, $replacePairs);
        }

        return $route;
    }

//////////////////////////////////////////////////////////////////////
/// route match
//////////////////////////////////////////////////////////////////////

    /**
     * find the matched route info for the given request uri path
     * @param string $method
     * @param string $path
     * @return mixed
     */
    public static function match($path, $method)
    {
        // if enable 'matchAll'
        if ($matchAll = static::$config['matchAll']) {
            if (is_string($matchAll) && $matchAll{0} === '/') {
                $path = $matchAll;
            } elseif (is_callable($matchAll)) {
                return [$path, $matchAll];
            }
        }

        // clear '//', '///' => '/'
        $path = rawurldecode(preg_replace('#\/\/+#', '/', $path));
        $method = strtoupper($method);
        $number = static::$config['tmpCacheNumber'];

        // find in class cache.
        if (self::$routeCaches && isset(self::$routeCaches[$path])) {
            if (isset(self::$routeCaches[$path][$method])) {
                return [$path, self::$routeCaches[$path][$method]];
            }

            if (isset(self::$routeCaches[$path][self::MATCH_ANY])) {
                return [$path, self::$routeCaches[$path][self::MATCH_ANY]];
            }
        }

        // is a static path route
        if (self::$staticRoutes && isset(self::$staticRoutes[$path])) {
            if (isset(self::$staticRoutes[$path][$method])) {
                return [$path, self::$staticRoutes[$path][$method]];
            }

            if (isset(self::$staticRoutes[$path][self::MATCH_ANY])) {
                return [$path, self::$staticRoutes[$path][self::MATCH_ANY]];
            }
        }

        $tmp = trim($path, '/'); // clear first '/'

        // is a regular dynamic route(the first char is 1th level index key).
        if (self::$regularRoutes && isset(self::$regularRoutes[$tmp{0}])) {
            $twoLevelArr = self::$regularRoutes[$tmp{0}];
            $twoLevelKey = isset($tmp{1}) ? $tmp{1} : '__NO__';

            // not found
            if (!isset($twoLevelArr[$twoLevelKey])) {
                return false;
            }

            foreach ((array)$twoLevelArr[$twoLevelKey] as $conf) {
                if (0 === strpos($path, $conf['first']) && preg_match($conf['regex'], $path, $matches)) {
                    // method not allowed
                    if ($method !== $conf['method'] && self::MATCH_ANY !== $conf['method']) {
                        return false;
                    }

                    $conf['matches'] = $matches;

                    // cache latest $number routes.
                    if ($number > 0) {
                        if (count(self::$routeCaches) === $number) {
                            array_shift(self::$routeCaches);
                        }

                        self::$routeCaches[$path][$conf['method']] = $conf;
                    }

                    return [$path, $conf];
                }
            }
        }

        // is a irregular dynamic route
        foreach (self::$vagueRoutes as $conf) {
            if (preg_match($conf['regex'], $path, $matches)) {
                // method not allowed
                if ($method !== $conf['method'] && self::MATCH_ANY !== $conf['method']) {
                    return false;
                }

                $conf['matches'] = $matches;

                // cache last $number routes.
                if ($number > 0) {
                    if (count(self::$routeCaches) === $number) {
                        array_shift(self::$routeCaches);
                    }

                    self::$routeCaches[$path][$conf['method']] = $conf;
                }

                return [$path, $conf];
            }
        }

        // handle Auto Route
        if ($handler = self::matchAutoRoute($path)) {
            return [$path, [
                'handler' => $handler
            ]];
        }

        // oo ... not found
        return false;
    }

    /**
     * handle Auto Route
     *  when config `'autoRoute' => true`
     * @param string $path The route path
     * @return bool|callable
     */
    private static function matchAutoRoute($path)
    {
        /**
         * @var array $opts
         * contains: [
         *  'controllerNamespace' => '', // controller namespace. eg: 'app\\controllers'
         *  'controllerSuffix' => '',    // controller suffix. eg: 'Controller'
         * ]
         */
        $opts = static::$config['autoRoute'];

        // not enabled
        if (!$opts || !isset($opts['enable']) || !$opts['enable']) {
            return false;
        }

        $cnp = $opts['controllerNamespace'];
        $sfx = $opts['controllerSuffix'];
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

//////////////////////////////////////////////////////////////////////
/// route callback handler dispatch
//////////////////////////////////////////////////////////////////////

    /**
     *
     * Runs the callback for the given request
     * @param null $path
     * @param null $method
     * @return mixed
     */
    public static function dispatch($path = null, $method = null)
    {
        $result = null;
        $path = $path ?: parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // if 'filterFavicon' setting is TRUE
        if ($path === self::MATCH_FAV_ICO && static::$config['filterFavicon']) {
            return $result;
        }

        $method = $method ?: $_SERVER['REQUEST_METHOD'];

        // handle Auto Route
        if ($data = self::match($path, $method)) {
            list($path, $conf) = $data;

            // trigger route found event
            self::fire(self::FOUND, [$path, $conf]);

            $handler = $conf['handler'];
            $matches = isset($conf['matches']) ? $conf['matches'] : null;

            try {
                // trigger route exec_start event
                self::fire(self::EXEC_START, [$path, $conf]);

                $result = self::callMatchedRouteHandler($path, $handler, $matches);

                // trigger route exec_end event
                self::fire(self::EXEC_END, [$path, $conf]);
            } catch (\Exception $e) {
                // trigger route exec_error event
                self::fire(self::EXEC_ERROR, [$e, $path, $conf]);
            }

            return $result;
        }

        return self::handleNotFound($path);
    }

    /**
     * manual dispatch a URI route
     * @param string $uri
     * @param string $method
     * @param bool $receiveReturn
     * @return null|string
     */
    public static function dispatchTo($uri, $method = 'GET', $receiveReturn = true)
    {
        $result = null;

        if ($receiveReturn) {
            ob_start();
            self::dispatch($uri, $method);
            $result = ob_get_clean();
        } else {
            $result = self::dispatch($uri, $method);
        }

        return $result;
    }

    /**
     * @param string $path Request uri path
     * @param bool $isActionNotExist
     *  True: The `$path` is matched success, but action not exist on route parser
     *  False: The `$path` is matched fail
     * @return bool|mixed
     */
    private static function handleNotFound($path, $isActionNotExist = false)
    {
        // Run the 'notFound' callback if the route was not found
        if (!isset(self::$events[self::NOT_FOUND])) {
            $notFoundHandler = function ($path, $isActionNotExist) {
                 header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
                 echo "<h1 style='width: 60%; margin: 5% auto;'>:( 404<br>Page Not Found <code style='font-weight: normal;'>$path</code></h1>";
            };

            self::on(self::NOT_FOUND, $notFoundHandler);
        } else {
            $notFoundHandler = self::$events[self::NOT_FOUND];

            // is a route path. like '/site/notFound'
            if (is_string($notFoundHandler) && '/' === $notFoundHandler{0}) {
                $_GET['_src_path'] = $path;

                unset(self::$events[self::NOT_FOUND]);
                return self::dispatch($path);
            }
        }

        // trigger notFound event
        return is_array($notFoundHandler) ?
            call_user_func($notFoundHandler, $path, $isActionNotExist) :
            $notFoundHandler($path, $isActionNotExist);
    }

    /**
     * the default matched route parser.
     * @param string $path The route path
     * @param callable $handler The route path handler
     * @param array $matches Matched param from path
     * @return mixed
     * @throws \RuntimeException
     */
    private static function callMatchedRouteHandler($path, $handler, array $matches = null)
    {
        // Remove $matches[0] as [1] is the first parameter.
        if ($matches) {
            array_shift($matches);
        }

        // is a \Closure or a callable object
        if (is_object($handler)) {
            return $matches ? $handler(...$matches) : $handler();
        }

        //// $handler is string

        // e.g `controllers\Home@index` Or only `controllers\Home`
        $segments = explode('@', trim($handler));

        // Instantiation controller
        $controller = new $segments[0]();

        // Already assign action
        if (isset($segments[1])) {
            $action = $segments[1];

            // use dynamic action
        } elseif ((bool)static::$config['dynamicAction']) {
            $action = isset($matches[0]) ? trim($matches[0], '/') : static::$config['defaultAction'];

            // defined default action
        } elseif (!$action = static::$config['defaultAction']) {
            throw new \RuntimeException("please config the route path [$path] controller action to call");
        }

        $action = self::convertNodeStr($action);

        // if set the 'actionExecutor', the action handle logic by it.
        if ($executor = static::$config['actionExecutor']) {
            return $controller->$executor($action, $matches);
        }

        // action method is not exist
        if (!$action || !method_exists($controller, $action)) {
            return self::handleNotFound($path, true);
        }

        // call controller's action method
        return $matches ? $controller->$action(...$matches) : $controller->$action();

    }

//////////////////////////////////////////////////////////////////////
/// helper methods
//////////////////////////////////////////////////////////////////////

    /**
     * @param array $tokens
     */
    public static function addTokens(array $tokens)
    {
        foreach ($tokens as $name => $pattern) {
            self::addToken($name, $pattern);
        }
    }

    /**
     * @param $name
     * @param $pattern
     */
    public static function addToken($name, $pattern)
    {
        $name = trim($name, '{} ');
        self::$globalTokens[$name] = $pattern;
    }

    /**
     * convert 'first-second' to 'firstSecond'
     * @param $str
     * @return mixed|string
     */
    protected static function convertNodeStr($str)
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
     * @return int
     */
    public static function count()
    {
        return self::$routeCounter;
    }

    /**
     * @return array
     */
    public static function getStaticRoutes()
    {
        return self::$staticRoutes;
    }

    /**
     * @return \array[]
     */
    public static function getRegularRoutes()
    {
        return self::$regularRoutes;
    }

    /**
     * @return array
     */
    public static function getVagueRoutes()
    {
        return self::$vagueRoutes;
    }

    /**
     * @return array
     */
    public static function getGlobalTokens()
    {
        return self::$globalTokens;
    }

    /**
     * @return array
     */
    public static function getSupportedMethods()
    {
        return self::$supportedMethods;
    }

    /**
     * @return array
     */
    public static function getConfig()
    {
        return static::$config;
    }

    /**
     * Defines callback on happen event
     * @param $event
     * @param callable $handler
     */
    public static function on($event, $handler)
    {
        if (self::isSupportedEvent($event)) {
            self::$events[$event] = $handler;
        }
    }

    /**
     * Trigger event
     * @param $event
     * @param array $args
     * @return mixed
     */
    protected static function fire($event, array $args = [])
    {
        if (isset(self::$events[$event]) && ($cb = self::$events[$event])) {
            return !is_array($cb) ? $cb(...$args) : call_user_func_array($cb, $args);
        }

        return null;
    }

    /**
     * @param $event
     * @return bool
     */
    public static function hasEventHandler($event)
    {
        return isset(self::$events[$event]);
    }

    /**
     * @return array
     */
    public static function getSupportedEvents()
    {
        return [self::FOUND, self::NOT_FOUND, self::EXEC_START, self::EXEC_END, self::EXEC_ERROR];
    }

    /**
     * @param $name
     * @return array
     */
    public static function isSupportedEvent($name)
    {
        return in_array($name, static::getSupportedEvents(), true);
    }
}
