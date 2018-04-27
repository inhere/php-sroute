<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午8:03
 */

namespace Inhere\Route;

use Inhere\Route\Base\AbstractRouter;
use Inhere\Route\Dispatcher\Dispatcher;
use Inhere\Route\Dispatcher\DispatcherInterface;
use Inhere\Route\Helper\RouteHelper;

/**
 * Class ORouter - this is object version
 * @package Inhere\Route
 */
class ORouter extends AbstractRouter
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
    public function map($methods, string $route, $handler, array $opts = []): AbstractRouter
    {
        $methods = $this->validateArguments($methods, $handler);
        list($route, $conf) = $this->prepareForMap($route, $handler, $opts);

        // it is static route
        if (self::isStaticRoute($route)) {
            foreach ($methods as $method) {
                if ($method === 'ANY') {
                    continue;
                }

                $this->routeCounter++;
                $this->staticRoutes[$route][$method] = $conf;
            }

            return $this;
        }

        // collect param route
        $this->collectParamRoute($route, $methods, $conf, $opts['params'] ?? []);

        return $this;
    }

    /**
     * @param string $route
     * @param mixed $handler
     * @param array $opts
     * @return array
     */
    protected function prepareForMap(string $route, $handler, array $opts): array
    {
        if (!$this->initialized) {
            $this->initialized = true;
        }

        $hasPrefix = (bool)$this->currentGroupPrefix;

        // always add '/' prefix.
        if ($route = \trim($route)) {
            $route = $route{0} === '/' ? $route : '/' . $route;
        } elseif (!$hasPrefix) {
            $route = '/';
        }

        $route = $this->currentGroupPrefix . $route;

        // setting 'ignoreLastSlash'
        if ($route !== '/' && $this->ignoreLastSlash) {
            $route = \rtrim($route, '/');
        }

        $conf = [
            'handler' => $handler,
        ];

        if ($this->currentGroupOption) {
            $opts = \array_merge($this->currentGroupOption, $opts);
        }

        if ($opts) {
            $conf['option'] = $opts;
        }

        return [$route, $conf];
    }

    /**
     * @param string $route
     * @param array $methods
     * @param array $conf
     * @param array $params
     * @throws \LogicException
     */
    protected function collectParamRoute(string $route, array $methods, array $conf, array $params)
    {
        $conf['original'] = $route;
        $params = $this->getAvailableParams($params);
        list($first, $conf) = $this->parseParamRoute($route, $params, $conf);

        // route string have regular
        if ($first) {
            $conf['methods'] = \implode(',', $methods) . ',';
            $this->routeCounter++;
            $this->regularRoutes[$first][] = $conf;

            return;
        }

        foreach ($methods as $method) {
            if ($method === 'ANY') {
                continue;
            }

            $this->routeCounter++;
            $this->vagueRoutes[$method][] = $conf;
        }
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
    public function match(string $path, string $method = 'GET'): array
    {
        // if enable 'matchAll'
        if ($matchAll = $this->matchAll) {
            if (\is_string($matchAll) && $matchAll{0} === '/') {
                $path = $matchAll;
            } elseif (\is_callable($matchAll)) {
                return [self::FOUND, $path, [
                    'handler' => $matchAll,
                ]];
            }
        }

        $path = RouteHelper::formatUriPath($path, $this->ignoreLastSlash);
        $method = \strtoupper($method);

        // is a static route path
        if ($this->staticRoutes && isset($this->staticRoutes[$path][$method])) {
            $conf = $this->staticRoutes[$path][$method];

            return [self::FOUND, $path, $conf];
        }

        $first = null;
        $allowedMethods = [];

        // eg '/article/12'
        if ($pos = \strpos($path, '/', 1)) {
            $first = \substr($path, 1, $pos - 1);
        }

        // is a regular dynamic route(the first node is 1th level index key).
        if ($first && isset($this->regularRoutes[$first])) {
            $result = $this->findInRegularRoutes($first, $path, $method);

            if ($result[0] === self::FOUND) {
                return $result;
            }

            $allowedMethods = $result[1];
        }

        // is a irregular dynamic route
        if ($result = $this->findInVagueRoutes($path, $method)) {
            return $result;
        }

        // handle Auto Route
        if ($this->autoRoute && ($handler = $this->matchAutoRoute($path))) {
            return [self::FOUND, $path, [
                'handler' => $handler,
            ]];
        }

        // For HEAD requests, attempt fallback to GET
        if ($method === 'HEAD') {
            if (isset($this->staticRoutes[$path]['GET'])) {
                return [self::FOUND, $path, $this->staticRoutes[$path]['GET']];
            }

            if ($first && isset($this->regularRoutes[$first])) {
                $result = $this->findInRegularRoutes($first, $path, 'GET');

                if ($result[0] === self::FOUND) {
                    return $result;
                }
            }

            if ($result = $this->findInVagueRoutes($path, 'GET')) {
                return $result;
            }
        }

        // If nothing else matches, try fallback routes. $router->any('*', 'handler');
        if ($this->staticRoutes && isset($this->staticRoutes['/*'][$method])) {
            return [self::FOUND, $path, $this->staticRoutes['/*'][$method]];
        }

        if ($this->notAllowedAsNotFound) {
            return [self::NOT_FOUND, $path, null];
        }

        // collect allowed methods from: staticRoutes, vagueRoutes OR return not found.
        return $this->findAllowedMethods($path, $method, $allowedMethods);
    }

    /*******************************************************************************
     * helper methods
     ******************************************************************************/

    /**
     * @param string $path
     * @param string $method
     * @param array $allowedMethods
     * @return array
     */
    protected function findAllowedMethods(string $path, string $method, array $allowedMethods): array
    {
        if (isset($this->staticRoutes[$path])) {
            $allowedMethods = \array_merge($allowedMethods, \array_keys($this->staticRoutes[$path]));
        }

        foreach ($this->vagueRoutes as $m => $routes) {
            if ($method === $m) {
                continue;
            }

            if ($this->findInVagueRoutes($path, $m)) {
                $allowedMethods[] = $method;
            }
        }

        if ($allowedMethods && ($list = \array_unique($allowedMethods))) {
            return [self::METHOD_NOT_ALLOWED, $path, $list];
        }

        // oo ... not found
        return [self::NOT_FOUND, $path, null];
    }

    /**
     * @param string $first
     * @param string $path
     * @param string $method
     * @return array
     */
    protected function findInRegularRoutes(string $first, string $path, string $method): array
    {
        $allowedMethods = '';
        /** @var array $routesInfo */
        $routesInfo = $this->regularRoutes[$first];

        foreach ($routesInfo as $conf) {
            if (0 === \strpos($path, $conf['start']) && \preg_match($conf['regex'], $path, $matches)) {
                $allowedMethods .= $conf['methods'];

                if (false !== \strpos($conf['methods'], $method . ',')) {
                    $conf = $this->mergeMatches($matches, $conf);

                    return [self::FOUND, $path, $conf];
                }
            }
        }

        return [
            self::NOT_FOUND,
            $allowedMethods ? \explode(',', \rtrim($allowedMethods, ',')) : []
        ];
    }

    /**
     * @param string $path
     * @param string $method
     * @return array|false
     */
    protected function findInVagueRoutes(string $path, string $method)
    {
        if (!isset($this->vagueRoutes[$method])) {
            return false;
        }

        /** @var array $routeList */
        $routeList = $this->vagueRoutes[$method];

        foreach ($routeList as $conf) {
            if ($conf['start'] && 0 !== \strpos($path, $conf['start'])) {
                continue;
            }

            if (\preg_match($conf['regex'], $path, $matches)) {
                $conf = $this->mergeMatches($matches, $conf);

                return [self::FOUND, $path, $conf];
            }
        }

        return false;
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
