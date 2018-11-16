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
final class ServerRouter extends Router
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
    }

    /*******************************************************************************
     * route match
     ******************************************************************************/

    /**
     * {@inheritdoc}
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

        // find in route caches.
        if ($this->cacheRoutes && isset($this->cacheRoutes[$sKey])) {
            return [self::FOUND, $path, $this->cacheRoutes[$sKey]];
        }

        // is a dynamic route, match by regexp
        $result = $this->matchDynamicRoute($path, $method);
        if ($result[0] === self::FOUND) {
            $this->cacheMatchedParamRoute($path, $method, $result[2]);
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

            if ($this->cacheRoutes && isset($this->cacheRoutes[$sKey])) {
                return [self::FOUND, $path, $this->cacheRoutes[$sKey]];
            }

            $result = $this->matchDynamicRoute($path, 'GET');
            if ($result[0] === self::FOUND) {
                return $result;
            }
        }

        // If nothing else matches, try fallback routes. $router->any('*', 'handler');
        $sKey = $method . ' /*';
        if ($this->staticRoutes && isset($this->staticRoutes[$sKey])) {
            return [self::FOUND, $path, $this->staticRoutes[$sKey]];
        }

        if ($this->handleMethodNotAllowed) {
            return [self::NOT_FOUND, $path, null];
        }

        // collect allowed methods from: staticRoutes, vagueRoutes OR return not found.
        return $this->findAllowedMethods($path, $method);
    }

    /*******************************************************************************
     * helper methods
     ******************************************************************************/

    /**
     * @param string $path
     * @param string $method
     * @param array $conf
     */
    protected function cacheMatchedParamRoute(string $path, string $method, array $conf)
    {
        $cacheKey = $method . ' ' . $path;
        $cacheNumber = (int)$this->tmpCacheNumber;

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
