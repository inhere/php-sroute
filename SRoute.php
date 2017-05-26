<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-02
 * Time: 10:44
 */

namespace inhere\sroute;

/**
 * Class SRoute
 * Simple Router
 * @package inhere\sroute
 *
 * @method static get(string $route, mixed $handler)
 * @method static post(string $route, mixed $handler)
 * @method static put(string $route, mixed $handler)
 * @method static delete(string $route, mixed $handler)
 * @method static options(string $route, mixed $handler)
 * @method static head(string $route, mixed $handler)
 * @method static any(string $route, mixed $handler)
 */
class SRoute
{
    // events
    const FOUND = 'found';
    const HANDLE_START = 'HANDLE_START';
    const HANDLE_END = 'handleEnd';
    const NOT_FOUND = 'notFound';

    const MATCH_ANY = 'ANY';
    const MATCH_FAV_ICO = '/favicon.ico';

    /**
     * There are route path or regex pattern string list
     * @var array
     */
    private static $routes = [];

    /**
     * There are registered request method list
     * @var array
     */
    private static $methods = [];

    /**
     * There are route handler list
     * @var \SplFixedArray
     */
    private static $handlers;

    /**
     * The found route parser
     * @var callable
     */
    private static $matchedRouteParser;

    /**
     * some available patterns
     * @var array
     */
    public static $patterns = [
        ':any' => '[^/]+',
        ':num' => '[0-9]+',  // match a number
        ':act' => '[a-zA-Z][\w-]+', // match a action name
        ':all' => '.*'
    ];

    /**
     * supported Methods
     * @var array
     */
    private static $supportedMethods = ['get', 'post', 'put', 'delete', 'options', 'head', 'any'];

    /**
     * event handlers
     * @var array[]
     */
    private static $_events = [];

    /**
     * some setting for self
     * @var array
     */
    private static $_config = [
        // stop on matched. only match one
        'stopOnMatch' => true,
        // Filter the `/favicon.ico` request.
        'filterFavicon' => false,
        // ignore last '/' char. If is True, will clear last '/'.
        'ignoreLastSep' => false,

        // match all request.
        // 1. If is a valid URI path, will match all request uri to the path.
        // 2. If is a closure, will match all request then call it
        // eg: '/site/maintenance' or `function () { echo 'System Maintaining ... ...'; }`
        'matchAll' => '',

        // auto route match @like yii framework
        'autoRoute' => [
            // If is True, will auto find the handler controller file.
            'enable' => false,
            // The default controllers namespace, is valid when `'enable' = true`
            'controllerNamespace' => '', // eg: 'app\\controllers'
            // controller suffix, is valid when `'enable' = true`
            'controllerSuffix' => '',    // eg: 'Controller'
        ],

        // default action method name
        'defaultAction' => 'index',

        // enable dynamic action.
        // e.g
        // if set True;
        //  SRoute::any('/demo/(\w+)', app\controllers\Demo::class);
        //  you access '/demo/test' will call 'app\controllers\Demo::test()'
        'dynamicAction' => false,

        // action executor. will auto call controller's executor method to run all action.
        // e.g: 'actionExecutor' => 'run'`
        //  SRoute::any('/demo/(:act)', app\controllers\Demo::class);
        //  you access `/demo/test` will call `app\controllers\Demo::run('test')`
        'actionExecutor' => '', // 'run'
    ];

    /**
     * Defines a route w/ callback and method
     * @param string $method
     * @param array $args
     */
    public static function __callStatic($method, array $args)
    {
        if (!$args) {
            throw new \InvalidArgumentException("The method [$method] parameters is required.");
        }

        // $uri = dirname($_SERVER['PHP_SELF']).'/'.$params[0];
        $path = trim($args[0]);

        if (!isset($args[1])) {
            throw new \LogicException("Please setting a callback for the route path: $path");
        }

        self::map($method, $path, $args[1]);
    }

