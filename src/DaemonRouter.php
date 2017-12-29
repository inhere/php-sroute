<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/12/29 0029
 * Time: 23:56
 */

namespace Inhere\Route;

/**
 * Class DaemonRouter
 *
 * - 适用于常驻后台的应用程序(e.g swoole server)
 * - 多了动态路由缓存
 *
 * @package Inhere\Route
 */
class DaemonRouter extends ORouter
{
    /** @var int */
    private $cacheCounter = 0;

    /**
     * There are last route caches
     * @var array[]
     * [
     *     '/user/login' => [
     *          // METHOD => INFO [...]
     *          'GET' => [
     *              'handler' => 'handler',
     *              'option' => [...],
     *          ],
     *          'PUT' => [
     *              'handler' => 'handler',
     *              'option' => [...],
     *          ],
     *          ...
     *      ],
     *      ... ...
     * ]
     */
    protected $routeCaches = [];

    /**
     * flatten static routes
     * @see AbstractRouter::$staticRoutes
     * @var array
     * [
     *  '/user/login#GET' => int, (int: data index in the {@see AbstractRouter::$routesData})
     *  '/user/login#PUT' => int,
     * ]
     */
    protected $flatStaticRoutes = [];

    /**
     * The param route cache number.
     * @notice If is not daemon application, Please don't enable it.
     * @var int
     */
    protected $tmpCacheNumber = 0;

    /**
     * Flatten static routes info {@see $flatStaticRoutes}
     * @var bool
     */
    protected $flattenStatic = true;

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
             *      'GET' => 31,
             *      'POST' => 31,
             * ]
             */
            foreach ($this->staticRoutes as $path => $items) {
                foreach ($items as $method => $index) {
                    $this->flatStaticRoutes[$path . '#' . $method] = $index;
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
    public function match($path, $method = 'GET')
    {
        // if enable 'matchAll'
        if ($matchAll = $this->matchAll) {
            if (\is_string($matchAll) && $matchAll{0} === '/') {
                $path = $matchAll;
            } elseif (\is_callable($matchAll)) {
                return [
                    self::FOUND,
                    $path,
                    [
                        'handler' => $matchAll
                    ]
                ];
            }
        }

        $path = $this->formatUriPath($path, $this->ignoreLastSlash);
        $method = strtoupper($method);

        // find in route caches.
        if ($this->routeCaches && isset($this->routeCaches[$path][$method])) {
            return [self::FOUND, $path, $this->routeCaches[$path][$method]];
        }

        // is a static route path
        if ($routeInfo = $this->findInStaticRoutes($path, $method)) {
            return [self::FOUND, $path, $routeInfo];
        }

        $first = null;
        $allowedMethods = [];

        // eg '/article/12'
        if ($pos = strpos($path, '/', 1)) {
            $first = substr($path, 1, $pos - 1);
        }

        // is a regular dynamic route(the first node is 1th level index key).
        if ($first && isset($this->regularRoutes[$first])) {
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
            return [
                self::FOUND,
                $path,
                [
                    'handler' => $handler,
                ]
            ];
        }

        // For HEAD requests, attempt fallback to GET
        if ($method === self::HEAD) {
            if ($this->tmpCacheNumber && isset($this->routeCaches[$path]['GET'])) {
                return [self::FOUND, $path, $this->routeCaches[$path]['GET']];
            }

            if ($routeInfo = $this->findInStaticRoutes($path, 'GET')) {
                return [self::FOUND, $path, $routeInfo];
            }

            if ($first && isset($this->regularRoutes[$first])) {
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
        if ($routeInfo = $this->findInStaticRoutes('/*', $method)) {
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
    protected function findInStaticRoutes($path, $method)
    {
        // if flattenStatic is TRUE
        if ($this->flattenStatic) {
            $key = $path . '#' . $method;

            if (isset($this->flatStaticRoutes[$key])) {
                $index = $this->staticRoutes[$key];

                return $this->routesData[$index];
            }

            return false;
        }

        if (isset($this->staticRoutes[$path][$method])) {
            $index = $this->staticRoutes[$path][$method];

            return $this->routesData[$index];
        }

        return false;
    }

    /**
     * @param array $routesInfo
     * @param string $path
     * @param string $method
     * @return array
     */
    protected function findInRegularRoutes(array $routesInfo, $path, $method)
    {
        $allowedMethods = '';

        foreach ($routesInfo as $id => $conf) {
            if (0 === strpos($path, $conf['start']) && preg_match($conf['regex'], $path, $matches)) {
                $allowedMethods .= $conf['methods'];

                if (false !== strpos($conf['methods'], $method . ',')) {
                    $data = $this->routesData[$id];
                    $this->filterMatches($matches, $data);

                    if ($this->tmpCacheNumber > 0) {
                        $this->cacheMatchedParamRoute($path, $method, $data);
                    }

                    return [self::FOUND, $path, $data];
                }
            }
        }

        return [self::NOT_FOUND, explode(',', trim($allowedMethods, ','))];
    }

    /**
     * @param array $routesInfo
     * @param string $path
     * @param string $method
     * @return array
     */
    protected function findInVagueRoutes(array $routesInfo, $path, $method)
    {
        foreach ($routesInfo as $id => $conf) {
            if ($conf['include'] && false === strpos($path, $conf['include'])) {
                continue;
            }

            if (preg_match($conf['regex'], $path, $matches)) {
                $data = $this->routesData[$id];
                $this->filterMatches($matches, $data);

                if ($this->tmpCacheNumber > 0) {
                    $this->cacheMatchedParamRoute($path, $method, $data);
                }

                return [self::FOUND, $path, $data];
            }
        }

        return [self::NOT_FOUND];
    }

    /**
     * @param string $path
     * @param string $method
     * @param array $data
     */
    protected function cacheMatchedParamRoute($path, $method, $data)
    {
        $cacheNumber = (int)$this->tmpCacheNumber;

        // cache last $cacheNumber routes.
        if ($cacheNumber > 0 && !isset($this->routeCaches[$path][$method])) {
            if ($this->cacheCounter >= $cacheNumber) {
                $this->cacheCounter--;
                array_shift($this->routeCaches);
            }

            $this->cacheCounter++;
            $this->routeCaches[$path][$method] = $data;
        }
    }

    /**
     * @return array
     */
    public function getRouteCaches()
    {
        return $this->routeCaches;
    }
}
