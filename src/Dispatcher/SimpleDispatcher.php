<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-29
 * Time: 11:10
 */

namespace Inhere\Route\Dispatcher;

use Closure;
use Inhere\Route\Helper\RouteHelper;
use Inhere\Route\Route;
use Inhere\Route\RouterInterface;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Throwable;
use function array_merge;
use function explode;
use function function_exists;
use function header;
use function implode;
use function in_array;
use function is_array;
use function is_object;
use function is_string;
use function method_exists;
use function parse_url;
use function strpos;
use function strtoupper;
use function trim;
use const PHP_URL_PATH;

/**
 * Class SimpleDispatcher
 *
 * @package Inhere\Route\Dispatcher
 */
class SimpleDispatcher implements DispatcherInterface
{
    /** @var RouterInterface */
    private $router;

    /** @var bool */
    private $initialized;

    /**
     * some setting for self
     *
     * @var array
     */
    protected $options = [
        // Filter the `/favicon.ico` request.
        'filterFavicon' => false,

        // default action method name
        'defaultAction' => 'index',

        'actionPrefix'     => '',
        'actionSuffix'     => 'Action',

        // enable dynamic action.
        // e.g
        // if set True;
        //  $router->any('/demo/{act}', App\Controllers\Demo::class);
        //  you access '/demo/test' will call 'App\Controllers\Demo::test()'
        'dynamicAction'    => false,
        // @see Router::$globalParams['act']
        'dynamicActionVar' => 'act',

        // action executor. will auto call controller's executor method to run all action.
        // e.g: 'actionExecutor' => 'run'`
        //  $router->any('/demo/{act}', App\Controllers\Demo::class);
        //  you access `/demo/test` will call `App\Controllers\Demo::run('test')`
        'actionExecutor'   => '', // 'run'

        // events: please @see DispatcherInterface::ON_*
        // 'event name'  => callback
        // SimpleDispatcher::ON_FOUND => function(){ ... },
    ];

    /**
     * Object creator.
     *
     * @param RouterInterface|null $router
     * @param array                $options
     *
     * @return self
     * @throws LogicException
     */
    public static function create(array $options = [], RouterInterface $router = null): DispatcherInterface
    {
        return new static($options, $router);
    }

    /**
     * Class constructor.
     *
     * @param RouterInterface|null $router
     * @param array                $options
     *
     * @throws LogicException
     */
    public function __construct(array $options = [], RouterInterface $router = null)
    {
        $this->initialized = false;
        $this->initOptions($options);

        if ($router) {
            $this->setRouter($router);
        }
    }

    /**
     * @param array $options
     *
     * @throws LogicException
     */
    public function initOptions(array $options): void
    {
        if ($this->initialized) {
            throw new LogicException('Has already started to distributed routing, and configuration is not allowed!');
        }

        foreach ($options as $name => $value) {
            if (isset($this->options[$name])) {
                $this->options[$name] = $value;
            } else {
                // maybe it is a event
                $this->on($name, $value);
            }
        }
    }

    /**
     * Runs the callback for the given path and method.
     *
     * @param string      $path
     * @param null|string $method
     *
     * @return mixed
     * @throws Throwable
     */
    public function dispatchUri(string $path = '', string $method = '')
    {
        $path = $path ?: $_SERVER['REQUEST_URI'];

        if (strpos($path, '?')) {
            $path = (string)parse_url($path, PHP_URL_PATH);
        }

        // if 'filterFavicon' setting is TRUE
        if ($path === self::FAV_ICON && $this->options['filterFavicon']) {
            return null;
        }

        $method = $method ?: $_SERVER['REQUEST_METHOD'];
        $method = strtoupper($method);

        /** @var Route $route */
        [$status, $path, $route] = $this->router->match($path, $method);

        return $this->dispatch($status, $path, $method, $route);
    }