    /**
     * @param string|array      $method The match request method.
     * e.g
     *  string: 'get'
     *  array: ['get','post']
     * @param string            $path  The route path string. eg: '/user/login'
     * @param callable|string   $handler
     * @return bool
     */
    public static function map($method, $path, $handler)
    {
        // array
        if (is_array($method)) {
            foreach ($method as $m) {
                self::map($m, $path, $handler);
            }

            return true;
        }

        // string - register route and callback

        $path   = trim($path);
        $method = strtolower($method);
        $supStr = implode('|', self::$supportedMethods);

        if (false === strpos($supStr, $method)) {
            throw new \InvalidArgumentException("The method [$method] is not supported, Allow: $supStr");
        }

        if (!$handler || (!is_string($handler) && !is_object($handler))) {
            throw new \InvalidArgumentException('The route handler is not empty and only Allow: string,object');
        }

        if (is_object($handler) && !is_callable($handler)) {
            throw new \InvalidArgumentException('The route object handler must be is callable');
        }

        if (!self::$handlers) {
            self::$handlers = new \SplFixedArray(5);
        }

        $s = self::$handlers->getSize();

        if (($c = self::count()) >= $s) {
            self::$handlers->setSize(++$s);
        }

        // always add '/' prefix.
        self::$routes[]   = $path{0} === '/' ? $path : "/$path";
        self::$methods[]  = strtoupper($method);
        self::$handlers[$c] = $handler;

        return true;
    }

    /**
     * Runs the callback for the given request
     * @return mixed
     */
    public static function dispatch()
    {
        $result = null;
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // if 'filterFavicon' setting is TRUE
        if (self::$_config['filterFavicon'] && $path === self::MATCH_FAV_ICO) {
            return $result;
        }

        // if enable 'matchAll'
        if ($matchAll = self::$_config['matchAll']) {
            if (is_string($matchAll) && $matchAll{0} === '/') {
                $path = $matchAll;
            } elseif (is_callable($matchAll)) {
                return call_user_func($matchAll, $path);
            }
        }

        // clear '//', '///' => '/'
        $path = preg_replace('/\/\/+/', '/', $path);
        $founded = false;
        $method  = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $stopOnMatch = (bool)self::$_config['stopOnMatch'];

        $routes = self::formatRoutes();

        // Check if route is defined without regex
        if ($poses = array_keys($routes, $path, true)) {
            foreach ($poses as $pos) {
                // Using an ANY option to match both GET and POST requests
                if (self::$methods[$pos] === $method || self::$methods[$pos] === self::MATCH_ANY) {
                    $founded = true;
                    $result = self::handleMatchedRoute($path, self::$handlers[$pos]);

                    if ($stopOnMatch) {
                        break;
                    }
                }
            }

        // Check if defined with regex
        } else {
            $searches = array_keys(self::$patterns);
            $replaces = array_values(self::$patterns);

            foreach ($routes as $pos => $route) {
                if (strpos($route, ':') !== false) {
                    $route = str_replace($searches, $replaces, $route);
                }

                if (
                    (self::$methods[$pos] === $method || self::$methods[$pos] === self::MATCH_ANY) &&
                    preg_match('#^' . $route . '$#', $path, $matches)
                ) {
                    $founded = true;
                    $handler = self::$handlers[$pos];
                    $result = self::handleMatchedRoute($path, $handler, $matches);

                    if ($stopOnMatch) {
                        break;
                    }
                }
            }
        }

        if ($founded) {
            return $result;
        }

        // handle Auto Route
        if ($handler = self::handleAutoRoute($path)) {
            return self::handleMatchedRoute($path, $handler);
        }

        return self::handleNotFound($path, false);
    }

    /**
     * manual dispatch a URI route
     * @param string $uri
     * @param string $method
     * @param bool $receiveReturn
     * @return null|string
     */
    public static function dispatchTo($uri, $method = 'GET', $receiveReturn = true)
    {
        $result = null;

        // store old value
        $oldUri = $_SERVER['REQUEST_URI'];
        $oldMtd = $_SERVER['REQUEST_METHOD'];

        // override and dispatch
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['REQUEST_METHOD'] = $method ? strtoupper($method) : 'GET';

        if ($receiveReturn) {
            ob_start();
            self::dispatch();
            $result = ob_get_clean();
        } else {
            self::dispatch();
        }

        // restore old value
        $_SERVER['REQUEST_URI'] = $oldUri;
        $_SERVER['REQUEST_METHOD'] = $oldMtd;

        return $result;
    }

