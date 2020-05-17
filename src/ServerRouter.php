<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/2/1 0001
 * Time: 00:12
 */

namespace Inhere\Route;

use Inhere\Route\Helper\RouteHelper;
use LogicException;
use function array_shift;
use function count;
use function strtoupper;

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
    /**
     * The param route cache number.
     * @var int
     */
    public $tmpCacheNumber = 300;

    /**
     * There are last route caches. like static routes
     * @see $staticRoutes
     * @var Route[]
     * [
     *  'GET /user/login' => Route,
     *  'PUT /user/login' => Route,
     * ]
     */
    private $cacheRoutes = [];

    /**
     * object constructor.
     *
     * @param array $config
     *
     * @throws LogicException
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
        // For HEAD requests, attempt fallback to GET
        $method = strtoupper($method);
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $path = RouteHelper::formatPath($path, $this->ignoreLastSlash);
        $sKey = $method . ' ' . $path;

        // It is a static route path
        if (isset($this->staticRoutes[$sKey])) {
            return [self::FOUND, $path, $this->staticRoutes[$sKey]];
        }

        // Find in route caches.
        if ($this->cacheRoutes && isset($this->cacheRoutes[$sKey])) {
            return [self::FOUND, $path, $this->cacheRoutes[$sKey]];
        }

        // It is a dynamic route, match by regexp
        $result = $this->matchDynamicRoute($path, $method);
        if ($result[0] === self::FOUND) { // will cache param route.
            $this->cacheMatchedParamRoute($path, $method, $result[2]);
            return $result;
        }

        // Handle Auto Route
        if ($this->autoRoute && ($handler = $this->matchAutoRoute($path))) {
            return [self::FOUND, $path, Route::create($method, $path, $handler)];
        }

        // If nothing else matches, try fallback routes. $router->any('*', 'handler');
        $sKey = $method . ' /*';
        if ($this->staticRoutes && isset($this->staticRoutes[$sKey])) {
            return [self::FOUND, $path, $this->staticRoutes[$sKey]];
        }

        if ($this->handleMethodNotAllowed) {
            return $this->findAllowedMethods($path, $method);
        }

        return [self::NOT_FOUND, $path, null];
    }

    /*******************************************************************************
     * helper methods
     ******************************************************************************/

    /**
     * @param string $path
     * @param string $method
     * @param Route  $route
     */
    protected function cacheMatchedParamRoute(string $path, string $method, Route $route): void
    {
        $cacheKey    = $method . ' ' . $path;
        $cacheNumber = (int)$this->tmpCacheNumber;

        // cache last $cacheNumber routes.
        if ($cacheNumber > 0 && !isset($this->cacheRoutes[$cacheKey])) {
            if ($this->getCacheCount() >= $cacheNumber) {
                array_shift($this->cacheRoutes);
            }

            $this->cacheRoutes[$cacheKey] = $route;
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
    public function getCacheCount(): int
    {
        return count($this->cacheRoutes);
    }
}
