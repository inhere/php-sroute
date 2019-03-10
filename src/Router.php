<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午8:03
 */

namespace Inhere\Route;

use Inhere\Route\Dispatcher\Dispatcher;
use Inhere\Route\Dispatcher\DispatcherInterface;
use Inhere\Route\Helper\RouteHelper;

/**
 * Class Router - This is object version
 * @package Inhere\Route
 */
class Router implements RouterInterface
{
    use RouterConfigTrait;

    /** @var int */
    protected $routeCounter = 0;

    /** @var callable[] Router middleware handler chains */
    private $chains = [];

    /** @var Route */
    private $basicRoute;

    // -- Group info

    /** @var string */
    protected $currentGroupPrefix;
    /** @var array */
    protected $currentGroupOption = [];
    /** @var array */
    protected $currentGroupChains = [];

    // -- Routes data

    /**
     * name routes. use for find a route by name.
     * @var array [name => Route]
     */
    protected $namedRoutes = [];

    /**
     * static Routes - no dynamic argument match
     * 整个路由 path 都是静态字符串 e.g. '/user/login'
     * @var Route[]
     * [
     *     'GET /user/login' =>  Route,
     *     'POST /user/login' =>  Route,
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
     *      ],
     *     'blog' => [
     *        Route, // '/blog/post-{id}'
     *     ],
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
     * ]
     */
    protected $vagueRoutes = [];

    /**
     * object creator.
     * @param array $config
     * @return self
     * @throws \LogicException
     */
    public static function create(array $config = []): Router
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
        $this->basicRoute = new Route('GET', '/', null);

