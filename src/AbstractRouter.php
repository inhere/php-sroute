<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/17
 * Time: 下午11:37
 */

namespace Inhere\Route;

use Inhere\Route\Helper\RouteHelper;

/**
 * Class AbstractRouter
 * @package Inhere\Route
 */
abstract class AbstractRouter implements RouterInterface, \Countable
{
    /** @var string The router name */
    private $name = '';

    /**
     * some available patterns regex
     * $router->get('/user/{id}', 'handler');
     * @var array
     */
    protected static $globalParams = [
        'all' => '.*',
        'any' => '[^/]+',        // match any except '/'
        'num' => '[1-9][0-9]*',  // match a number and gt 0
        'int' => '\d+',          // match a number
        'act' => '[a-zA-Z][\w-]+', // match a action name
    ];

    /** @var int */
    protected $routeCounter = 0;

    // -- Group info

    /** @var string */
    protected $currentGroupPrefix;
    /** @var array */
    protected $currentGroupOption;
    /** @var array */
    protected $currentGroupChains;

    /**
     * static Routes - no dynamic argument match
     * 整个路由 path 都是静态字符串 e.g. '/user/login'
     * @var Route[]
     * [
     *     '/user/login' => [
     *          // METHOD => Route object,
     *          'GET' => Route,
     *          'PUT' => Route,
     *          ...
     *      ],
     *      ... ...
     * ]
     */
    protected $staticRoutes = [];

    /**
     * regular Routes - have dynamic arguments, but the first node is normal string.
     * 第一节是个静态字符串，称之为有规律的动态路由。按第一节的信息进行分组存储
     * @var Route[][]
     * [
     *     // 使用完整的第一节作为key进行分组
     *     'edit' => [
     *          Route, // '/edit/{id}'
     *          ...
     *      ],
     *     'blog' => [
     *        Route, // '/blog/post-{id}'
     *        ...
     *     ],
     *     ... ...
     * ]
     */
    protected $regularRoutes = [];

    /**
     * vague Routes - have dynamic arguments,but the first node is exists regex.
     * 第一节就包含了正则匹配，称之为无规律/模糊的动态路由
     * @var Route[][]
     * [
     *     // 使用 HTTP METHOD 作为 key进行分组
     *     'GET' => [
     *          Route, // '/{name}/profile'
     *          ...
     *     ],
     *     'POST' => [
     *          Route, // '/{some}/{some2}'
     *          ...
     *     ],
     *      ... ...
     * ]
     */
    protected $vagueRoutes = [];

    /**
     * middleware chains
     * @var array
     */
    private $chains = [];

    /*******************************************************************************
     * router config
     ******************************************************************************/

    /**
     * Ignore last slash char('/'). If is True, will clear last '/'.
     * @var bool
     */
    public $ignoreLastSlash = false;

    /**
     * whether handle method not allowed. If True, will find allowed methods.
     * @var bool
     */
    public $handleMethodNotAllowed = false;

    /**
     * Auto route match @like yii framework
     * If is True, will auto find the handler controller file.
     * @var bool
     */
    public $autoRoute = false;

    /**
     * The default controllers namespace. eg: 'App\\Controllers'
     * @var string
     */
    public $controllerNamespace;

    /**
     * Controller suffix, is valid when '$autoRoute' = true. eg: 'Controller'
     * @var string
     */
    public $controllerSuffix = 'Controller';

    /**
     * object creator.
     * @param array $config
     * @return self
     * @throws \LogicException
     */
    public static function create(array $config = []): AbstractRouter
    {
        return new static($config);
    }

    /**
     * object constructor.
     * @param array $config
     * @throws \LogicException
     */
    public function __construct(array $config = [])
    {
        $this->config($config);

        $this->currentGroupPrefix = '';
        $this->currentGroupOption = [];
    }

    /**
     * config the router
     * @param array $config
     * @throws \LogicException
     */
    public function config(array $config)
    {
        if ($this->routeCounter > 0) {
            throw new \LogicException('Routing has been added, and configuration is not allowed!');
        }

        $props = [
            'name' => 1,
            'chains' => 1,
            'ignoreLastSlash' => 1,
            'tmpCacheNumber' => 1,
            'handleMethodNotAllowed' => 1,
            'autoRoute' => 1,
            'controllerNamespace' => 1,
            'controllerSuffix' => 1,
        ];

        foreach ($config as $name => $value) {
            if (isset($props[$name])) {
                $this->$name = $value;
            }
        }
    }

    /*******************************************************************************
     * route collection
     ******************************************************************************/

    /**
     * register a route, allow GET request method.
     * @param string $path
     * @param $handler
     * @param array $binds path var bind.
     * @param array $opts
     * @return Route
     */
    public function get(string $path, $handler, array $binds = [], array $opts = []): Route
    {
        return $this->add('GET', $path, $handler, $binds, $opts);
        // return $this->map(['GET', 'HEAD'], $path, $handler, $binds, $opts);
    }

    /**
     * register a route, allow POST request method.
     * @param string $path
     * @param $handler
     * @param array $binds path var bind.
     * @param array $opts
     * @return Route
     */
    public function post(string $path, $handler, array $binds = [], array $opts = []): Route
    {
        return $this->add('POST', $path, $handler, $binds, $opts);
    }

    /**
     * register a route, allow PUT request method.
     * {@inheritdoc}
     */
    public function put(string $path, $handler, array $binds = [], array $opts = []): Route
    {
        return $this->add('PUT', $path, $handler, $binds, $opts);
    }

