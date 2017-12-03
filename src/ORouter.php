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
 * @method connect(string $route, mixed $handler, array $opts = [])
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
     *          // METHOD => [...] // 这里 key 和 value里的 'methods' 是一样的
     *          'GET' => [
     *              'handler' => 'handler',
     *              'methods' => 'GET',
     *              'option' => [...],
     *          ],
     *          'PUT' => [
     *              'handler' => 'handler',
     *              'methods' => 'PUT',
     *              'option' => [...],
     *          ],
     *          ...
     *      ],
     *      ... ...
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
    private $regularRoutes = [];

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
     *              'methods' => 'GET',
     *              'handler' => 'handler',
     *              'option' => [...],
     *          ],
     *          ...
     *     ],
     *     'POST' => [
     *          [
     *              'include' => null,
     *              'regex' => '/(\w+)/(\w+)',
     *              'methods' => 'POST',
     *              'handler' => 'handler',
     *              'option' => [...],
     *          ],
     *          ...
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
        $methods = static::validateArguments($methods, $handler);

        // always add '/' prefix.
        if ($route = trim($route)) {
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
        $opts = array_merge($this->currentGroupOption, $opts);
        $conf = [
            'handler' => $handler,
            'option' => $opts,
        ];

        // it is static route
        if (self::isStaticRoute($route)) {
            foreach (explode(',', $methods) as $method) {
                $this->staticRoutes[$route][$method] = $conf;
            }

            return $this;
        }

        $params = $this->getAvailableParams(isset($opts['params']) ? $opts['params'] : []);

        list($first, $conf) = $this->parseParamRoute($route, $params, $conf);

        // route string have regular
        if ($first) {
            $conf['methods'] = $methods;
            $this->regularRoutes[$first][] = $conf;
        } else {
            foreach (explode(',', $methods) as $method) {
                $this->vagueRoutes[$method][] = $conf;
            }
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
    public function match($path, $method = 'GET')
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

        $path = $this->formatUriPath($path, $this->config['ignoreLastSep']);
        $method = strtoupper($method);

        // find in route caches.
        if ($this->routeCaches && isset($this->routeCaches[$path][$method])) {
            return [self::FOUND, $path, $this->routeCaches[$path][$method]];
        }

        // is a static route path
        if ($this->staticRoutes && isset($this->staticRoutes[$path][$method])) {
            $conf = $this->staticRoutes[$path][$method];

            return [self::FOUND, $path, $conf];
        }

        $first = $this->getFirstFromPath($path);
        $allowedMethods = [];

        // is a regular dynamic route(the first node is 1th level index key).
        if (isset($this->regularRoutes[$first])) {
            $result = $this->findInRegularRoutes($this->regularRoutes[$first], $path, $method);

            if ($result[0] === self::FOUND) {
                return $result;
            }

            $allowedMethods = $result[1];
        }

        // is a irregular dynamic route
        if (isset($this->vagueRoutes[$method])) {
            $result = $this->findInVagueRoutes($this->vagueRoutes[$method], $path, $method);

            if ($result[0] === self::FOUND) {
                return $result;
            }
        }

        // handle Auto Route
        if (
            $this->config['autoRoute'] &&
            ($handler = $this->matchAutoRoute($path, $this->config['controllerNamespace'], $this->config['controllerSuffix']))
        ) {
            return [self::FOUND, $path, [
                'handler' => $handler,
                'option' => [],
            ]];
        }

        // For HEAD requests, attempt fallback to GET
        if ($method === self::HEAD) {
            if (isset($this->routeCaches[$path]['GET'])) {
                return [self::FOUND, $path, $this->routeCaches[$path]['GET']];
            }

            if (isset($this->staticRoutes[$path]['GET'])) {
                return [self::FOUND, $path, $this->staticRoutes[$path]['GET']];
            }

            if (isset($this->regularRoutes[$first])) {
                $result = $this->findInRegularRoutes($this->regularRoutes[$first], $path, 'GET');

                if ($result[0] === self::FOUND) {
                    return $result;
                }
            }

            if (isset($this->vagueRoutes['GET'])) {
                $result = $this->findInVagueRoutes($this->vagueRoutes['GET'], $path, 'GET');

                if ($result[0] === self::FOUND) {
                    return $result;
                }
            }
        }

        // If nothing else matches, try fallback routes
        if ($this->staticRoutes && isset($this->staticRoutes['*'][$method])) {
            return [self::FOUND, $path, $this->staticRoutes['*'][$method]];
        }

        // collect allowed methods from: staticRoutes, vagueRoutes

        if (isset($this->staticRoutes[$path])) {
            $allowedMethods = array_merge($allowedMethods, array_keys($this->staticRoutes[$path]));
        }

        foreach ($this->vagueRoutes as $m => $routes) {
            if ($method === $m) {
                continue;
            }

            $result = $this->findInVagueRoutes($this->vagueRoutes['GET'], $path, $m);

            if ($result[0] === self::FOUND) {
                $allowedMethods[] = $method;
            }
        }

        if ($allowedMethods) {
            return [
                self::METHOD_NOT_ALLOWED,
                $path,
                array_unique($allowedMethods)
            ];
        }

        // oo ... not found
        return [self::NOT_FOUND, $path, null];
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
    protected function findInRegularRoutes(array $routes, $path, $method)
    {
        $allowedMethods = '';

        foreach ($routes as $conf) {
            if (0 === strpos($path, $conf['start']) && preg_match($conf['regex'], $path, $matches)) {
                $allowedMethods .= $conf['methods'] . ',';

                if (false !== strpos($conf['methods'] . ',', $method . ',')) {
                    $conf['matches'] = self::filterMatches($matches, $conf);

                    $this->cacheMatchedParamRoute($path, $method, $conf);

                    return [self::FOUND, $path, $conf];
                }
            }
        }

        return [self::NOT_FOUND, explode(',', trim($allowedMethods, ','))];
    }

    /**
     * @param array $routes
     * @param string $path
     * @param string $method
     * @return array
     */
    protected function findInVagueRoutes(array $routes, $path, $method)
    {
        foreach ($routes as $conf) {
            if ($conf['include'] && false === strpos($path, $conf['include'])) {
                continue;
            }

            if (preg_match($conf['regex'], $path, $matches)) {
                $conf['matches'] = self::filterMatches($matches, $conf);

                $this->cacheMatchedParamRoute($path, $method, $conf);

                return [self::FOUND, $path, $conf];
            }
        }

        return [self::NOT_FOUND];
    }

    /**
     * @param string $path
     * @param string $method
     * @param array $conf
     */
    protected function cacheMatchedParamRoute($path, $method, array $conf)
    {
        $cacheNumber = (int)$this->config['tmpCacheNumber'];

        // cache last $cacheNumber routes.
        if ($cacheNumber > 0) {
            if ($this->cacheCounter === $cacheNumber) {
                array_shift($this->routeCaches);
            }

            if (!isset($this->routeCaches[$path][$method])) {
                $this->cacheCounter++;
                $this->routeCaches[$path][$method] = $conf;
            }
        }
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
