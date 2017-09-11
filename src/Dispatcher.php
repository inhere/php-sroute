<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午8:03
 */

namespace Inhere\Route;

/**
 * Class Dispatcher
 * @package Inhere\Route
 */
class Dispatcher implements DispatcherInterface
{
    const MATCH_FAV_ICO = '/favicon.ico';

    /**
     * event handlers
     * @var array
     */
    private static $events = [];

    /**
     * some setting for self
     * @var array
     */
    private $config = [
        // Filter the `/favicon.ico` request.
        'filterFavicon' => false,

        // default action method name
        'defaultAction' => 'index',

        // enable dynamic action.
        // e.g
        // if set True;
        //  $router->any('/demo/{act}', app\controllers\Demo::class);
        //  you access '/demo/test' will call 'app\controllers\Demo::test()'
        'dynamicAction' => false,

        // action executor. will auto call controller's executor method to run all action.
        // e.g: 'actionExecutor' => 'run'`
        //  $router->any('/demo/{act}', app\controllers\Demo::class);
        //  you access `/demo/test` will call `app\controllers\Demo::run('test')`
        'actionExecutor' => '', // 'run'
    ];

    /** @var \Closure */
    private $matcher;

    /** @var bool */
    private $initialized;

    /**
     * object creator.
     * @param \Closure $matcher
     * @param array $config
     * @return self
     * @throws \LogicException
     */
    public static function make(array $config = [], \Closure $matcher = null)
    {
        return new self($config, $matcher);
    }

    /**
     * object constructor.
     * @param \Closure $matcher
     * @param array $config
     * @throws \LogicException
     */
    public function __construct(array $config = [], \Closure $matcher = null)
    {
        $this->initialized = false;
        $this->setConfig($config);

        if ($matcher) {
            $this->setMatcher($matcher);
        }
    }

    /**
     * @param array $config
     * @throws \LogicException
     */
    public function setConfig(array $config)
    {
        if ($this->initialized) {
            throw new \LogicException('Has already started to distributed routing, and configuration is not allowed!');
        }

        foreach ($config as $name => $value) {
            if (isset($this->config[$name])) {
                $this->config[$name] = $value;
            } else {
                // maybe it is a event
                $this->on($name, $value);
            }
        }
    }

//////////////////////////////////////////////////////////////////////
/// route callback handler dispatch
//////////////////////////////////////////////////////////////////////

    /**
     * Runs the callback for the given request
     * @param string $path
     * @param null|string $method
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function dispatch($path = null, $method = null)
    {
        $path = $path ?: parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // if 'filterFavicon' setting is TRUE
        if ($path === self::MATCH_FAV_ICO && $this->config['filterFavicon']) {
            return null;
        }

        if (!$matcher = $this->matcher) {
            throw new \RuntimeException('Must be setting the property [matcher] before call dispatch().');
        }

        $this->initialized = true;
        $method = $method ?: $_SERVER['REQUEST_METHOD'];

        if (!$info = $matcher($path, $method)) {
            return $this->handleNotFound($path, $method);
        }

        list($path, $route) = $info;

        // trigger route found event
        $this->fire(self::ON_FOUND, [$path, $route]);

        $result = null;
        $options = isset($route['option']) ? $route['option'] : [];
        unset($route['option']);

        // schema,domains ... metadata validate
        if (false === $this->validateMetadata($route)) {
            return $result;
        }

        // fire enter event
        if (isset($options['enter']) && false === $this->fireCallback($options['enter'], [$path])) {
            return $result;
        }

        $handler = $route['handler'];
        $args = isset($route['matches']) ? $route['matches'] : [];

        // Remove matches[0] as [1] is the first parameter.
        if ($args) {
            array_shift($args);
            $args = array_values($args);
        }

        try {
            // trigger route exec_start event
            $this->fire(self::ON_EXEC_START, [$path, $route]);

            $result = $this->executeRouteHandler($path, $method, $handler, $args);

            // fire leave event
            if (isset($options['leave'])) {
                $this->fireCallback($options['leave'], [$path]);
            }

            // trigger route exec_end event
            $this->fire(self::ON_EXEC_END, [$path, $route]);
        } catch (\Exception $e) {
            // trigger route exec_error event
            $this->fire(self::ON_EXEC_ERROR, [$e, $path, $route]);
        } catch (\Throwable $e) {
            // trigger route exec_error event
            $this->fire(self::ON_EXEC_ERROR, [$e, $path, $route]);
        }

        return $result;
    }

    /**
     * @param array $route
     * [
     *     'domains'  => [ 'a-domain.com', '*.b-domain.com'],
     *     'schema' => 'https',
     * ]
     */
    protected function validateMetadata(array $route)
    {
        // 1. validate Schema

        // 2. validate validateDomains
        // $serverName = $_SERVER['SERVER_NAME'];

        // 3. more something ...
    }

