<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-29
 * Time: 11:10
 */

namespace Inhere\Route\Dispatcher;

use Inhere\Route\ORouter;
use Inhere\Route\Base\RouterInterface;

/**
 * Class SimpleDispatcher
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
     * @var array
     */
    protected $options = [
        // Filter the `/favicon.ico` request.
        'filterFavicon' => false,

        // default action method name
        'defaultAction' => 'index',

        'actionPrefix' => '',

        'actionSuffix' => 'Action',

        // enable dynamic action.
        // e.g
        // if set True;
        //  $router->any('/demo/{act}', App\Controllers\Demo::class);
        //  you access '/demo/test' will call 'App\Controllers\Demo::test()'
        'dynamicAction' => false,
        // @see ORouter::$globalParams['act']
        'dynamicActionVar' => 'act',

        // action executor. will auto call controller's executor method to run all action.
        // e.g: 'actionExecutor' => 'run'`
        //  $router->any('/demo/{act}', App\Controllers\Demo::class);
        //  you access `/demo/test` will call `App\Controllers\Demo::run('test')`
        'actionExecutor' => '', // 'run'

        // events
    ];

    /**
     * object creator.
     * @param RouterInterface $router
     * @param array $options
     * @return self
     * @throws \LogicException
     */
    public static function make(array $options = [], RouterInterface $router = null): DispatcherInterface
    {
        return new static($options, $router);
    }

    /**
     * object constructor.
     * @param RouterInterface $router
     * @param array $options
     * @throws \LogicException
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
     * @throws \LogicException
     */
    public function initOptions(array $options)
    {
        if ($this->initialized) {
            throw new \LogicException('Has already started to distributed routing, and configuration is not allowed!');
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
     * @param string $path
     * @param null|string $method
     * @return mixed
     * @throws \Throwable
     */
    public function dispatchUri(string $path = null, string $method = null)
    {
        $path = $path ?: $_SERVER['REQUEST_URI'];

        if (\strpos($path, '?')) {
            $path =  \parse_url($path, PHP_URL_PATH);
        }

        // if 'filterFavicon' setting is TRUE
        if ($path === self::FAV_ICON && $this->options['filterFavicon']) {
            return null;
        }

        $method = $method ?: $_SERVER['REQUEST_METHOD'];

        list($status, $path, $info) = $this->router->match($path, $method);
        $info['requestMethod'] = $method;

        return $this->dispatch($status, $path, $info);
    }

    /**
     * Dispatch route handler for the given route info.
     * @param int $status
     * @param string $path
     * @param array $info
     * @return mixed
     * @throws \Throwable
     */
    public function dispatch(int $status, string $path, array $info)
    {
        $args = $info['matches'] ?? [];
        $method = $info['requestMethod'] ?? null;

        // not found
        if ($status === RouterInterface::NOT_FOUND) {
            return $this->handleNotFound($path, $method);
        }

        // method not allowed
        if ($status === RouterInterface::METHOD_NOT_ALLOWED) {
            unset($info['requestMethod']);
            return $this->handleNotAllowed($path, $method, $info);
        }

        $result = null;

        try {
            $result = $this->callRouteHandler($path, $method, $info['handler'], $args);
        } catch (\Throwable $e) {
            // trigger route exec_error event
            if ($cb = $this->getOption(self::ON_EXEC_ERROR)) {
                return $this->fireCallback($cb, [$e, $path, $info]);
            }

            throw $e;
        }

        return $result;
    }

    /**
     * execute the matched Route Handler
     * @param string $path The route path
     * @param string $method The request method
     * @param callable|mixed $handler The route path handler
     * @param array $args Matched param from path
     * [
     *  'matches' => []
     * ]
     * @return mixed
     * @throws \Throwable
     */
    protected function callRouteHandler(string $path, string $method, $handler, array $args = [])
    {
        $vars = $args['matches'];
        $args = \array_values($args);

        // is a \Closure or a callable object
        if (\is_object($handler)) {
            return $handler(...$args);
        }

        //// $handler is string

        // is array ['controller', 'action']
        if (\is_array($handler)) {
            $segments = $handler;
        } elseif (\is_string($handler)) {
            // is function
            if (\strpos($handler, '@') === false && \function_exists($handler)) {
                return $handler(...$args);
            }

            // e.g `Controllers\Home@index` Or only `Controllers\Home`
            $segments = \explode('@', \trim($handler));
        } else {
            throw new \InvalidArgumentException('Invalid route handler');
        }

        // Instantiation controller
        $controller = new $segments[0]();

        // Already assign action
        if (!empty($segments[1])) {
            $action = $segments[1];

            // use dynamic action
        } elseif ($this->options['dynamicAction'] && ($var = $this->options['dynamicActionVar'])) {
            $action = isset($vars[$var]) ? \trim($vars[$var], '/') : $this->options['defaultAction'];

            // defined default action
        } elseif (!$action = $this->options['defaultAction']) {
            throw new \RuntimeException("please config the route path [$path] controller action to call");
        }

        $action = ORouter::convertNodeStr($action);
        $actionMethod = $action . $this->options['actionSuffix'];

        // if set the 'actionExecutor', the action handle logic by it.
        if ($executor = $this->options['actionExecutor']) {
            return $controller->$executor($actionMethod, $args);
        }

        // action method is not exist
        if (!\method_exists($controller, $actionMethod)) {
            return $this->handleNotFound($path, $method, true);
        }

        // call controller's action method
        return $controller->$actionMethod(...$args);
    }

    /**
     * @param string $path Request uri path
     * @param string $method
     * @param bool $actionNotExist
     *  True: The `$path` is matched success, but action not exist on route parser
     *  False: The `$path` is matched fail
     * @return bool|mixed
     * @throws \Throwable
     */
    protected function handleNotFound(string $path, string $method, $actionNotExist = false)
    {
        // Run the 'notFound' callback if the route was not found
        if (!$handler = $this->getOption(self::ON_NOT_FOUND)) {
            $handler = $this->defaultNotFoundHandler();

            $this->setOption(self::ON_NOT_FOUND, $handler);
            // is a route path. like '/site/notFound'
        } else if (\is_string($handler) && '/' === $handler{0}) {
            $_GET['_src_path'] = $path;

            if ($path === $handler) {
                $defaultHandler = $this->defaultNotFoundHandler();

                return $defaultHandler($path, $method);
            }

            return $this->dispatchUri($handler, $method);
        }

        // trigger notFound event
        return $this->fireCallback($handler, [$path, $method, $actionNotExist]);
    }

    /**
     * @param string $path
     * @param string $method
     * @param array $methods The allowed methods
     * @return mixed
     * @throws \Throwable
     */
    protected function handleNotAllowed(string $path, string $method, array $methods)
    {
        // Run the 'NotAllowed' callback if the route was not found
        if (!$handler = $this->getOption(self::ON_METHOD_NOT_ALLOWED)) {
            $handler = $this->defaultNotAllowedHandler();
            $this->setOption(self::ON_METHOD_NOT_ALLOWED, $handler);

            // is a route path. like '/site/notFound'
        } elseif (\is_string($handler) && '/' === $handler{0}) {
            $_GET['_src_path'] = $path;

            if ($path === $handler) {
                $defaultHandler = $this->defaultNotAllowedHandler();

                return $defaultHandler($path, $method, $methods);
            }

            return $this->dispatchUri($handler, $method);
        }

        // trigger methodNotAllowed event
        return $this->fireCallback($handler, [$path, $method, $methods]);
    }

    /**
     * @return \Closure
     */
    protected function defaultNotFoundHandler(): \Closure
    {
        return function ($path) {
            $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
            \header($protocol . ' 404 Not Found');
            echo "<h1 style='width: 80%; margin: 5% auto; text-align: center;'>:( 404<br>Page Not Found <code style='font-weight: normal;'>$path</code></h1>";
        };
    }

    /**
     * @return \Closure
     */
    protected function defaultNotAllowedHandler(): \Closure
    {
        return function ($path, $method, $methods) {
            $allow = \implode(',', $methods);
            $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
            \header($protocol . ' 405 Method Not Allowed');

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
     * @param $event
     * @param callable $handler
     */
    public function on(string $event, $handler)
    {
        if (self::isSupportedEvent($event)) {
            $this->options[$event] = $handler;
        }
    }

    /**
     * @param callable|mixed $cb
     * string - func name, class name
     * array - [class, method]
     * object - Closure, Object
     *
     * @param array $args
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function fireCallback($cb, array $args = [])
    {
        if (!$cb) {
            return true;
        }

        if (\is_array($cb)) {
            list($obj, $mhd) = $cb;

            return \is_object($obj) ? $obj->$mhd(...$args) : $obj::$mhd(...$args);
        }

        if (\is_string($cb)) {
            if (\function_exists($cb)) {
                return $cb(...$args);
            }

            // a class name
            if (\class_exists($cb)) {
                $cb = new $cb;
            }
        }

        // a \Closure or Object implement '__invoke'
        if (\is_object($cb) && \method_exists($cb, '__invoke')) {
            return $cb(...$args);
        }

        throw new \InvalidArgumentException('the callback handler is not callable!');
    }

    /**
     * @param string $name
     * @param $value
     */
    public function setOption(string $name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * @param string $name
     * @param null $default
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
     * @return bool
     */
    public static function isSupportedEvent(string $name): bool
    {
        return \in_array($name, static::getSupportedEvents(), true);
    }

    /**
     * @return RouterInterface|null
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @param RouterInterface $router
     * @return SimpleDispatcher
     */
    public function setRouter(RouterInterface $router): SimpleDispatcher
    {
        $this->router = $router;

        return $this;
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
    public function setOptions(array $options)
    {
        $this->options = $options;
    }
}
