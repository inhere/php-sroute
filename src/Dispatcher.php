<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午8:03
 */

namespace inhere\sroute;

/**
 * Class Dispatcher
 * @package inhere\sroute
 *
 */
class Dispatcher
{
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

    /**
     *
     * Runs the callback for the given request
     * @param null $path
     * @param null $method
     * @return mixed
     */
    public function dispatch($path = null, $method = null)
    {
        $result = null;
        $path = $path ?: parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // if 'filterFavicon' setting is TRUE
        if ($path === self::MATCH_FAV_ICO && $this->config['filterFavicon']) {
            return $result;
        }

        $method = $method ?: $_SERVER['REQUEST_METHOD'];

        // handle Auto Route
        if ($data = $this->match($path, $method)) {
            list($path, $conf) = $data;

            // trigger route found event
            $this->fire(self::FOUND, [$path, $conf]);

            $handler = $conf['handler'];
            $matches = isset($conf['matches']) ? $conf['matches'] : null;

            try {
                // trigger route exec_start event
                $this->fire(self::EXEC_START, [$path, $conf]);

                $result = $this->callMatchedRouteHandler($path, $handler, $matches);

                // trigger route exec_end event
                $this->fire(self::EXEC_END, [$path, $conf]);
            } catch (\Exception $e) {
                // trigger route exec_error event
                $this->fire(self::EXEC_ERROR, [$e, $path, $conf]);
            }

            return $result;
        }

        return $this->handleNotFound($path);
    }

    /**
     * manual dispatch a URI route
     * @param string $uri
     * @param string $method
     * @param bool $receiveReturn
     * @return null|string
     */
    public function dispatchTo($uri, $method = 'GET', $receiveReturn = true)
    {
        $result = null;

        if ($receiveReturn) {
            ob_start();
            $this->dispatch($uri, $method);
            $result = ob_get_clean();
        } else {
            $result = $this->dispatch($uri, $method);
        }

        return $result;
    }

    /**
     * @param string $path Request uri path
     * @param bool $isActionNotExist
     *  True: The `$path` is matched success, but action not exist on route parser
     *  False: The `$path` is matched fail
     * @return bool|mixed
     */
    private function handleNotFound($path, $isActionNotExist = false)
    {
        // Run the 'notFound' callback if the route was not found
        if (!isset(self::$events[self::NOT_FOUND])) {
            $notFoundHandler = function ($path, $isActionNotExist) {
                 header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
                 echo "<h1 style='width: 60%; margin: 5% auto;'>:( 404<br>Page Not Found <code style='font-weight: normal;'>$path</code></h1>";
            };

            $this->on(self::NOT_FOUND, $notFoundHandler);
        } else {
            $notFoundHandler = self::$events[self::NOT_FOUND];

            // is a route path. like '/site/notFound'
            if (is_string($notFoundHandler) && '/' === $notFoundHandler{0}) {
                $_GET['_src_path'] = $path;

                unset(self::$events[self::NOT_FOUND]);
                return $this->dispatch($notFoundHandler);
            }
        }

        // trigger notFound event
        return is_array($notFoundHandler) ?
            call_user_func($notFoundHandler, $path, $isActionNotExist) :
            $notFoundHandler($path, $isActionNotExist);
    }

    /**
     * the default matched route parser.
     * @param string $path The route path
     * @param callable $handler The route path handler
     * @param array $matches Matched param from path
     * @return mixed
     * @throws \RuntimeException
     */
    private function callMatchedRouteHandler($path, $handler, array $matches = null)
    {
        // Remove $matches[0] as [1] is the first parameter.
        if ($matches) {
            array_shift($matches);
        }

        // is a \Closure or a callable object
        if (is_object($handler)) {
            return $matches ? $handler(...$matches) : $handler();
        }

        //// $handler is string

        // e.g `controllers\Home@index` Or only `controllers\Home`
        $segments = explode('@', trim($handler));

        // Instantiation controller
        $controller = new $segments[0]();

        // Already assign action
        if (isset($segments[1])) {
            $action = $segments[1];

            // use dynamic action
        } elseif ((bool)$this->config['dynamicAction']) {
            $action = isset($matches[0]) ? trim($matches[0], '/') : $this->config['defaultAction'];

            // defined default action
        } elseif (!$action = $this->config['defaultAction']) {
            throw new \RuntimeException("please config the route path [$path] controller action to call");
        }

        $action = self::convertNodeStr($action);

        // if set the 'actionExecutor', the action handle logic by it.
        if ($executor = $this->config['actionExecutor']) {
            return $controller->$executor($action, $matches);
        }

        // action method is not exist
        if (!$action || !method_exists($controller, $action)) {
            return $this->handleNotFound($path, true);
        }

        // call controller's action method
        return $matches ? $controller->$action(...$matches) : $controller->$action();
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
            return !is_array($cb) ? $cb(...$args) : call_user_func_array($cb, $args);
        }

        return null;
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
        return [self::FOUND, self::NOT_FOUND, self::EXEC_START, self::EXEC_END, self::EXEC_ERROR];
    }

    /**
     * @param $name
     * @return array
     */
    public static function isSupportedEvent($name)
    {
        return in_array($name, static::getSupportedEvents(), true);
    }
}
