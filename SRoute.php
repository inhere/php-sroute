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
 * @package micro
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
    private static $routeParser;

    /**
     * some available patterns
     * @var array
     */
    public static $patterns = [
        ':any' => '[^/]+',
        ':num' => '[0-9]+',
        ':action' => '[a-zA-Z][\w-]+',
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

        // If is True, will auto find the handler controller file. @like yii framework
        'autoRoute' => false,

        // The default controllers namespace, if valid when `'autoRoute' => true`
        'controllerNamespace' => '', // 'app\\controllers'

        // enable dynamic action.
        // e.g
        // if set True;
        //  SRoute::any('/demo/(\w+)', app\controllers\Demo::class);
        //  you access '/demo/test' will call 'app\controllers\Demo::test()'
        'dynamicAction' => false,

        // action executor. will auto call controller's executor method to run all action.
        // e.g
        //  `run($action)`
        //  SRoute::any('/demo/(?<act>\w+)', app\controllers\Demo::class);
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
        $method = strtolower($method);
        $supStr = implode('|', self::$supportedMethods);

        // if (!in_array($method, self::$supportedMethods, true)) {
        if (false === strpos($supStr, $method)) {
            throw new \InvalidArgumentException("The method [$method] is not supported, Allow: $supStr");
        }

        $path = trim($path);

        if (!self::$handlers) {
            self::$handlers = new \SplFixedArray(5);
        }

        $c = self::count();
        $s = self::$handlers->getSize();

        if ($c >= $s) {
            self::$handlers->setSize(++$s);
        }

        self::$routes[]   = '/' . ltrim($path, '/ ');
        self::$methods[]  = strtoupper($method);
        self::$handlers[$c] = $handler;

        return true;
    }

    /**
     * Runs the callback for the given request
     * @return bool
     */
    public static function dispatch()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        // if 'filterFavicon' setting is TRUE
        if (self::$_config['filterFavicon'] && $path === self::MATCH_FAV_ICO) {
            return true;
        }

        // if not setting $routeParser, use default handler.
        if (!self::$routeParser) {
            self::setRouteParser([self::class, 'defaultRouteParser']);
        }

        $foundRoute = false;
        $stopOnMatch = self::$_config['stopOnMatch'];
        $ignoreLastSep = (bool)self::$_config['ignoreLastSep'];

        /** @var array $routes */
        $routes = array_map(function ($route) use ($ignoreLastSep) {
            $route = preg_replace('/\/+/', '/', $route);

            return $ignoreLastSep && $route !== '/' ? rtrim($route, '/') : $route;
        }, self::$routes);

        // Check if route is defined without regex
        if (in_array($path, $routes, true)) {
            $poses = array_keys($routes, $path);

            foreach ($poses as $pos) {
                // Using an ANY option to match both GET and POST requests
                if (self::$methods[$pos] === $method || self::$methods[$pos] === self::MATCH_ANY) {
                    $foundRoute = true;
                    $handler = self::$handlers[$pos];

                    // trigger route found event
                    self::fire(self::FOUND, [$path, $handler, null]);

                    // handle the found route
                    call_user_func(self::$routeParser, $path, $handler, []);

                    if ($stopOnMatch) {
                        break;
                    }
                }
            }

        // Check if defined with regex
        } else {
            $pos = 0;
            $searches = array_keys(self::$patterns);
            $replaces = array_values(self::$patterns);

            foreach ($routes as $route) {
                if (strpos($route, ':') !== false) {
                    $route = str_replace($searches, $replaces, $route);
                }

                if (
                    (self::$methods[$pos] === $method || self::$methods[$pos] === self::MATCH_ANY) &&
                    preg_match('#^' . $route . '$#', $path, $matched)
                ) {
                    $foundRoute = true;
                    $handler = self::$handlers[$pos];

                    // trigger route found event
                    self::fire(self::FOUND, [$path, $handler, $matched]);
                    call_user_func(self::$routeParser, $path, $handler, $matched);

                    if ($stopOnMatch) {
                        break;
                    }
                }

                $pos++;
            }
        }

        if ($foundRoute) {
            return true;
        }

        if (self::$_config['autoRoute'] && ($cnp = self::$_config['controllerNamespace'])) {
            self::handleAutoRoute($path, $cnp);
        } else {
            self::handleNotFound($path, false);
        }

        return true;
    }

    /**
     * the default match route parser.
     * @param string   $path        The route path
     * @param callable $pathHandler The route path handler
     * @param array    $matched     Matched param from path
     */
    public static function defaultRouteParser($path, $pathHandler, array $matched = [])
    {
        // Remove $matched[0] as [1] is the first parameter.
        if ($matched) {
            array_shift($matched);
        }

        // is a \Closure
        if (is_object($pathHandler)) {
            $matched ? call_user_func_array($pathHandler, $matched) : $pathHandler();
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
                $action = isset($matched[0]) ? trim($matched[0], '/') : '';
            } else {
                throw new \RuntimeException("please config the route path [$path] controller action to call");
            }

            // if set the 'actionExecutor', the action handle logic by it.
            if ($executor = self::$_config['actionExecutor']) {
                $controller->$executor($action, $matched);
            } elseif (!$action || !method_exists($controller, $action)) {
                self::handleNotFound($path, true);
            } else {
                $matched ? call_user_func_array([$controller, $action], $matched) : $controller->$action();
            }
        } else {
            throw new \InvalidArgumentException('The route path handler only allow type is: object|string');
        }
    }

    /**
     * handle Auto Route
     *  when config `'autoRoute' => true`
     * @param string $path The route path
     * @param string $cnp  The controllers namespace
     */
    protected static function handleAutoRoute($path, $cnp)
    {
        $path = trim($path, '/ ');
        $ary  = explode('/', $path);

        $class = $cnp . '\\' . str_replace('/', '\\', $path);
        // todo ...
    }

    /**
     * @param $path
     * @param bool $isAction
     * @return bool|mixed
     */
    protected static function handleNotFound($path, $isAction = false)
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
        return call_user_func($notFoundHandler, $path, $isAction);
    }

    /**
     * Set the route founded handler
     * @param callable $routeParser
     */
    public static function setRouteParser(callable $routeParser)
    {
        self::$routeParser = $routeParser;
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
            if (isset(self::$_config[$name])) {
                self::$_config[$name] = $value;
            }
        }
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
        if (isset(self::$_events[$event])) {
            $cb = self::$_events[$event];

            return call_user_func_array($cb, $args);
        }

        return null;
    }

    /**
     * @param $event
     * @return bool
     */
    public static function hasHandler($event)
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
