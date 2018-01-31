<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/2/1 0001
 * Time: 00:12
 */

namespace Inhere\Route;

/**
 * Class ServerRouter
 *
 * 适用于常驻后台的应用程序(e.g swoole server, workman)
 * - 多了动态路由缓存
 *
 * @package Inhere\Route
 */
final class ServerRouter extends ORouter
{
    /** @var int */
    private $cacheCounter = 0;

    /**
     * The param route cache number.
     * @notice If is not daemon application, Please don't enable it.
     * @var int
     */
    protected $tmpCacheNumber = 0;

    /**
     * There are last route caches. like static routes
     * @var array[]
     * [
     *     '/user/login' => [
     *          // METHOD => INFO [...]
     *          'GET' => [
     *              'handler' => 'handler0',
     *              'option' => [...],
     *          ],
     *          'PUT' => [
     *              'handler' => 'handler1',
     *              'option' => [...],
     *          ],
     *          ...
     *      ],
     *      ... ...
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
             *      'GET' => 31,
             *      'POST' => 31,
             * ]
             */
            foreach ($this->staticRoutes as $path => $items) {
                foreach ($items as $method => $conf) {
                    $this->flatStaticRoutes[$path . '#' . $method] = $conf;
                }
            }
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