    /**
     * Dispatch route handler for the given route info.
     * {@inheritdoc}
     *
     * @throws Throwable
     */
    public function dispatch(int $status, string $path, string $method, $route)
    {
        // not found
        if ($status === RouterInterface::NOT_FOUND) {
            return $this->handleNotFound($path, $method);
        }

        // method not allowed. $route is methods array.
        if ($status === RouterInterface::METHOD_NOT_ALLOWED) {
            return $this->handleNotAllowed($path, $method, $route);
        }

        return $this->doDispatch($path, $method, $route);
    }

    /**
     * @param string $path
     * @param string $method
     * @param        $route
     *
     * @return bool|mixed|null
     * @throws Throwable
     */
    protected function doDispatch(string $path, string $method, $route)
    {
        // trigger route found event
        $this->fire(self::ON_FOUND, [$path, $route]);
        $result = null;

        try {
            // trigger route exec_start event
            $this->fire(self::ON_EXEC_START, [$path, $route]);

            $result = $this->callHandler($path, $method, $route->getHandler(), $route->getParams());

            // trigger route exec_end event
            $this->fire(self::ON_EXEC_END, [$path, $route, $result]);
        } catch (Throwable $e) {
            // trigger route exec_error event
            if ($cb = $this->getOption(self::ON_EXEC_ERROR)) {
                return RouteHelper::call($cb, [$e, $path, $route]);
            }

            throw $e;
        }

        return $result;
    }

    /**
     * execute the matched Route Handler
     *
     * @param string         $path    The route path
     * @param string         $method  The request method
     * @param callable|mixed $handler The route path handler
     * @param array          $args    Matched param from path
     *                                [
     *                                'name' => value
     *                                ]
     *
     * @return mixed
     * @throws Throwable
     */
    protected function callHandler(string $path, string $method, $handler, array $args = [])
    {
        // is a \Closure or a callable object
        if (is_object($handler)) {
            return $handler($args);
        }

        //// $handler is string

        // is array ['controller', 'action']
        if (is_array($handler)) {
            $segments = $handler;
        } elseif (is_string($handler)) {
            // is function
            if (strpos($handler, '@') === false && function_exists($handler)) {
                return $handler($args);
            }

            // e.g `Controllers\Home@index` Or only `Controllers\Home`
            $segments = explode('@', trim($handler));
        } else {
            throw new InvalidArgumentException("Invalid route handler for route '$path'");
        }

        // Instantiation controller
        $controller = new $segments[0]();

        // Already assign action
        if (!empty($segments[1])) {
            $action = $segments[1];

            // use dynamic action
        } elseif ($this->options['dynamicAction'] && ($var = $this->options['dynamicActionVar'])) {
            $action = isset($args[$var]) ? trim($args[$var], '/') : $this->options['defaultAction'];

            // defined default action
        } elseif (!$action = $this->options['defaultAction']) {
            throw new RuntimeException("please config the route path [$path] controller action to call");
        }

        $action       = RouteHelper::str2Camel($action);
        $actionMethod = $action . $this->options['actionSuffix'];

        // if set the 'actionExecutor', the action handle logic by it.
        if ($executor = $this->options['actionExecutor']) {
            return $controller->$executor($actionMethod, $args);
        }

        // action method is not exist
        if (!method_exists($controller, $actionMethod)) {
            return $this->handleNotFound($path, $method, true);
        }

        // call controller's action method
        return $controller->$actionMethod($args);
    }

    /**
     * @param string $path Request uri path
     * @param string $method
     * @param bool   $actionNotExist
     *                     True: The `$path` is matched success, but action not exist on route parser
     *                     False: The `$path` is matched fail
     *
     * @return bool|mixed
     * @throws Throwable
     */
    protected function handleNotFound(string $path, string $method, $actionNotExist = false)
    {
        // Run the 'notFound' callback if the route was not found
        if (!$handler = $this->getOption(self::ON_NOT_FOUND)) {
            $handler = $this->defaultNotFoundHandler();

            $this->setOption(self::ON_NOT_FOUND, $handler);
            // is a route path. like '/site/notFound'
        } elseif (is_string($handler) && strpos($handler, '/') === 0) {
            $_GET['_src_path'] = $path;

            if ($path === $handler) {
                $defaultHandler = $this->defaultNotFoundHandler();
                return $defaultHandler($path, $method);
            }

            return $this->dispatchUri($handler, $method);
        }

        // trigger notFound event
        return RouteHelper::call($handler, [$path, $method, $actionNotExist]);
    }

