<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-02-09
 * Time: 10:03
 */

namespace Inhere\Route;

use InvalidArgumentException;
use function in_array;
use function is_string;

/**
 * Class RouterManager
 * @package Inhere\Route
 */
class RouterManager
{
    public const DEFAULT_ROUTER = 'default';

    /**
     * @var self
     */
    private static $_instance;

    /**
     * @var Router[]
     * [
     *  'main-site' => Object(Router),
     *  ... ...
     * ]
     */
    private $routers = [];

    /**
     * @var array Available router driver
     */
    private $drivers = [
        'default'  => Router::class,
        'cached'   => CachedRouter::class,
        'preMatch' => PreMatchRouter::class,
        'server'   => ServerRouter::class,
    ];

    /**
     * @var array[]
     * [
     *  'default' => 'main-site', // this is default router.
     *  'main-site' => [
     *      'driver' => 'default',
     *      'conditions' => [
     *          'domains' => 'abc.com',
     *          'schemes' => ['https'],
     *      ],
     *      'options' => [
     *          // some setting for router.
     *          'name' => 'value'
     *      ],
     *  ],
     *  'doc-site' => [
     *      'driver' => 'cached',
     *      'conditions' => [
     *          'domains' => 'docs.abc.com',
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
     *  'main-site' => [
     *      'domains' => 'abc.com',
     *      'schemes' => ['https'],
     *  ],
     *  'doc-site' => [
     *       'domains' => 'docs.abc.com',
     *       'schemes' => ['https'],
     *   ],
     *  'th3-site' => [
     *       'domains' => 'th3.abc.com',
     *   ],
     * ]
     */
    private $conditions = [];

    /**
     * @var array
     */
    private $supportedConditions = [
        'domains' => 'domain',
        'schemes' => 'scheme',
    ];

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
    public static function instance(): RouterManager
    {
        return self::$_instance;
    }

    /**
     * RouterManager constructor.
     *
     * @param array $configs
     */
    public function __construct(array $configs = [])
    {
        self::$_instance = $this;

        if ($configs) {
            $this->configs($configs);
        }
    }

    // match by $_SERVER info.

    /**
     * get router by condition
     *
     * @param array|string $condition
     * array:
     * [
     *   'domain' => 'abc.com',
     *   'scheme' => 'https',
     * ]
     * string:
     *  get by name. same of call getByName()
     *
     * @return Router |RouterInterface
     * @throws InvalidArgumentException
     */
    public function get($condition = null): Router
    {
        if (!$condition) {
            return $this->getDefault();
        }

        // alias of getByName()
        if (is_string($condition)) {
            return $this->getByName($condition);
        }

        $useName = self::DEFAULT_ROUTER;

        foreach ($this->conditions as $name => $cond) {
            if ($this->compareArray($cond, $condition)) {
                $useName = $name;
                break;
            }
        }

        return $this->getByName($useName);
    }

    /**
     * @param array $define
     * @param array $input
     *
     * @return bool
     */
    protected function compareArray(array $define, array $input): bool
    {
        $match = true;

        foreach ($this->supportedConditions as $def => $key) {
            if (isset($define[$def], $input[$key])) {
                $defValues = (array)$define[$def];

                if (!in_array($input[$key], $defValues, true)) {
                    $match = false;
                    break;
                }
            }
        }

        return $match;
    }

    /**
     * @param string $name
     *
     * @return Router
     * @throws InvalidArgumentException
     */
    public function getByName(string $name): Router
    {
        if (!isset($this->configs[$name])) {
            throw new InvalidArgumentException("The named router '$name' does not exists!");
        }

        // if created
        if (isset($this->routers[$name])) {
            return $this->routers[$name];
        }

        // create
        $config = $this->configs[$name];

        if (is_string($config)) {
            if (!isset($this->configs[$config])) {
                throw new InvalidArgumentException("The reference config '$config' does not exists of the '$name'!");
            }

            $config = $this->configs[$config];
        }

        return ($this->routers[$name] = $this->createRouter($config, $name));
    }

    /**
     * @return Router
     * @throws InvalidArgumentException
     */
    public function getDefault(): Router
    {
        return $this->getByName(self::DEFAULT_ROUTER);
    }

    /**
     * @param array  $config
     * @param string $name
     *
     * @return Router
     * @throws InvalidArgumentException
     */
    private function createRouter(array $config, string $name = ''): Router
    {
        $driver  = $config['driver'] ?? self::DEFAULT_ROUTER;
        $options = $config['options'] ?? [];

        if (!$class = $this->drivers[$driver] ?? null) {
            throw new InvalidArgumentException("The router driver name '$driver' does not exists!");
        }

        if ($name && !isset($options['name'])) {
            $options['name'] = $name;
        }

        return new $class($options);
    }

    /**
     * @param string $name
     * @param string $class
     */
    public function setDriver(string $name, string $class): void
    {
        $this->drivers[$name] = $class;
    }

    /**
     * @return Router[]
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
    public function configs(array $configs): void
    {
        $this->configs = $configs;

        foreach ($configs as $name => $config) {
            if (isset($config['conditions'])) {
                $this->conditions[$name] = $config['conditions'];
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
