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
                $this->routeCounter++;
                // $this->staticRoutes[$route][$method] = $conf;
                $this->staticRoutes[$method . ' ' . $route] = $conf;
            }

            return $this;
        }

        $conf['original'] = $route;

        // collect param route
        $this->collectParamRoute($methods, $conf, $opts['params'] ?? []);

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
     * @param array $methods
     * @param array $conf
     * @param array $params
     * @throws \LogicException
     */
    protected function collectParamRoute(array $methods, array $conf, array $params)
    {
        list($first, $conf) = $this->parseParamRoute($conf, $this->getAvailableParams($params));

        // route string have regular
        if ($first) {
            foreach ($methods as $method) {
                $this->routeCounter++;
                $this->regularRoutes[$method . ' ' . $first][] = $conf;
            }
        } else {
            foreach ($methods as $method) {
                $this->routeCounter++;
                $this->vagueRoutes[$method][] = $conf;
            }
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
        $sKey = $method . ' ' . $path;

        // is a static route path
        if (isset($this->staticRoutes[$sKey])) {
            return [self::FOUND, $path, $this->staticRoutes[$sKey]];
        }

        // is a dynamic route, match by regexp
        $result = $this->doMatch($path, $method);
        if ($result[0] === self::FOUND) {
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
            $sKey = 'GET ' . $path;
            if (isset($this->staticRoutes[$sKey])) {
                return [self::FOUND, $path, $this->staticRoutes[$sKey]];
            }

            $result = $this->doMatch($path, 'GET');
            if ($result[0] === self::FOUND) {
                return $result;
            }
        }

        // If nothing else matches, try fallback routes. $router->any('*', 'handler');
        $sKey = $method . ' /*';
        if ($this->staticRoutes && isset($this->staticRoutes[$sKey])) {
            return [self::FOUND, $path, $this->staticRoutes[$sKey]];
        }

        if ($this->notAllowedAsNotFound) {
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
     */
    protected function doMatch(string $path, string $method): array
    {
        $fKey = $first = null;

        if ($pos = \strpos($path, '/', 1)) {
            $first = \substr($path, 1, $pos - 1);
            $fKey = $method . ' ' . $first;
        }

        // is a regular dynamic route(the first node is 1th level index key).
        if ($fKey && $routeList = $this->regularRoutes[$fKey]?? false) {
            foreach ($routeList as $conf) {
                if (0 === \strpos($path, $conf['start']) && \preg_match($conf['regex'], $path, $matches)) {
                // if (\preg_match($conf['regex'], $path, $matches)) {
                //     $conf = $this->mergeMatches($matches, $conf);

                    // collect param values. first is full match.
                    unset($matches[0]);
                    // if (isset($conf['']))
                    $conf['matches'] = $matches;

                    return [self::FOUND, $path, $conf];
                }
            }
        }

        // is a irregular dynamic route
        if ($routeList = $this->vagueRoutes[$method] ?? false) {
            foreach ($routeList as $conf) {
                if ($conf['start'] && 0 !== \strpos($path, $conf['start'])) {
                    continue;
                }

                if (\preg_match($conf['regex'], $path, $matches)) {
                    unset($matches[0]);
                    $conf['matches'] = $matches;

                    return [self::FOUND, $path, $conf];
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

        foreach (self::ALLOWED_METHODS as $m) {
            if ($method === $m) {
                continue;
            }

            $sKey = $m . ' ' . $path;

            if (isset($this->staticRoutes[$sKey])) {
                $allowedMethods[] = $m;
            }

            $result = $this->doMatch($path, $m);
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