    /**
     * @param string $path
     * @param callable $handler
     * @param array $matches
     * @return mixed
     */
    protected static function handleMatchedRoute($path, $handler, array $matches = [])
    {
        // trigger route found event
        self::fire(self::FOUND, [$path, $handler, $matches]);

        // trigger route handle_start event
        self::fire(self::HANDLE_START, [$path, $handler, $matches]);

        if (self::$matchedRouteParser) {
            $result = call_user_func(self::$matchedRouteParser, $path, $handler, $matches);

        // if not setting `$matchedRouteParser`, use default handler.
        } else {
            $result = self::defaultMatchedRouteParser($path, $handler, $matches);
        }

        // trigger route handle_end event
        self::fire(self::HANDLE_END, [$path, $handler, null]);

        return $result;
    }

    /**
     * handle Auto Route
     *  when config `'autoRoute' => true`
     * @param string $path The route path
     * @return bool|callable
     */
    protected static function handleAutoRoute($path)
    {
        /**
         * @var array $opts
         * contains: [
         *  'controllerNamespace' => '', // controller namespace. eg: 'app\\controllers'
         *  'controllerSuffix' => '',    // controller suffix. eg: 'Controller'
         * ]
         */
        $opts = self::$_config['autoRoute'];

        // not enabled
        if (!$opts || !isset($opts['enable']) || !$opts['enable']) {
            return false;
        }

        $cnp = $opts['controllerNamespace'];
        $sfx = $opts['controllerSuffix'];
        $tmp = trim($path, '/- ');

        // one node. eg: 'home'
        if (!strpos($tmp, '/')) {
            $tmp = self::convertNodeStr($tmp);
            $class = "$cnp\\" . ucfirst($tmp) . $sfx;

            return class_exists($class) ? $class : false;
        }

        $ary = array_map([self::class, 'convertNodeStr'], explode('/', $tmp));
        $cnt = count($ary);

        // two nodes. eg: 'home/test' 'admin/user'
        if ($cnt === 2) {
            list($n1, $n2) = $ary;

            // last node is an controller class name. eg: 'admin/user'
            $class = "$cnp\\$n1\\" . ucfirst($n2) . $sfx;

            if (class_exists($class)) {
                return $class;
            }

            // first node is an controller class name, second node is a action name,
            $class = "$cnp\\" . ucfirst($n1) . $sfx;

            return class_exists($class) ? "$class@$n2" : false;
        }

        // max allow 5 nodes
        if ($cnt > 5) {
            return false;
        }

        // last node is an controller class name
        $n2 = array_pop($ary);
        $class = sprintf('%s\\%s\\%s', $cnp, implode('\\', $ary), ucfirst($n2) . $sfx);

        if (class_exists($class)) {
            return $class;
        }

        // last second is an controller class name, last node is a action name,
        $n1 = array_pop($ary);
        $class = sprintf('%s\\%s\\%s', $cnp, implode('\\', $ary), ucfirst($n1) . $sfx);

        return class_exists($class) ? "$class@$n2" : false;
    }

    /**
     * @param string $path Request uri path
     * @param bool $isActionNotExist
     *  True: The `$path` is matched success, but action not exist on route parser
     *  False: The `$path` is matched fail
     * @return bool|mixed
     */
    protected static function handleNotFound($path, $isActionNotExist = false)
    {
        // Run the 'notFound' callback if the route was not found
        if (!isset(self::$_events[self::NOT_FOUND])) {
            $notFoundHandler = function ($path) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
                echo "<h1 style='width: 60%; margin: 5% auto;'>:( 404<br>Page Not Found <code>$path</code></h1>";
            };

            self::on(self::NOT_FOUND, $notFoundHandler);
        } else {
            $notFoundHandler = self::$_events[self::NOT_FOUND];

            // is a route path. like '/site/notFound'
            if (is_string($notFoundHandler) && '/' === $notFoundHandler{0}) {
                $_GET['path'] = $path;
                $_SERVER['REQUEST_URI'] = $notFoundHandler;

                unset(self::$_events[self::NOT_FOUND]);
                return self::dispatch();
            }
        }