        $this->currentGroupPrefix = '';
        $this->currentGroupOption = [];
    }

    /*******************************************************************************
     * router middleware
     ******************************************************************************/

    /**
     * alias of the method: middleware()
     * @param array ...$middleware
     * @return self
     */
    public function use(...$middleware): Router
    {
        return $this->middleware(...$middleware);
    }

    /**
     * push middleware(s) for the route
     * @param mixed ...$middleware
     * @return Router
     */
    public function middleware(...$middleware): Router
    {
        foreach ($middleware as $handler) {
            $this->chains[] = $handler;
        }

        return $this;
    }

    /*******************************************************************************
     * route register
     ******************************************************************************/

    /**
     * register a route, allow GET request method.
     * {@inheritdoc}
     */
    public function get(string $path, $handler, array $pathParams = [], array $opts = []): Route
    {
        return $this->add('GET', $path, $handler, $pathParams, $opts);
        // return $this->map(['GET', 'HEAD'], $path, $handler, $pathParams, $opts);
    }

    /**
     * register a route, allow POST request method.
     * {@inheritdoc}
     */
    public function post(string $path, $handler, array $pathParams = [], array $opts = []): Route
    {
        return $this->add('POST', $path, $handler, $pathParams, $opts);
    }

    /**
     * register a route, allow PUT request method.
     * {@inheritdoc}
     */
    public function put(string $path, $handler, array $pathParams = [], array $opts = []): Route
    {
        return $this->add('PUT', $path, $handler, $pathParams, $opts);
    }

    /**
     * register a route, allow PATCH request method.
     * {@inheritdoc}
     */
    public function patch(string $path, $handler, array $pathParams = [], array $opts = []): Route
    {
        return $this->add('PATCH', $path, $handler, $pathParams, $opts);
    }

    /**
     * register a route, allow DELETE request method.
     * {@inheritdoc}
     */
    public function delete(string $path, $handler, array $pathParams = [], array $opts = []): Route
    {
        return $this->add('DELETE', $path, $handler, $pathParams, $opts);
    }

    /**
     * register a route, allow HEAD request method.
     * {@inheritdoc}
     */
    public function head(string $path, $handler, array $pathParams = [], array $opts = []): Route
    {
        return $this->add('HEAD', $path, $handler, $pathParams, $opts);
    }

    /**
     * register a route, allow OPTIONS request method.
     * {@inheritdoc}
     */
    public function options(string $path, $handler, array $pathParams = [], array $opts = []): Route
    {
        return $this->add('OPTIONS', $path, $handler, $pathParams, $opts);
    }

    /**
     * register a route, allow CONNECT request method.
     * {@inheritdoc}
     */
    public function connect(string $path, $handler, array $pathParams = [], array $opts = []): Route
    {
        return $this->add('CONNECT', $path, $handler, $pathParams, $opts);
    }

    /**
     * register a route, allow any request METHOD.
     * {@inheritdoc}
     */
    public function any(string $path, $handler, array $pathParams = [], array $opts = []): void
    {
        $this->map(self::METHODS_ARRAY, $path, $handler, $pathParams, $opts);
    }

    /**
     * @param array|string    $methods
     * @param string          $path
     * @param callable|string $handler
     * @param array           $pathParams
     * @param array           $opts
     */
    public function map($methods, string $path, $handler, array $pathParams = [], array $opts = [])
    {
        foreach ((array)$methods as $method) {
            $this->add($method, $path, $handler, $pathParams, $opts);
        }
    }

    /**
     * @param string $method
     * @param string $path
     * @param        $handler
     * @param array  $pathParams
     * @param array  $opts
     * @return Route
     */
    public function add(string $method, string $path, $handler, array $pathParams = [], array $opts = []): Route
    {
        if (!$method || !$handler) {
            throw new \InvalidArgumentException('The method and route handler is not allow empty.');
        }

        $route  = $this->cloneRoute();
        $method = \strtoupper($method);
        if ($method === 'ANY') {
            $this->any($path, $handler, $pathParams, $opts);
            return $route; // Only use for return type
        }

        if (false === \strpos(self::METHODS_STRING, ',' . $method . ',')) {
            throw new \InvalidArgumentException(
                "The method [$method] is not supported, Allow: " . \trim(self::METHODS_STRING, ',')
            );
        }

        // Initialize Route
        $route->initialize($method, $path, $handler, $pathParams, $opts);

        return $this->addRoute($route);
    }

    /**
     * @param Route $route
     * @return Route
     */
    public function addRoute(Route $route): Route
    {
        $this->routeCounter++;
        $this->appendGroupInfo($route);

        $path   = $route->getPath();
        $method = $route->getMethod();

        // Has route name.
        if ($name = $route->getName()) {
            $this->namedRoutes[$name] = $route;
        }

        // It is static route
        $argPos = \strpos($path, '{');
        $optPos = \strpos($path, '[');
        if ($argPos === false && $optPos === false) {
            $this->staticRoutes[$method . ' ' . $path] = $route;
            return $route;
        }

        // Parse param route
        // - If the first node is static string.
        $globalParams = self::$globalParams;
        if ($first = $route->quickParseParams($argPos, $optPos, $globalParams)) {
            $this->regularRoutes[$method . ' ' . $first][] = $route;
        } else {
            $this->vagueRoutes[$method][] = $route;
        }

        return $route;
    }

    /**
     * Create a route group with a common prefix.
     * All routes created in the passed callback will have the given group prefix prepended.
     * @param string   $prefix
     * @param \Closure $callback
     * @param array    $middleware
     * @param array    $opts
     */
    public function group(string $prefix, \Closure $callback, array $middleware = [], array $opts = []): void
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
     * prepare for add
     * @param Route $route
     * @return void
     */
    protected function appendGroupInfo(Route $route): void
    {
        $path = $route->getPath();

        // always add '/' prefix.
        $path = \strpos($path, '/') === 0 ? $path : '/' . $path;
        $path = $this->currentGroupPrefix . $path;

        // setting 'ignoreLastSlash'
        if ($path !== '/' && $this->ignoreLastSlash) {
            $path = \rtrim($path, '/');
        }

        $route->setPath($path);

        if ($grpOptions = $this->currentGroupOption) {
            $route->setOptions(\array_merge($grpOptions, $route->getOptions()));
        }

        if ($grpChains = $this->currentGroupChains) {
            // prepend group middleware at before.
            $route->setChains(\array_merge($grpChains, $route->getChains()));
        }
    }

    /*******************************************************************************
     * route match
     ******************************************************************************/

    /**
     * find the matched route info for the given request uri path
     * @param string $method
     * @param string $path
     * @return array returns array.
     * [
     *  match status, // found, not found, method not allowed
     *  formatted path,
     *  (Route object) OR (allowed methods list)
     * ]
     */
    public function match(string $path, string $method = 'GET'): array
    {
        $path   = RouteHelper::formatPath($path, $this->ignoreLastSlash);
        $method = \strtoupper($method);
        $sKey   = $method . ' ' . $path;

        // It is a static route path
        if (isset($this->staticRoutes[$sKey])) {
            return [self::FOUND, $path, $this->staticRoutes[$sKey]];
        }

        // It is a dynamic route, match by regexp
        $result = $this->matchDynamicRoute($path, $method);
        if ($result[0] === self::FOUND) {
            return $result;
        }

        // handle Auto Route. always return new Route object.
        if ($this->autoRoute && ($handler = $this->matchAutoRoute($path))) {
            return [self::FOUND, $path, Route::create($method, $path, $handler)];
        }

        // For HEAD requests, attempt fallback to GET
        if ($method === 'HEAD') {
            $sKey = 'GET ' . $path;
            if (isset($this->staticRoutes[$sKey])) {
                return [self::FOUND, $path, $this->staticRoutes[$sKey]];
            }

            $result = $this->matchDynamicRoute($path, 'GET');
            if ($result[0] === self::FOUND) {
                return $result;
            }
        }

        // If nothing else matches, try fallback routes. $router->any('*', 'handler');
        $sKey = $method . ' /*';
        if (isset($this->staticRoutes[$sKey])) {
            return [self::FOUND, $path, $this->staticRoutes[$sKey]];
        }

        // Collect allowed methods from: staticRoutes, vagueRoutes OR return not found.
        if ($this->handleMethodNotAllowed) {
            return $this->findAllowedMethods($path, $method);
        }

        return [self::NOT_FOUND, $path, null];
    }

    /**
     * is a dynamic route, match by regexp
     * @param string $path
     * @param string $method
     * @return array
     * [
     *  status,
     *  path,
     *  Route(object) -> it's a raw Route clone.
     * ]
     */
    protected function matchDynamicRoute(string $path, string $method): array
    {
        $fKey = $first = '';
        if ($pos = \strpos($path, '/', 1)) {
            $first = \substr($path, 1, $pos - 1);
            $fKey  = $method . ' ' . $first;
        }

        // It is a regular dynamic route(the first node is 1th level index key).
        if ($fKey && $routeList = $this->regularRoutes[$fKey] ?? false) {
            /** @var Route $route */
            foreach ($routeList as $route) {
                // Check path start string
                $pathStart = $route->getPathStart();
                if (\strpos($path, $pathStart) !== 0) {
                    continue;
                }

                $result = $route->matchRegex($path);
                if ($result[0]) {
                    return [self::FOUND, $path, $route->copyWithParams($result[1])];
                }
            }
        }

        // It is a irregular dynamic route
        if ($routeList = $this->vagueRoutes[$method] ?? false) {
            foreach ($routeList as $route) {
                $result = $route->matchRegex($path);
                if ($result[0]) {
                    return [self::FOUND, $path, $route->copyWithParams($result[1])];
                }
            }
        }

        return [self::NOT_FOUND, $path, null];
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
     * @param string $path
     * @param string $method
     * @return array
     */
    protected function findAllowedMethods(string $path, string $method): array
    {
        $methods = [];
        foreach (self::METHODS_ARRAY as $m) {
            if ($method === $m) {
                continue;
            }

            $sKey = $m . ' ' . $path;
            if (isset($this->staticRoutes[$sKey])) {
                $methods[$m] = 1;
                continue;
            }

            $result = $this->matchDynamicRoute($path, $m);
            if ($result[0] === self::FOUND) {
                $methods[$m] = 1;
            }
        }

        if ($methods) {
            return [self::METHOD_NOT_ALLOWED, $path, \array_keys($methods)];
        }
        return [self::NOT_FOUND, $path, null];
    }

    /*******************************************************************************
     * route dispatch
     ******************************************************************************/

    /**
     * Runs the callback for the given request
     * @param DispatcherInterface|array $dispatcher
     * @param null|string               $path
     * @param null|string               $method
     * @return mixed
     * @throws \LogicException
     * @throws \Throwable
     */
    public function dispatch($dispatcher = null, $path = null, $method = null)
    {
        if (!$dispatcher) {
            $dispatcher = new Dispatcher;
        } elseif (\is_array($dispatcher)) {
            $dispatcher = new Dispatcher($dispatcher);
        }

        if (!$dispatcher instanceof DispatcherInterface) {
            throw new \InvalidArgumentException(
                'The first argument is must an array OR an object instanceof the DispatcherInterface'
            );
        }

        if (!$dispatcher->hasRouter()) {
            $dispatcher->setRouter($this);
        }

        return $dispatcher->dispatchUri($path, $method);
    }

    /*******************************************************************************
     * helper methods
     ******************************************************************************/

    /**
     * @param string $name Route name
     * @param array  $pathVars
     * @return string
     */
    public function createUri(string $name, array $pathVars = []): string
    {
        if ($route = $this->getRoute($name)) {
            return $route->toUri($pathVars);
        }

        return '';
    }

    /**
     * @param string $name
     * @param Route  $route
     */
    public function nameRoute(string $name, Route $route): void
    {
        if ($name = \trim($name)) {
            $this->namedRoutes[$name] = $route;
        }
    }

    /**
     * get a name route by given name.
     * @param string $name
     * @return Route|null
     */
    public function getRoute(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->routeCounter;
    }

    /**
     * @param \Closure $func
     */
    public function each(\Closure $func): void
    {
        /** @var Route $route */
        foreach ($this->staticRoutes as $route) {
            $func($route);
        }

        foreach ($this->regularRoutes as $routes) {
            foreach ($routes as $route) {
                $func($route);
            }
        }

        foreach ($this->vagueRoutes as $routes) {
            foreach ($routes as $route) {
                $func($route);
            }
        }
    }

    /**
     * get all routes
     * @return array
     */
    public function getRoutes(): array
    {
        $routes = [];
        $this->each(function (Route $route) use (&$routes) {
            $routes[] = $route;
        });

        return $routes;
    }

    /**
     * @return array
     */
    public function getChains(): array
    {
        return $this->chains;
    }

    protected function cloneRoute(): Route
    {
        return clone $this->basicRoute;
    }

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->getRoutes());
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $indent    = '  ';
        $strings   = ['#Routes Number: ' . $this->count()];
        $strings[] = "\n#Static Routes:";
        /** @var Route $route */
        foreach ($this->staticRoutes as $route) {
            $strings[] = $indent . $route->toString();
        }

        $strings[] = "\n# Regular Routes:";
        foreach ($this->regularRoutes as $routes) {
            foreach ($routes as $route) {
                $strings[] = $indent . $route->toString();
            }
        }

        $strings[] = "\n# Vague Routes:";
        foreach ($this->vagueRoutes as $routes) {
            foreach ($routes as $route) {
                $strings[] = $indent . $route->toString();
            }
        }

        return \implode("\n", $strings);
    }
}