    /**
     * register a route, allow PATCH request method.
     * {@inheritdoc}
     */
    public function patch(string $path, $handler, array $binds = [], array $opts = []): Route
    {
        return $this->add('PATCH', $path, $handler, $binds, $opts);
    }

    /**
     * register a route, allow DELETE request method.
     * {@inheritdoc}
     */
    public function delete(string $path, $handler, array $binds = [], array $opts = []): Route
    {
        return $this->add('DELETE', $path, $handler, $binds, $opts);
    }

    /**
     * register a route, allow HEAD request method.
     * {@inheritdoc}
     */
    public function head(string $path, $handler, array $binds = [], array $opts = []): Route
    {
        return $this->add('HEAD', $path, $handler, $binds, $opts);
    }

    /**
     * register a route, allow OPTIONS request method.
     * {@inheritdoc}
     */
    public function options(string $path, $handler, array $binds = [], array $opts = []): Route
    {
        return $this->add('OPTIONS', $path, $handler, $binds, $opts);
    }

    /**
     * register a route, allow CONNECT request method.
     * {@inheritdoc}
     */
    public function connect(string $path, $handler, array $binds = [], array $opts = []): Route
    {
        return $this->add('CONNECT', $path, $handler, $binds, $opts);
    }

    /**
     * register a route, allow any request METHOD.
     * {@inheritdoc}
     */
    public function any(string $path, $handler, array $binds = [], array $opts = [])
    {
        $this->map(self::METHODS_ARRAY, $path, $handler, $binds, $opts);
    }

    /**
     * @param array|string $methods
     * @param string $path
     * @param callable|string $handler
     * @param array $binds
     * @param array $opts
     */
    public function map($methods, string $path, $handler, array $binds = [], array $opts = [])
    {
        foreach ((array)$methods as $method) {
            $this->add($method, $path, $handler, $binds, $opts);
        }
    }

    /**
     * Create a route group with a common prefix.
     * All routes created in the passed callback will have the given group prefix prepended.
     * @param string $prefix
     * @param \Closure $callback
     * @param array $middleware
     * @param array $opts
     */
    public function group(string $prefix, \Closure $callback, array $middleware = [], array $opts = [])
    {
        // backups
        $previousGroupPrefix = $this->currentGroupPrefix;
        $previousGroupOption = $this->currentGroupOption;
        $previousGroupChains = $this->currentGroupChains;

        $this->currentGroupOption = $opts;
        $this->currentGroupChains = $middleware;
        $this->currentGroupPrefix = $previousGroupPrefix . '/' . \trim($prefix, '/');

        // run callback.
        $callback($this);

        // reverts
        $this->currentGroupPrefix = $previousGroupPrefix;
        $this->currentGroupOption = $previousGroupOption;
        $this->currentGroupChains = $previousGroupChains;
    }

    /**
     * handle auto route match, when config `'autoRoute' => true`
     * @param string $path The route path
     * @return bool|callable
     */
    public function matchAutoRoute(string $path)
    {
        if (!$cnp = \trim($this->controllerNamespace)) {
            return false;
        }

        $sfx = \trim($this->controllerSuffix);

        return RouteHelper::parseAutoRoute($path, $cnp, $sfx);
    }

    /**
     * alias of the method: middleware()
     * @param array ...$middleware
     * @return self
     */
    public function use(...$middleware): AbstractRouter
    {
        return $this->middleware(...$middleware);
    }

    /**
     * push middleware(s) for the route
     * @param mixed ...$middleware
     * @return AbstractRouter
     */
    public function middleware(...$middleware): AbstractRouter
    {
        foreach ($middleware as $handler) {
            $this->chains[] = $handler;
        }

        return $this;
    }

    /**
     * is Static Route
     * @param string $route
     * @return bool
     */
    public static function isStaticRoute(string $route): bool
    {
        return \strpos($route, '{') === false && \strpos($route, '[') === false;
    }

    /**
     * @param array $params
     */
    public function addGlobalParams(array $params)
    {
        foreach ($params as $name => $pattern) {
            $this->addGlobalParam($name, $pattern);
        }
    }

    /**
     * @param $name
     * @param $pattern
     */
    public function addGlobalParam($name, $pattern)
    {
        $name = \trim($name, '{} ');
        self::$globalParams[$name] = $pattern;
    }

    /**
     * @return array
     */
    public static function getGlobalParams(): array
    {
        return self::$globalParams;
    }

    /**
     * @return array
     */
    public static function getSupportedMethods(): array
    {
        return self::METHODS_ARRAY;
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
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param array $staticRoutes
     */
    public function setStaticRoutes(array $staticRoutes)
    {
        $this->staticRoutes = $staticRoutes;
    }

    /**
     * @return array
     */
    public function getStaticRoutes(): array
    {
        return $this->staticRoutes;
    }

    /**
     * @param \array[] $regularRoutes
     */
    public function setRegularRoutes(array $regularRoutes)
    {
        $this->regularRoutes = $regularRoutes;
    }

    /**
     * @return \array[]
     */
    public function getRegularRoutes(): array
    {
        return $this->regularRoutes;
    }

    /**
     * @param array $vagueRoutes
     */
    public function setVagueRoutes(array $vagueRoutes)
    {
        $this->vagueRoutes = $vagueRoutes;
    }

    /**
     * @return array
     */
    public function getVagueRoutes(): array
    {
        return $this->vagueRoutes;
    }

    /**
     * @return array
     */
    public function getChains(): array
    {
        return $this->chains;
    }
}