    /**
     * execute the matched Route Handler
     * @param string $path The route path
     * @param string $method The request method
     * @param callable $handler The route path handler
     * @param array $args Matched param from path
     * @return mixed
     */
    protected function executeRouteHandler($path, $method, $handler, array $args = [])
    {
        // is a \Closure or a callable object
        if (is_object($handler)) {
            return $args ? $handler(...$args) : $handler();
        }

        //// $handler is string

        // is array ['controller', 'action']
        if (is_array($handler)) {
            $segments = $handler;
        } elseif (is_string($handler)) {
            if (strpos($handler, '@') === false && function_exists($handler)) {
                return $args ? $handler(...$args) : $handler();
            }

            // e.g `controllers\Home@index` Or only `controllers\Home`
            $segments = explode('@', trim($handler));
        } else {
            throw new \InvalidArgumentException('Invalid route handler');
        }

        // Instantiation controller
        $controller = new $segments[0]();

        // Already assign action
        if (isset($segments[1])) {
            $action = $segments[1];

            // use dynamic action
        } elseif ((bool)$this->config['dynamicAction']) {
            $action = isset($args[0]) ? trim($args[0], '/') : $this->config['defaultAction'];

            // defined default action
        } elseif (!$action = $this->config['defaultAction']) {
            throw new \RuntimeException("please config the route path [$path] controller action to call");
        }

        $action = ORouter::convertNodeStr($action);

        // if set the 'actionExecutor', the action handle logic by it.
        if ($executor = $this->config['actionExecutor']) {
            return $controller->$executor($action, $args);
        }

        // action method is not exist
        if (!$action || !method_exists($controller, $action)) {
            return $this->handleNotFound($path, $method, true);
        }

        // call controller's action method
        return $args ? $controller->$action(...$args) : $controller->$action();
    }

    /**
     * @param string $path Request uri path
     * @param string $method
     * @param bool $actionNotExist
     *  True: The `$path` is matched success, but action not exist on route parser
     *  False: The `$path` is matched fail
     * @return bool|mixed
     */
    private function handleNotFound($path, $method, $actionNotExist = false)
    {
        // Run the 'notFound' callback if the route was not found
        if (!isset(self::$events[self::ON_NOT_FOUND])) {
            $notFoundHandler = $this->defaultNotFoundHandler();

            $this->on(self::ON_NOT_FOUND, $notFoundHandler);
        } else {
            $notFoundHandler = self::$events[self::ON_NOT_FOUND];

            // is a route path. like '/site/notFound'
            if (is_string($notFoundHandler) && '/' === $notFoundHandler{0}) {
                $_GET['_src_path'] = $path;

                if ($path === $notFoundHandler) {
                    unset(self::$events[self::ON_NOT_FOUND]);
                }

                return $this->dispatch($notFoundHandler, $method);
            }
        }

        // trigger notFound event
        return $this->fireCallback($notFoundHandler, [$path, $method, $actionNotExist]);
    }

    /**
     * @return \Closure
     */
    protected function defaultNotFoundHandler()
    {
        return function ($path) {
            $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';

            header($protocol . ' 404 Not Found');
            echo "<h1 style='width: 60%; margin: 5% auto;'>:( 404<br>Page Not Found <code style='font-weight: normal;'>$path</code></h1>";
        };
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Defines callback on happen event
     * @param $event
     * @param callable $handler
     */
    public function on($event, $handler)
    {
        if (self::isSupportedEvent($event)) {
            self::$events[$event] = $handler;
        }
    }

    /**
     * Trigger event
     * @param $event
     * @param array $args
     * @return mixed
     */
    protected function fire($event, array $args = [])
    {
        if (isset(self::$events[$event]) && ($cb = self::$events[$event])) {
            return $this->fireCallback($cb, $args);
        }

        return null;
    }

    /**
     * @param callable $cb
     * string - func name, class name
     * array - [class, method]
     * object - Closure, Object
     *
     * @param array $args
     * @return mixed
     */
    protected function fireCallback($cb, array $args = [])
    {
        if (!$cb) {
            return true;
        }

        if (is_array($cb)) {
            // return call_user_func($cb, $path);
            list($obj, $mhd) = $cb;

            return is_object($obj) ? $obj->$mhd(...$args) : $obj::$mhd(...$args);
        }

        if (is_string($cb)) {
            if (function_exists($cb)) {
                return $cb(...$args);
            }

            // a class name
            if (class_exists($cb)) {
                $cb = new $cb;
            }
        }

        // a \Closure or Object implement '__invoke'
        if (is_object($cb) && method_exists($cb, '__invoke')) {
            return $cb(...$args);
        }

        throw new \InvalidArgumentException('the callback handler is not callable!');
    }

    /**
     * @param $event
     * @return bool
     */
    public static function hasEventHandler($event)
    {
        return isset(self::$events[$event]);
    }

    /**
     * @return array
     */
    public static function getSupportedEvents()
    {
        return [self::ON_FOUND, self::ON_NOT_FOUND, self::ON_EXEC_START, self::ON_EXEC_END, self::ON_EXEC_ERROR];
    }

    /**
     * @param $name
     * @return array
     */
    public static function isSupportedEvent($name)
    {
        return in_array($name, static::getSupportedEvents(), true);
    }

    /**
     * @return \Closure
     */
    public function getMatcher()
    {
        return $this->matcher;
    }

    /**
     * @param \Closure $matcher
     * @return $this
     */
    public function setMatcher(\Closure $matcher)
    {
        $this->matcher = $matcher;

        return $this;
    }
}
