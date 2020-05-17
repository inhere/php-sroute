<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-12-13
 * Time: 23:49
 */

namespace Inhere\Route;

use LogicException;
use function method_exists;
use function trim;
use function ucfirst;

/**
 * Trait RouterConfigTrait
 *
 * @package Inhere\Route
 */
trait RouterConfigTrait
{
    /** @var string The router name */
    private $name = '';

    /**
     * some available patterns regex
     * $router->get('/user/{id}', 'handler');
     *
     * @var array
     */
    protected static $globalParams = [
        'all' => '.*',
        'any' => '[^/]+',        // match any except '/'
        'num' => '[1-9][0-9]*',  // match a number and gt 0
        'int' => '\d+',          // match a number
        'act' => '[a-zA-Z][\w-]+', // match a action name
    ];

    /*******************************************************************************
     * router config
     ******************************************************************************/

    /**
     * Can define an default route path
     *
     * @var string
     */
    // public $defaultRoute = '';

    /**
     * Ignore last slash char('/'). If is True, will clear last '/'.
     *
     * @var bool
     */
    public $ignoreLastSlash = false;

    /**
     * Whether handle method not allowed. If True, will find allowed methods.
     *
     * @var bool
     */
    public $handleMethodNotAllowed = false;

    /**
     * Enable auto route match like yii framework
     * If is True, will auto find the handler controller file.
     *
     * @var bool
     */
    public $autoRoute = false;

    /**
     * The default controllers namespace. eg: 'App\\Controllers'
     *
     * @var string
     */
    public $controllerNamespace = '';

    /**
     * The first char case of namespace.
     *
     * false - lower case. eg: 'controllers\admin'
     * true - upper case. eg: 'Controllers\Admin'
     *
     * @var bool
     */
    protected $namespaceUcFirst = false;

    /**
     * Controller suffix, is valid when '$autoRoute' = true. eg: 'Controller'
     *
     * @var string
     */
    public $controllerSuffix = 'Controller';

    /**
     * @var array global Options
     */
    private $globalOptions = [
        // 'domains' => [ 'localhost' ], // allowed domains
        // 'schemas' => [ 'http' ], // allowed schemas
        // 'time' => ['12'],
    ];

    /**
     * config the router
     *
     * @param array $config
     *
     * @throws LogicException
     */
    public function config(array $config): void
    {
        if ($this->routeCounter > 0) {
            throw new LogicException('Routing has been added, and configuration is not allowed!');
        }

        $props = [
            'name'                   => 1,
            'chains'                 => 1,
            // 'defaultRoute'           => 1,
            'ignoreLastSlash'        => 1,
            'tmpCacheNumber'         => 1,
            'handleMethodNotAllowed' => 1,
            'autoRoute'              => 1,
            'controllerNamespace'    => 1,
            'controllerSuffix'       => 1,
        ];

        foreach ($config as $name => $value) {
            $setter = 'set' . ucfirst($name);
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            } elseif (isset($props[$name])) {
                $this->$name = $value;
            }
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param array $params
     */
    public function addGlobalParams(array $params): void
    {
        foreach ($params as $name => $pattern) {
            $this->addGlobalParam($name, $pattern);
        }
    }

    /**
     * @param string $name
     * @param string $pattern
     */
    public function addGlobalParam(string $name, string $pattern): void
    {
        $name = trim($name, '{} ');

        self::$globalParams[$name] = $pattern;
    }

    /**
     * @return array
     */
    public function getGlobalParams(): array
    {
        return self::$globalParams;
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
     */
    public function setGlobalOptions(array $globalOptions): void
    {
        $this->globalOptions = $globalOptions;
    }

    /**
     * @return bool
     */
    public function isNamespaceUcFirst(): bool
    {
        return $this->namespaceUcFirst;
    }

    /**
     * @param bool $namespaceUcFirst
     */
    public function setNamespaceUcFirst($namespaceUcFirst): void
    {
        $this->namespaceUcFirst = (bool)$namespaceUcFirst;
    }
}