    /**
     * @param string $path
     * @param string $method
     * @param array  $methods The allowed methods
     *
     * @return mixed
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    protected function handleNotAllowed(string $path, string $method, array $methods)
    {
        // Run the 'NotAllowed' callback if the route was not found
        if (!$handler = $this->getOption(self::ON_METHOD_NOT_ALLOWED)) {
            $handler = $this->defaultNotAllowedHandler();
            $this->setOption(self::ON_METHOD_NOT_ALLOWED, $handler);

            // is a route path. like '/site/notFound'
        } elseif (is_string($handler) && strpos($handler, '/') === 0) {
            $_GET['_src_path'] = $path;

            if ($path === $handler) {
                $defaultHandler = $this->defaultNotAllowedHandler();

                return $defaultHandler($path, $method, $methods);
            }

            return $this->dispatchUri($handler, $method);
        }

        // trigger methodNotAllowed event
        return RouteHelper::call($handler, [$path, $method, $methods]);
    }

    /**
     * @return Closure
     */
    protected function defaultNotFoundHandler(): Closure
    {
        return static function ($path) {
            $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
            header($protocol . ' 404 Not Found');
            echo "<h1 style='width: 80%; margin: 5% auto; text-align: center;'>:( 404<br>Page Not Found <code style='font-weight: normal;'>$path</code></h1>";
        };
    }

    /**
     * @return Closure
     */
    protected function defaultNotAllowedHandler(): Closure
    {
        return static function ($path, $method, $methods) {
            $allow    = implode(',', $methods);
            $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
            header($protocol . ' 405 Method Not Allowed');

            echo <<<HTML
<div style="min-width: 500px;margin: 5% auto;width: 80%;text-align: center;">
<h1>:( Method not allowed </h1>
<p style="font-size: 20px">Method not allowed for <strong>$method</strong> <code style="color: #d94141;font-size:  smaller;">$path</code></p>
<p>The request method must be one of <strong>$allow</strong></p>
</div>
HTML;
        };
    }

    /**
     * Defines callback on happen event
     *
     * @param          $event
     * @param callable $handler
     */
    public function on(string $event, $handler): void
    {
        if (self::isSupportedEvent($event)) {
            $this->options[$event] = $handler;
        }
    }

    /**
     * Trigger event
     *
     * @param string $event
     * @param array  $args
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function fire(string $event, array $args = [])
    {
        if (!$cb = $this->getOption($event)) {
            return null;
        }

        return RouteHelper::call($cb, $args);
    }

    /**
     * @param string $name
     * @param        $value
     */
    public function setOption(string $name, $value): void
    {
        $this->options[$name] = $value;
    }

    /**
     * @param string $name
     * @param null   $default
     *
     * @return mixed|null
     */
    public function getOption(string $name, $default = null)
    {
        return $this->options[$name] ?? $default;
    }

    /**
     * @return array
     */
    public static function getSupportedEvents(): array
    {
        return [
            self::ON_FOUND,
            self::ON_NOT_FOUND,
            self::ON_METHOD_NOT_ALLOWED,
            self::ON_EXEC_START,
            self::ON_EXEC_END,
            self::ON_EXEC_ERROR
        ];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public static function isSupportedEvent(string $name): bool
    {
        return in_array($name, static::getSupportedEvents(), true);
    }

    /**
     * @return bool
     */
    public function hasRouter(): bool
    {
        return $this->router !== null;
    }

    /**
     * @return RouterInterface
     */
    public function getRouter(): RouterInterface
    {
        return $this->router;
    }

    /**
     * @param RouterInterface $router
     */
    public function setRouter(RouterInterface $router): void
    {
        $this->router = $router;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        $this->options = array_merge($this->options, $options);
    }
}
