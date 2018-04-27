<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/2/1 0001
 * Time: 00:12
 */

namespace Inhere\Route;

use Inhere\Route\Helper\RouteHelper;

/**
 * Class ServerRouter
 *
 * 适用于常驻后台的应用程序(e.g swoole server, workman)
 * - 多了动态路由临时缓存
 *
 * @package Inhere\Route
 */
final class ServerRouter extends ORouter
{
    /** @var int */
    private $cacheCounter = 0;

    /**
     * The param route cache number.
     * @var int
     */
    public $tmpCacheNumber = 300;

    /**
     * There are last route caches. like static routes
     * @var array[]
     * [
     *  '/user/login#GET' => [
     *      'handler' => 'handler0',
     *      'option' => [...],
     *  ],
     *  '/user/login#PUT' => [
     *      'handler' => 'handler1',
     *      'option' => [...],
     *  ],
     * ]
     */
    protected $cacheRoutes = [];

    /**
     * Flatten static routes info {@see $flatStaticRoutes}
     * @var bool
     */
    protected $flattenStatic = true;

    /**
     * flatten static routes
     * @see AbstractRouter::$staticRoutes
     * @var array
     * [
     *  '/user/login#GET' => [
     *      'handler' => 'handler0',
     *      'option' => [...],
     *  ],
     *  '/user/login#PUT' => [
     *      'handler' => 'handler1',
     *      'option' => [...],
     *  ],
     * ]
     */
    protected $flatStaticRoutes = [];

    /**
     * object constructor.
     * @param array $config
     * @throws \LogicException
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if (isset($config['tmpCacheNumber'])) {
            $this->tmpCacheNumber = (int)$config['tmpCacheNumber'];
        }

        if (isset($config['flattenStatic'])) {
            $this->flattenStatic = (bool)$config['flattenStatic'];
        }
    }

    /**
     * convert staticRoutes to $flatStaticRoutes
     */
    public function flattenStatics()
    {
        if ($this->flattenStatic) {
            /**
             * @var array $items eg:
             * '/user/login' => [
             *      // METHOD => [...]
             *      'GET' => [
             *          'handler' => 'handler',
             *          'option' => [...],
             *      ],
             * ]
             */
            foreach ($this->staticRoutes as $path => $items) {
                foreach ($items as $method => $conf) {
                    $this->flatStaticRoutes[$path . '#' . $method] = $conf;
                }
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

        // is a static route path
        if ($this->staticRoutes && ($routeInfo = $this->findInStaticRoutes($path, $method))) {
            return [self::FOUND, $path, $routeInfo];
        }

        $cacheKey = $path . '#' . $method;

        // find in route caches.
        if ($this->cacheRoutes && isset($this->cacheRoutes[$cacheKey])) {
            return [self::FOUND, $path, $this->cacheRoutes[$cacheKey]];
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
            $cacheKey = $path . '#GET';

            if (isset($this->cacheRoutes[$cacheKey])) {
                return [self::FOUND, $path, $this->cacheRoutes[$cacheKey]];
            }

            if ($routeInfo = $this->findInStaticRoutes($path, 'GET')) {
                return [self::FOUND, $path, $routeInfo];
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
        if ($this->staticRoutes && ($routeInfo = $this->findInStaticRoutes('/*', $method))) {
            return [self::FOUND, $path, $routeInfo];
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
     * @return array|false
     */
    protected function findInStaticRoutes(string $path, string $method)
    {
        // if flattenStatic is TRUE
        if ($this->flatStaticRoutes) {
            $key = $path . '#' . $method;

            if (isset($this->flatStaticRoutes[$key])) {
                return $this->flatStaticRoutes[$key];
            }
        } elseif (isset($this->staticRoutes[$path][$method])) {
            return $this->staticRoutes[$path][$method];
        }

        return false;
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

                    if ($this->tmpCacheNumber > 0) {
                        $this->cacheMatchedParamRoute($path, $method, $conf);
                    }

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

                if ($this->tmpCacheNumber > 0) {
                    $this->cacheMatchedParamRoute($path, $method, $conf);
                }

                return [self::FOUND, $path, $conf];
            }
        }

        return false;
    }

    /**
     * @param string $path
     * @param string $method
     * @param array $conf
     */
    protected function cacheMatchedParamRoute(string $path, string $method, array $conf)
    {
        $cacheNumber = (int)$this->tmpCacheNumber;
        $cacheKey = $path . '#' . $method;

        // cache last $cacheNumber routes.
        if ($cacheNumber > 0 && !isset($this->cacheRoutes[$cacheKey])) {
            if ($this->cacheCounter >= $cacheNumber) {
                \array_shift($this->cacheRoutes);
            }

            $this->cacheCounter++;
            $this->cacheRoutes[$cacheKey] = $conf;
        }
    }

    /**
     * @return array[]
     */
    public function getCacheRoutes(): array
    {
        return $this->cacheRoutes;
    }

    /**
     * @return int
     */
    public function getCacheCounter(): int
    {
        return $this->cacheCounter;
    }
}
