<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午8:03
 */

namespace Inhere\Route;

use Inhere\Route\Dispatcher\Dispatcher;
use Inhere\Route\Dispatcher\DispatcherInterface;
use Inhere\Route\Helper\RouteHelper;

/**
 * Class Router - this is object version
 * @package Inhere\Route
 */
class Router extends AbstractRouter
{
    /** @var int */
    protected $routeCounter = 0;

    /** @var array global Options */
    private $globalOptions = [
        // 'domains' => [ 'localhost' ], // allowed domains
        // 'schemas' => [ 'http' ], // allowed schemas
        // 'time' => ['12'],
    ];

    /*******************************************************************************
     * route collection
     ******************************************************************************/

    /**
     * @param string $method
     * @param string $path
     * @param $handler
     * @param array $binds
     * @param array $opts
     * @return Route
     */
    public function add(string $method, string $path, $handler, array $binds = [], array $opts = []): Route
    {
        if (!$method || !$handler) {
            throw new \InvalidArgumentException('The method and route handler is not allow empty.');
        }

        if (false === \strpos(self::METHODS_STRING, ',' . $method . ',')) {
            throw new \InvalidArgumentException(
                "The method [$method] is not supported, Allow: " . \trim(self::METHODS_STRING, ',')
            );
        }

        list($path, $opts) = $this->prepareForAdd($path, $opts);

        // create Route
        $route = Route::create($method, $path, $handler, $binds, $opts);

        return $this->addRoute($route);
    }

    /**
     * @param Route $route
     * @return Route
     */
    public function addRoute(Route $route): Route
    {
        $this->routeCounter++;

        $path = $route->getPath();
        $method = $route->getMethod();

        // it is static route
        if (self::isStaticRoute($path)) {
            $this->staticRoutes[$method . ' ' . $path] = $route;

            return $route;
        }

        // parse param route
        $first = $route->parseParam($this->getAvailableParams($route->getBindVars()));

        // route string have regular
        if ($first) {
            $this->regularRoutes[$method . ' ' . $first][] = $route;
        } else {
            $this->vagueRoutes[$method][] = $route;
        }

        return $route;
    }

    /**
     * prepare for add
     * @param string $path
     * @param array $opts
     * @return array
     */
    protected function prepareForAdd(string $path, array $opts): array
    {
        if (!$this->initialized) {
            $this->initialized = true;
        }

        $hasPrefix = (bool)$this->currentGroupPrefix;

        // always add '/' prefix.
        if ($path = \trim($path)) {
            $path = \strpos($path, '/') === 0 ? $path : '/' . $path;
        } elseif (!$hasPrefix) {
            $path = '/';
        }

        $path = $this->currentGroupPrefix . $path;

        // setting 'ignoreLastSlash'
        if ($path !== '/' && $this->ignoreLastSlash) {
            $path = \rtrim($path, '/');
        }

        if ($this->currentGroupOption) {
            $opts = \array_merge($this->currentGroupOption, $opts);
        }

        return [$path, $opts];
    }

    /*******************************************************************************
     * route match
     ******************************************************************************/

