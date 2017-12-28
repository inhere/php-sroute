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

    /** @var DispatcherInterface */
    private $dispatcher;

    /*******************************************************************************
     * route collection
     ******************************************************************************/

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
        $methods = $this->validateArguments($methods, $handler);

        // always add '/' prefix.
        if ($route = trim($route)) {
            $route = $route{0} === '/' ? $route : '/' . $route;
        } elseif (!$hasPrefix) {
            $route = '/';
        }

        $route = $this->currentGroupPrefix . $route;

        // setting 'ignoreLastSlash'
        if ($route !== '/' && $this->ignoreLastSlash) {
            $route = rtrim($route, '/');
        }

        $conf = [
            'handler' => $handler,
        ];

        if ($opts = array_merge($this->currentGroupOption, $opts)) {
            $conf['option'] = $opts;
        }

        // it is static route
        if (self::isStaticRoute($route)) {
            foreach ($methods as $method) {
                $this->routeCounter++;
                $this->staticRoutes[$route][$method] = $conf;
            }

            return $this;
        }

        $conf['original'] = $route;
        $params = $this->getAvailableParams(isset($opts['params']) ? $opts['params'] : []);
        list($first, $conf) = $this->parseParamRoute($route, $params, $conf);

        // route string have regular
        if ($first) {
            $conf['methods'] = implode(',', $methods);
            $this->routeCounter++;
            $this->regularRoutes[$first][] = $conf;
        } else {
            foreach ($methods as $method) {
                $this->routeCounter++;
                $this->vagueRoutes[$method][] = $conf;
            }
        }

        return $this;
    }

    /**
     * quick register a group restful routes for the controller class.
     * ```php
     * $router->rest('/users', UserController::class);
     * ```
     * @param string $prefix eg '/users'
     * @param string $controllerClass
     * @param array $map You can append or change default map list.
     * [
     *      'index' => null, // set value is empty to delete.
     *      'list' => 'get', // add new route
     * ]
     * @param array $opts Common options
     * @return static
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    public function rest($prefix, $controllerClass, array $map = [], array $opts = [])
    {
        $map = array_merge([
            'index' => ['GET'],
            'create' => ['POST'],
            'view' => ['GET', '{id}', ['id' => '[1-9]\d*']],
            'update' => ['PUT', '{id}', ['id' => '[1-9]\d*']],
            'patch' => ['PATCH', '{id}', ['id' => '[1-9]\d*']],
            'delete' => ['DELETE', '{id}', ['id' => '[1-9]\d*']],
        ], $map);
        //$opts = array_merge([], $opts);

        foreach ($map as $action => $conf) {
            if (!$conf || !$action) {
                continue;
            }

            $route = $prefix;

            // '/users/{id}'
            if (isset($conf[1]) && ($subPath = trim($conf[1]))) {
                // allow define a abs route. '/user-other-info'. it's not prepend prefix.
                $route = $subPath[0] === '/' ? $subPath : $prefix . '/' . $subPath;
            }

            if (isset($conf[2])) {
                $opts['params'] = $conf[2];
            }

            $this->map($conf[0], $route, $controllerClass . '@' . $action, $opts);
        }

        return $this;
    }

    /**
     * quick register a group universal routes for the controller class.
     *
     * ```php
     * $router->rest('/users', UserController::class, [
     *      'index' => 'get',
     *      'create' => 'post',
     *      'update' => 'post',
     *      'delete' => 'delete',
     * ]);
     * ```
     *
     * @param string $prefix eg '/users'
     * @param string $controllerClass
     * @param array $map You can append or change default map list.
     * [
     *      'index' => null, // set value is empty to delete.
     *      'list' => 'get', // add new route
     * ]
     * @param array $opts Common options
     * @return static
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    public function ctrl($prefix, $controllerClass, array $map = [], array $opts = [])
    {
        foreach ($map as $action => $method) {
            if (!$method || !\is_string($action)) {
                continue;
            }

            if ($action) {
                $route = $prefix . '/' . $action;
            } else {
                $route = $prefix;
                $action = 'index';
            }

            $this->map($method, $route, $controllerClass . '@' . $action, $opts);
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
        if ($matchAll = $this->matchAll) {
            if (\is_string($matchAll) && $matchAll{0} === '/') {
                $path = $matchAll;
            } elseif (\is_callable($matchAll)) {
                return [self::FOUND, $path, [
                    'handler' => $matchAll,
                    'option' => [],
                ]];
            }
        }

        $path = $this->formatUriPath($path, $this->ignoreLastSlash);
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
        if ($this->autoRoute && ($handler = $this->matchAutoRoute($path))) {
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

        // If nothing else matches, try fallback routes. $router->any('*', 'handler');
        if ($this->staticRoutes && isset($this->staticRoutes['/*'][$method])) {
            return [self::FOUND, $path, $this->staticRoutes['/*'][$method]];
        }

        if ($this->notAllowedAsNotFound) {
            return [self::NOT_FOUND, $path, null];
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

        if ($allowedMethods && ($list = array_unique($allowedMethods))) {
            return [self::METHOD_NOT_ALLOWED, $path, $list];
        }

        // oo ... not found
        return [self::NOT_FOUND, $path, null];
    }

    /*******************************************************************************
     * helper methods
     ******************************************************************************/

    /**
     * @param array $routesData
     * @param string $path
     * @param string $method
     * @return array
     */
    protected function findInRegularRoutes(array $routesData, $path, $method)
    {
        $allowedMethods = '';

        foreach ($routesData as $conf) {
            if (0 === strpos($path, $conf['start']) && preg_match($conf['regex'], $path, $matches)) {
                $allowedMethods .= $conf['methods'] . ',';

                if (false !== strpos($conf['methods'] . ',', $method . ',')) {
                    $conf['matches'] = $this->filterMatches($matches, $conf);

                    $this->cacheMatchedParamRoute($path, $method, $conf);

                    return [self::FOUND, $path, $conf];
                }
            }
        }

        return [self::NOT_FOUND, explode(',', trim($allowedMethods, ','))];
    }

    /**
     * @param array $routesData
     * @param string $path
     * @param string $method
     * @return array
     */
    protected function findInVagueRoutes(array $routesData, $path, $method)
    {
        foreach ($routesData as $conf) {
            if ($conf['include'] && false === strpos($path, $conf['include'])) {
                continue;
            }

            if (preg_match($conf['regex'], $path, $matches)) {
                $conf['matches'] = $this->filterMatches($matches, $conf);

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
        $cacheNumber = (int)$this->tmpCacheNumber;

        // cache last $cacheNumber routes.
        if ($cacheNumber > 0 && !isset($this->routeCaches[$path][$method])) {
            if ($this->cacheCounter >= $cacheNumber) {
                array_shift($this->routeCaches);
            }

            $this->cacheCounter++;
            $this->routeCaches[$path][$method] = $conf;
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
    public function getGlobalOptions()
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
