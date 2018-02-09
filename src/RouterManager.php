<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-02-09
 * Time: 10:03
 */

namespace Inhere\Route;

use Inhere\Route\Base\AbstractRouter;
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
     * [
     *  'main-site' => Object(ORouter),
     *  ... ...
     * ]
     */
    private $routers = [];

    /**
     * @var array Available router driver
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
     *  'default' => 'main-site', // this is default router.
     *
     *  'main-site' => [
     *      'driver' => 'default',
     *      'conditions' => [
     *          'domain' => 'domain.com',
     *      ],
     *      'options' => [
     *          // some setting for router.
     *          'name' => 'value'
     *      ],
     *  ],
     *  'doc-site' => [
     *      'driver' => 'cached',
     *      'conditions' => [
     *          'domain' => 'docs.domain.com',
     *      ],
     *      'options' => [
     *          'cacheFile' => '/path/to/routes-cache.php',
     *          'cacheEnable' => true,
     *      ],
     *  ],
     * ]
     */
    private $configs;

    /**
     * @var array
     * [
     *  'condition ID' => 'name',
     *  'condition ID1' => 'main-site'
     * ]
     */
    private $conditions = [];

    /**
     * @var array
     */
    // private $onCollected = [
    //     'cached' => 'completed',
    //     'server' => 'flattenStatics'
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

        if ($configs) {
            $this->setConfigs($configs);
        }
    }

    /**
     * get router by condition
     * @param array $conditions
     * [
     *  'domain' => 'domain.com'
     * ]
     * @return AbstractRouter|RouterInterface
     * @throws \InvalidArgumentException
     */
    public function get($conditions = null): AbstractRouter
    {
        if (!$conditions) {
            return $this->getDefault();
        }

        $key = $this->genConditionID($conditions);
        $name = $this->conditions[$key] ?? self::DEFAULT_ROUTER;

        return $this->getByName($name);
    }

    /**
     * @param string $name
     * @return AbstractRouter
     * @throws \InvalidArgumentException
     */
    public function getByName(string $name): AbstractRouter
    {
        if (!isset($this->configs[$name])) {
            throw new \InvalidArgumentException("The named router '$name' does not exists!");
        }

        // if created
        if (isset($this->routers[$name])) {
            return $this->routers[$name];
        }

        // create
        $config = $this->configs[$name];

        if (\is_string($config)) {
            if (!isset($this->configs[$config])) {
                throw new \InvalidArgumentException("The reference config '$config' does not exists of the '$name'!");
            }

            $config = $this->configs[$config];
        }

        return ($this->routers[$name] = $this->createRouter($config));
    }

    /**
     * @return AbstractRouter
     */
    public function getDefault(): AbstractRouter
    {
        return $this->getByName(self::DEFAULT_ROUTER);
    }

    /**
     * @param string|array $conditions
     * @return string
     */
    private function genConditionID($conditions): string
    {
        return \md5(\is_array($conditions) ? \json_encode($conditions) : $conditions);
    }

    /**
     * @param array $config
     * @return AbstractRouter
     * @throws \InvalidArgumentException
     */
    private function createRouter(array $config): AbstractRouter
    {
        $driver = $config['driver'] ?? self::DEFAULT_ROUTER;
        $options = $config['options'] ?? [];

        if (!$class = $this->drivers[$driver] ?? null) {
            throw new \InvalidArgumentException("The router driver name '$driver' does not exists!");
        }

        return new $class($options);
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
    public function getRouters(): array
    {
        return $this->routers;
    }

    /**
     * @return array
     */
    public function getDrivers(): array
    {
        return $this->drivers;
    }

    /**
     * @return array[]
     */
    public function getConfigs(): array
    {
        return $this->configs;
    }

    /**
     * @param array[] $configs
     */
    public function setConfigs(array $configs)
    {
        $this->configs = $configs;

        foreach ($configs as $name => $config) {
            if (isset($config['conditions'])) {
                $key = $this->genConditionID($config['conditions']);
                $this->conditions[$key] = $name;
            }
        }
    }

    /**
     * @return array
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }
}