    /**
     * find the matched route info for the given request uri path
     * @param string $method
     * @param string $path
     * @return array returns array.
     * [
     *  match status, // found, not found, method not allowed
     *  formatted path,
     *  (Route object) OR (methods list)
     * ]
     */
    public function match(string $path, string $method = 'GET'): array
    {
        $path = RouteHelper::formatPath($path, $this->ignoreLastSlash);
        $method = \strtoupper($method);
        $sKey = $method . ' ' . $path;

        // is a static route path
        if (isset($this->staticRoutes[$sKey])) {
            return [self::FOUND, $path, $this->staticRoutes[$sKey]];
        }

        // is a dynamic route, match by regexp
        $result = $this->matchDynamicRoute($path, $method);
        if ($result[0] === self::FOUND) {
            return $result;
        }

        // handle Auto Route. always return new Route object.
        if ($this->autoRoute && ($handler = $this->matchAutoRoute($path))) {
            return [self::FOUND, $path, Route::create($method, $path, $handler)];
        }

        // For HEAD requests, attempt fallback to GET
        if ($method === 'HEAD') {
            $sKey = 'GET ' . $path;
            if (isset($this->staticRoutes[$sKey])) {
                return [self::FOUND, $path, $this->staticRoutes[$sKey]];
            }

            $result = $this->matchDynamicRoute($path, 'GET');
            if ($result[0] === self::FOUND) {
                return $result;
            }
        }

        // If nothing else matches, try fallback routes. $router->any('*', 'handler');
        $sKey = $method . ' /*';
        if ($this->staticRoutes && isset($this->staticRoutes[$sKey])) {
            $info = $this->staticRoutes[$sKey]->info();
            return [self::FOUND, $path, $info];
        }

        if (!$this->handleMethodNotAllowed) {
            return [self::NOT_FOUND, $path, null];
        }

        // collect allowed methods from: staticRoutes, vagueRoutes OR return not found.
        return $this->findAllowedMethods($path, $method);
    }

    /*******************************************************************************
     * helper methods
     ******************************************************************************/

    /**
     * is a dynamic route, match by regexp
     * @param string $path
     * @param string $method
     * @return array
     * [
     *  status,
     *  path,
     *  Route
     * ]
     */
    protected function matchDynamicRoute(string $path, string $method): array
    {
        $fKey = $first = '';

        if ($pos = \strpos($path, '/', 1)) {
            $first = \substr($path, 1, $pos - 1);
            $fKey = $method . ' ' . $first;
        }

        // is a regular dynamic route(the first node is 1th level index key).
        if ($fKey && $routeList = $this->regularRoutes[$fKey] ?? false) {
            /** @var Route $route */
            foreach ($routeList as $route) {
                $result = $route->match($path);
                if ($result[0]) {
                    return [self::FOUND, $path, $route->copyWithParams($result[1])];
                }
            }
        }

        // is a irregular dynamic route
        if ($routeList = $this->vagueRoutes[$method] ?? false) {
            foreach ($routeList as $route) {
                $result = $route->match($path);
                if ($result[0]) {
                    return [self::FOUND, $path, $route->copyWithParams($result[1])];
                }
            }
        }

        return [self::NOT_FOUND];
    }

    /**
     * @param string $path
     * @param string $method
     * @return array
     */
    protected function findAllowedMethods(string $path, string $method): array
    {
        $allowedMethods = [];

        foreach (self::METHODS_ARRAY as $m) {
            if ($method === $m) {
                continue;
            }

            $sKey = $m . ' ' . $path;
            if (isset($this->staticRoutes[$sKey])) {
                $allowedMethods[] = $m;
            }

            $result = $this->matchDynamicRoute($path, $m);
            if ($result[0] === self::FOUND) {
                $allowedMethods[] = $m;
            }
        }

        if ($allowedMethods && ($list = \array_unique($allowedMethods))) {
            return [self::METHOD_NOT_ALLOWED, $path, $list];
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
     * @throws \LogicException
     * @throws \Throwable
     */
    public function dispatch($dispatcher = null, $path = null, $method = null)
    {
        if (!$dispatcher) {
            $dispatcher = new Dispatcher;
        } elseif (\is_array($dispatcher)) {
            $dispatcher = new Dispatcher($dispatcher);
        }

        if (!$dispatcher instanceof DispatcherInterface) {
            throw new \InvalidArgumentException(
                'The first argument is must an array OR an object instanceof the DispatcherInterface'
            );
        }

        if (!$dispatcher->getRouter()) {
            $dispatcher->setRouter($this);
        }

        return $dispatcher->dispatchUri($path, $method);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->routeCounter;
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
    public function setGlobalOptions(array $globalOptions): self
    {
        $this->globalOptions = $globalOptions;

        return $this;
    }
}
