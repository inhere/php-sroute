<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午8:03
 */

namespace Inhere\Route;

/**
 * Class ORouter - this is object version
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
class ORouter extends AbstractRouter
{
    /** @var int */
    private $routeCounter = 0;
    private $cacheCounter = 0;

    /** @var array global Options */
    private $globalOptions = [
        // 'domains' => [ 'localhost' ], // allowed domains
        // 'schemas' => [ 'http' ], // allowed schemas
        // 'time' => ['12'],
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
     * @var array[]
     * [
     *     '/user/login' => [
     *         // METHODS => [...] // 这里 key 和 value里的 'methods' 是一样的。仅是为了防止重复添加
     *         'GET,POST' => [
     *              'handler' => 'handler',
     *              'methods' => 'GET,POST',
     *              'option' => [...],
     *          ],
     *          'PUT' => [
     *              'handler' => 'handler',
     *              'methods' => 'PUT',
     *              'option' => [...],
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
     *     // 使用完整的第一节作为key进行分组
     *     'a' => [
     *          [
     *              'start' => '/a/',
     *              'regex' => '/a/(\w+)',
     *              'methods' => 'GET,POST',
     *              'handler' => 'handler',
     *              'option' => [...],
     *          ],
     *          ... ...
     *      ],
     *     'add' => [
     *          [
     *              'start' => '/add/',
     *              'regex' => '/add/(\w+)',
     *              'methods' => 'GET',
     *              'handler' => 'handler',
     *              'option' => [...],
     *          ],
     *          ... ...
     *      ],
     *     'blog' => [
     *        [
     *              'start' => '/blog/post-',
     *              'regex' => '/blog/post-(\w+)',
     *              'methods' => 'GET',
     *              'handler' => 'handler',
     *              'option' => [...],
     *        ],
     *        ... ...
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
     *         'methods' => 'GET',
     *         'handler' => 'handler',
     *         'option' => [...],
     *     ],
     *     [
     *         'include' => null,
     *         'regex' => '/(\w+)/(\w+)',
     *         'methods' => 'GET,POST',
     *         'handler' => 'handler',
     *         'option' => [...],
     *     ],
     *      ... ...
     * ]
     */
    private $vagueRoutes = [];

    /**
     * There are last route caches
     * @see $staticRoutes
     * @var array[]
     */
    private $routeCaches = [];

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
     * @return ORouter
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    public function __call($method, array $args)
    {
        if (\count($args) < 2) {
            throw new \InvalidArgumentException("The method [$method] parameters is missing.");
        }

        return $this->map($method, $args[0], $args[1], isset($args[2]) ? $args[2] : []);
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
     * @param string|array $methods The match request method(s).
     * e.g
     *  string: 'get'
     *  array: ['get','post']
     * @param string $route The route path string. is allow empty string. eg: '/user/login'
     * @param callable|string $handler
     * @param array $opts some option data
     * [
     *     'params' => [ 'id' => '[0-9]+', ],
     *     'defaults' => [ 'id' => 10, ],
     *     'domains'  => [ 'a-domain.com', '*.b-domain.com'],
     *     'schemas' => ['https'],
     * ]
     * @return static
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    public function map($methods, $route, $handler, array $opts = [])
    {
        if (!$this->initialized) {
            $this->initialized = true;
        }

        $hasPrefix = (bool)$this->currentGroupPrefix;
        // validate and format arguments
        $methods = static::validateArguments($methods, $handler);

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
            'params' => null,
            // 'domains' => null,
        ], $this->currentGroupOption, $opts);
        $conf = [
            'methods' => $methods,
            'handler' => $handler,
            'option' => $opts,
        ];

        // no dynamic param params
        if (self::isNoDynamicParam($route)) {
            $this->staticRoutes[$route][$methods] = $conf;

            return $this;
        }

        // have dynamic param params

        // replace param name To pattern regex
        $params = static::getAvailableParams(self::$globalParams, $opts['params']);
        list($first, $conf) = static::parseParamRoute($route, $params, $conf);

        // route string have regular
        if ($first) {
            $this->regularRoutes[$first][] = $conf;
        } else {
            $this->vagueRoutes[] = $conf;
        }

        return $this;
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
    public function match($path, $method = self::GET)
    {
        // if enable 'matchAll'
        if ($matchAll = $this->config['matchAll']) {
            if (\is_string($matchAll) && $matchAll{0} === '/') {
                $path = $matchAll;
            } elseif (\is_callable($matchAll)) {
                return [self::FOUND, $path, [
                    'handler' => $matchAll,
                    'option' => [],
                ]];
            }
        }

        $path = self::formatUriPath($path, $this->config['ignoreLastSep']);
        $method = strtoupper($method);

        // find in route caches.
        if ($this->routeCaches && isset($this->routeCaches[$path])) {
            return self::findInStaticRoutes($this->routeCaches[$path], $path, $method);
        }

        // is a static route path
        if ($this->staticRoutes && isset($this->staticRoutes[$path])) {
            return self::findInStaticRoutes($this->staticRoutes[$path], $path, $method);
        }

        $first = self::getFirstFromPath($path);
        $founded = [];

        // is a regular dynamic route(the first node is 1th level index key).
        if (isset($this->regularRoutes[$first])) {
            foreach ($this->regularRoutes[$first] as $conf) {
                if (0 === strpos($path, $conf['start']) && preg_match($conf['regex'], $path, $matches)) {
                    $conf['matches'] = $matches;
                    $founded[] = $conf;
                    // return $this->checkMatched($path, $method, $conf);
                }
            }

            if ($founded) {
                return $this->findInPossibleParamRoutes($founded, $path, $method);
            }
        }

        // is a irregular dynamic route
        foreach ($this->vagueRoutes as $conf) {
            if ($conf['include'] && false === strpos($path, $conf['include'])) {
                continue;
            }

            if (preg_match($conf['regex'], $path, $matches)) {
                $conf['matches'] = $matches;
                $founded[] = $conf;
                // return $this->checkMatched($path, $method, $conf);
            }
        }

        if ($founded) {
            return $this->findInPossibleParamRoutes($founded, $path, $method);
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

    /*******************************************************************************
     * route callback handler dispatch
     ******************************************************************************/

    /**
     * Runs the callback for the given request
     * @param DispatcherInterface|array $dispatcher
     * @param null|string $path
     * @param null|string $method
     * @return mixed
     * @throws \Throwable
     */
    public function dispatch($dispatcher = null, $path = null, $method = null)
    {
        if ($dispatcher) {
            if ($dispatcher instanceof DispatcherInterface) {
                $this->dispatcher = $dispatcher;
            } elseif (\is_array($dispatcher)) {
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
     * @param array $routes
     * @param string $path
     * @param string $method
     * @return array
     */
    protected function findInPossibleParamRoutes(array $routes, $path, $method)
    {
        $methods = null;

        foreach ($routes as $conf) {
            if (false !== strpos($conf['methods'] . ',', $method . ',')) {
                $conf['matches'] = self::filterMatches($conf['matches'], $conf);

                $this->cacheMatchedParamRoute($path, $conf);

                return [self::FOUND, $path, $conf];
            }

            $methods .= $conf['methods'] . ',';
        }

        // method not allowed
        return [
            self::METHOD_NOT_ALLOWED,
            $path,
            array_unique(explode(',', trim($methods, ',')))
        ];
    }

    /**
     * @param string $path
     * @param array $conf
     */
    protected function cacheMatchedParamRoute($path, array $conf)
    {
        $methods = $conf['methods'];
        $cacheNumber = (int)$this->config['tmpCacheNumber'];

        // cache last $cacheNumber routes.
        if ($cacheNumber > 0) {
            if ($this->cacheCounter === $cacheNumber) {
                array_shift($this->routeCaches);
            }

            if (!isset($this->routeCaches[$path][$methods])) {
                $this->cacheCounter++;
                $this->routeCaches[$path][$methods] = $conf;
            }
        }
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->routeCounter;
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

    /**
     * @return array
     */
    public function getGlobalOptions(): array
    {
        return $this->globalOptions;
    }

    /**
     * @param array $globalOptions
     * @return $this
     */
    public function setGlobalOptions(array $globalOptions)
    {
        $this->globalOptions = $globalOptions;

        return $this;
    }
}
