<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午8:03
 */

namespace Inhere\Route;

/**
 * Class SRoute - this is static class version
 * @package Inhere\Route
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

    /** @var string  */
    private static $currentGroupPrefix = '';

    /** @var array  */
    private static $currentGroupOption = [];

    /** @var bool  */
    private static $initialized = false;

    /**
     * static Routes - no dynamic argument match
     * 整个路由 path 都是静态字符串 e.g. '/user/login'
     * @var array
     * @see ORouter::$staticRoutes
     */
    private static $staticRoutes = [];

    /**
     * regular Routes - have dynamic arguments, but the first node is normal.
     * 第一节是个静态字符串，称之为有规律的动态路由。按第一节的信息进行存储
     * @var array[]
     * @see ORouter::$regularRoutes
     */
    private static $regularRoutes = [];

    /**
     * vague Routes - have dynamic arguments,but the first node is exists regex.
     * 第一节就包含了正则匹配，称之为无规律/模糊的动态路由
     * @var array
     * @see ORouter::$vagueRoutes
     */
    private static $vagueRoutes = [];

    /**
     * There are last route caches
     * @var array
     * @see ORouter::$routeCaches
     */
    private static $routeCaches = [];

    /**
     * some setting for self
     * @var array
     */
    private static $config = [
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
    private static $dispatcher;

    /**
     * @param array $config
     * @throws \LogicException
     */
    public static function setConfig(array $config)
    {
        if (self::$initialized) {
            throw new \LogicException('Routing has been added, and configuration is not allowed!');
        }

        foreach ($config as $name => $value) {
            if (isset(self::$config[$name])) {
                self::$config[$name] = $value;
            }
        }

        // load routes
        if (isset($config['routesFile']) && ($file = $config['routesFile'])) {
            require $file;
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
     * @throws \LogicException
     */
    public static function __callStatic($method, array $args)
    {
        if (count($args) < 2) {
            throw new \InvalidArgumentException("The method [$method] parameters is required.");
        }

        self::map($method, $args[0], $args[1], isset($args[2]) ? $args[2] : []);
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
     * @throws \LogicException
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
        ORouter::validateArguments($method, $handler);

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

        // replace token name To pattern regex
        list($first, $conf) = ORouter::parseRoute(
            $route,
            ORouter::getAvailableTokens(self::$globalTokens, $opts['tokens']),
            $conf
        );

        // route string is regular
        if ($first) {
            $twoLevelKey = isset($first{1}) ? $first{1} : self::DEFAULT_TWO_LEVEL_KEY;
            self::$regularRoutes[$first{0}][$twoLevelKey][] = $conf;
        } else {
            self::$vagueRoutes[] = $conf;
        }

        return true;
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
        // if enable 'intercept'
        if ($intercept = static::$config['intercept']) {
            if (is_string($intercept) && $intercept{0} === '/') {
                $path = $intercept;
            } elseif (is_callable($intercept)) {
                return [$path, $intercept];
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

            if (isset(self::$routeCaches[$path][self::ANY_METHOD])) {
                return [$path, self::$routeCaches[$path][self::ANY_METHOD]];
            }
        }

        // is a static path route
        if (self::$staticRoutes && isset(self::$staticRoutes[$path])) {
            if (isset(self::$staticRoutes[$path][$method])) {
                return [$path, self::$staticRoutes[$path][$method]];
            }

            if (isset(self::$staticRoutes[$path][self::ANY_METHOD])) {
                return [$path, self::$staticRoutes[$path][self::ANY_METHOD]];
            }
        }

        $tmp = trim($path, '/'); // clear first '/'

        // is a regular dynamic route(the first char is 1th level index key).
        if (self::$regularRoutes && isset(self::$regularRoutes[$tmp{0}])) {
            $twoLevelArr = self::$regularRoutes[$tmp{0}];
            $twoLevelKey = isset($tmp{1}) ? $tmp{1} : self::DEFAULT_TWO_LEVEL_KEY;

            // not found
            if (!isset($twoLevelArr[$twoLevelKey])) {
                return false;
            }

            foreach ((array)$twoLevelArr[$twoLevelKey] as $conf) {
                if (0 === strpos($path, $conf['first']) && preg_match($conf['regex'], $path, $matches)) {
                    // method not allowed
                    if ($method !== $conf['method'] && self::ANY_METHOD !== $conf['method']) {
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
                if ($method !== $conf['method'] && self::ANY_METHOD !== $conf['method']) {
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
        if (
            self::$config['autoRoute'] &&
            ($handler = ORouter::matchAutoRoute($path, self::$config['controllerNamespace'], self::$config['controllerSuffix']))
        ) {
            return [$path, [
                'handler' => $handler
            ]];
        }

        // oo ... not found
        return false;
    }

//////////////////////////////////////////////////////////////////////
/// route callback handler dispatch
//////////////////////////////////////////////////////////////////////

    /**
     * Runs the callback for the given request
     * @param DispatcherInterface|array $dispatcher
     * @param null|string $path
     * @param null|string $method
     * @return mixed
     */
    public static function dispatch($dispatcher = null, $path = null, $method = null)
    {
        if ($dispatcher) {
            if ($dispatcher instanceof DispatcherInterface) {
                self::$dispatcher = $dispatcher;
            } elseif (is_array($dispatcher)) {
                self::$dispatcher = new Dispatcher($dispatcher);
            }
        }

        if (!self::$dispatcher) {
            self::$dispatcher = new Dispatcher;
        }

        return self::$dispatcher->setMatcher(function ($p, $m) {
            return self::match($p, $m);
        })->dispatch($path, $method);
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
        return self::SUPPORTED_METHODS;
    }

    /**
     * @return array
     */
    public static function getConfig()
    {
        return static::$config;
    }

    /**
     * @return DispatcherInterface
     */
    public static function getDispatcher()
    {
        return self::$dispatcher;
    }

    /**
     * @param DispatcherInterface $dispatcher
     */
    public static function setDispatcher(DispatcherInterface $dispatcher)
    {
        self::$dispatcher = $dispatcher;
    }
}
