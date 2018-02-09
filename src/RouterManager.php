<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-02-09
 * Time: 10:03
 */

namespace Inhere\Route;

use Inhere\Route\Base\RouterInterface;

/**
 * Class RouterManager
 * @package Inhere\Route
 */
class RouterManager
{
    const DEFAULT_ROUTER = 'default';

    /**
     * @var self
     */
    private static $_instance;

    /**
     * @var ORouter[]
     */
    private static $routers = [];

    /**
     * @var array
     */
    private $drivers = [
        'default' => ORouter::class,
        'cached' => CachedRouter::class,
        'preMatch' => PreMatchRouter::class,
        'server' => ServerRouter::class,
    ];

    /**
     * @var array[]
     * [
     *  'default' => [
     *      // some setting for router.
     *      'name' => 'value'
     *  ],
     *  'cached' => [
     *      'cacheFile' => '/path/to/routes-cache.php',
     *      'cacheEnable' => true,
     *  ],
     * ]
     */
    private $configs;

    /**
     * @var array[]
     * [
     *  'main-site' => [
     *      'domain' => 'domain.com',
     *      'driver' => 'default',
     *      'options' => [],
     *  ],
     *  'doc-site' => [
     *      'domain' => 'docs.domain.com'
     *  ],
     * ]
     */
    private $conditions = [];

    // private $onCreated = [
    //     'cached' => 'completed'
    // ];

    /**
     * @return RouterManager
     */
    public static function instance()
    {
        return self::$_instance;
    }

    /**
     * RouterManager constructor.
     * @param array $configs
     */
    public function __construct(array $configs = [])
    {
        self::$_instance = $this;

        $this->configs = $configs;
    }

    /**
     * @param array $conditions
     * @return ORouter|RouterInterface
     */
    public function getRouter(array $conditions = []): RouterInterface
    {
        $driver = self::DEFAULT_ROUTER;

        if (!$conditions) {

        }
    }

    /**
     * @param string $name
     * @param string $class
     */
    public function setDriver(string $name, string $class)
    {
        $this->drivers[$name] = $class;
    }

    /**
     * @return ORouter[]
     */
    public static function getRouters(): array
    {
        return self::$routers;
    }

    /**
     * @return array
     */
    public function getDrivers(): array
    {
        return $this->drivers;
    }

    /**
     * @return array
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * @param array $conditions
     */
    public function setConditions(array $conditions)
    {
        $this->conditions = $conditions;
    }

    /**
     * @return array[]
     */
    public function getConfigs(): array
    {
        return $this->configs;
    }
}