        // trigger notFound event
        return call_user_func($notFoundHandler, $path, $isActionNotExist);
    }

    /**
     * the default matched route parser.
     * @param string $path The route path
     * @param callable $pathHandler The route path handler
     * @param array $matches Matched param from path
     * @return mixed
     */
    protected static function defaultMatchedRouteParser($path, $pathHandler, array $matches = [])
    {
        // Remove $matches[0] as [1] is the first parameter.
        if ($matches) {
            array_shift($matches);
        }

        // is a \Closure or a callable object
        if (is_object($pathHandler) && is_callable($pathHandler)) {
            return $matches ? call_user_func_array($pathHandler, $matches) : $pathHandler();
        } elseif (is_string($pathHandler)) {
            // e.g `controllers\Home@index` Or only `controllers\Home`
            $segments = explode('@', trim($pathHandler));

            // Instantiation controller
            $controller = new $segments[0]();

            // Already assign action
            if (isset($segments[1])) {
                $action = $segments[1];

                // use dynamic action
            } elseif ((bool)self::$_config['dynamicAction']) {
                $action = isset($matches[0]) ? trim($matches[0], '/') : self::$_config['defaultAction'];

                // defined default action
            } elseif (!$action = self::$_config['defaultAction']) {
                throw new \RuntimeException("please config the route path [$path] controller action to call");
            }

            // if set the 'actionExecutor', the action handle logic by it.
            if ($executor = self::$_config['actionExecutor']) {
                return $controller->$executor($action, $matches);

                // action method is not exist
            } elseif (!$action || !method_exists($controller, $action)) {
                return self::handleNotFound($path, true);

                // call controller's action method
            } else {
                return $matches ? call_user_func_array([$controller, $action], $matches) : $controller->$action();
            }
        } else {
            throw new \InvalidArgumentException('The route path handler only allow type is: object|string');
        }
    }

    /**
     * formatRoutes
     * @return array
     */
    protected static function formatRoutes()
    {
        static $formatted;

        if (!$formatted) {
            $ignoreLastSep = (bool)self::$_config['ignoreLastSep'];

            self::$routes = array_map(function ($route) use ($ignoreLastSep) {
                $route = preg_replace('/\/\/+/', '/', $route);

                return $ignoreLastSep && $route !== '/' ? rtrim($route, '/') : $route;
            }, self::$routes);

            $formatted = true;
        }

        return self::$routes;
    }

    /**
     * convert 'first-second' to 'firstSecond'
     * @param $str
     * @return mixed|string
     */
    protected static function convertNodeStr($str)
    {
        $str = trim($str, '-');

        // convert 'first-second' to 'firstSecond'
        if (strpos($str, '-')) {
            $str = preg_replace_callback('/-+([a-z])/', function ($c) {
                return strtoupper($c[1]);
            }, $str);
        }

        return str_replace('-', '', $str);
    }

    /**
     * Set the matched route handler
     * @param callable $parser
     */
    public static function setMatchedRouteParser(callable $parser)
    {
        self::$matchedRouteParser = $parser;
    }

    /**
     * @return int
     */
    public static function count()
    {
        return count(self::$routes);
    }

    /**
     * @param array $settings
     */
    public static function config(array $settings)
    {
        foreach ($settings as $name => $value) {
            if ($name === 'autoRoute') {
                self::$_config['autoRoute'] = array_merge(self::$_config['autoRoute'], (array)$value);
            } elseif (isset(self::$_config[$name])) {
                self::$_config[$name] = $value;
            }
        }
    }

    /**
     * @return array
     */
    public static function getRoutes()
    {
        return self::$routes;
    }

    /**
     * @return array
     */
    public static function getSupportedMethods()
    {
        return self::$supportedMethods;
    }

    /**
     * @return array
     */
    public static function getConfig()
    {
        return self::$_config;
    }

    /**
     * @return bool
     */
    public static function isStopOnMatch()
    {
        return (bool)self::$_config['stopOnMatch'];
    }

    /**
     * Defines callback on happen event
     * @param $event
     * @param callable $handler
     */
    public static function on($event, $handler)
    {
        if (self::isSupportedEvent($event)) {
            self::$_events[$event] = $handler;
        }
    }

    /**
     * Trigger event
     * @param $event
     * @param array $args
     * @return mixed
     */
    protected static function fire($event, array $args = [])
    {
        if (isset(self::$_events[$event]) && ($cb = self::$_events[$event])) {
            return call_user_func_array($cb, $args);
        }

        return null;
    }

    /**
     * @param $event
     * @return bool
     */
    public static function hasEventHandler($event)
    {
        return isset(self::$_events[$event]);
    }

    /**
     * @return array
     */
    public static function supportedEvents()
    {
        return [self::FOUND, self::NOT_FOUND];
    }

    /**
     * @param $name
     * @return array
     */
    public static function isSupportedEvent($name)
    {
        return in_array($name, static::supportedEvents(), true);
    }
}
