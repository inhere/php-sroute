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
 * @method static get(string $route, callable $callback)
 * @method static post(string $route, callable $callback)
 * @method static put(string $route, callable $callback)
 * @method static delete(string $route, callable $callback)
 * @method static options(string $route, callable $callback)
 * @method static head(string $route, callable $callback)
 * @method static any(string $route, callable $callback)
 */
class SRoute
{
    // events
    const FOUND       = 'found';
    const NOT_FOUND   = 'notFound';

    const MATCH_ANY = 'ANY';
    const MATCH_FAV_ICO = '/favicon.ico';

    /**
     * @var array
     */
    private static $routes = [];

    /**
     * @var array
     */
    private static $methods = [];

    /**
     * @var array
     */
    private static $callbacks = [];

    /**
     * @var callable
     */
    private static $foundHandler;

    /**
     * some patterns
     * @var array
     */
    public static $patterns = [
        ':any' => '[^/]+',
        ':num' => '[0-9]+',
        ':all' => '.*'
    ];

    /**
     * event handlers
     * @var array[]
     */
    private static $_eventCallbacks = [];

    /**
     * some setting for self
     * @var array
     */
    private static $_config = [
        // stop On Matched. only match one
        'stopOnMatch'  => true,

        // Filter the `/favicon.ico` request.
        'filterFavicon' => false,

        // ignore last '/' char. If is true, will clear last '/'.
        'ignoreLastSep' => false,

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
        // $uri = dirname($_SERVER['PHP_SELF']).'/'.$params[0];
        $uri = trim($args[0]);

        if ( !isset($args[1]) ) {
            throw new \LogicException('Please setting a callback for the URI: ' . $uri);
        }

        $callback = $args[1];

        self::$routes[]    = '/' . trim($uri, '/ ');
        self::$methods[]   = strtoupper($method);
        self::$callbacks[] = $callback;
    }

    /**
     * Runs the callback for the given request
     */
    public static function dispatch()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        // if 'filterFavicon' setting is TRUE
        if (self::$_config['filterFavicon'] && $uri === self::MATCH_FAV_ICO ) {
            return true;
        }

        // if not setting $foundHandler, use default handler.
        if ( !self::$foundHandler ) {
            self::setFoundHandler([self::class, 'defaultFoundHandler']);
        }

        $foundRoute = false;
        $searches = array_keys(static::$patterns);
        $replaces = array_values(static::$patterns);

        $ignoreLastSep = (bool)self::$_config['ignoreLastSep'];

        /** @var array $routes */
        $routes = array_map(function($route) use($ignoreLastSep) {
            $route = preg_replace('/\/+/', '/', $route);

            return $ignoreLastSep && $route !== '/' ? rtrim($route, '/') : $route;
        }, self::$routes);

        // Check if route is defined without regex
        if ( in_array($uri, $routes, true) ) {
            $routePos = array_keys($routes, $uri);

            foreach ($routePos as $index) {
                // Using an ANY option to match both GET and POST requests
                if (self::$methods[$index] === $method || self::$methods[$index] === self::MATCH_ANY) {
                    $foundRoute = true;
                    $callback = self::$callbacks[$index];

                    // trigger route found event
                    self::fire(self::FOUND, [ $uri, $callback, null ]);

                    // handle the found route
                    $ret = call_user_func(self::$foundHandler, $uri, $callback, []);

                    if ( true === $ret ) {
                        break;
                    }
                }
            }
        } else {
            // Check if defined with regex
            $pos = 0;
            foreach ($routes as $route) {
                if ( strpos($route, ':') !== false ) {
                    $route = str_replace($searches, $replaces, $route);
                }

                if (
                    (self::$methods[$pos] === $method || self::$methods[$pos] === self::MATCH_ANY) &&
                    preg_match('#^' . $route . '$#', $uri, $matched)
                ) {
                    $foundRoute = true;
                    $callback = self::$callbacks[$pos];

                    // trigger route found event
                    self::fire(self::FOUND, [ $uri, $callback, $matched ]);

                    $ret = call_user_func(self::$foundHandler, $uri, $callback, $matched);

                    // trigger route found event
                    if ( true === $ret ) {
                        break;
                    }
                }

                $pos++;
            }
        }

        if ( $foundRoute ) {
            return true;
        }

        return self::notFoundHandler($uri, false);
    }

    /**
     * @param string $uri
     * @param callable $uriHandler
     * @param array $matched Matched param from uri
     * @return bool
     */
    public static function defaultFoundHandler($uri, $uriHandler, array $matched = [])
    {
        if ( $matched ) {
            // Remove $matched[0] as [1] is the first parameter.
            array_shift($matched);
        }

        // is a \Closure
        if ( is_object($uriHandler) ) {
            $matched ? call_user_func_array($uriHandler, $matched) : $uriHandler();
        } elseif ( is_string($uriHandler) ) {
            // e.g `controllers\Home@index` Or only `controllers\Home`
            $segments = explode('@', trim($uriHandler));

            // Instantiation controller
            $controller = new $segments[0]();
            $dynamicAction = (bool)self::$_config['dynamicAction'];

            // Already assign action
            if ( isset($segments[1]) ) {
                $action = $segments[1];
            // use dynamic action
            } elseif ($dynamicAction) {
                $action = isset($matched[0]) ? trim($matched[0], '/') : '';
            } else {
                throw new \RuntimeException("please config the uri [$uri] controller action to call");
            }

            // if set the 'actionExecutor', the action handle logic by it.
            if ($executor = self::$_config['actionExecutor']) {
                $controller->$executor($action, $matched);
            } elseif( !method_exists($controller, $action) ) {
                // echo "controller and action not found";
                self::notFoundHandler($uri, true);
            } else {
                $matched ? call_user_func_array([ $controller, $action], $matched) : $controller->$action();
            }
        } else {
            throw new \InvalidArgumentException('The uri callback handler only allow type is: object|string');
        }

        if ( self::$_config['stopOnMatch'] ) {
            return false;
        }

        return true;
    }

    /**
     * @param $uri
     * @param bool $isAction
     * @return bool|mixed
     */
    protected static function notFoundHandler($uri, $isAction = false)
    {
        // Run the error callback if the route was not found
        if ( !isset(self::$_eventCallbacks[self::NOT_FOUND]) ) {
            $notFoundHandler = function($uri) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
                echo "<h2>404 Not found: $uri</h2>";
            };

            self::on(self::NOT_FOUND, $notFoundHandler);
        } else {
            $notFoundHandler = self::$_eventCallbacks[self::NOT_FOUND];

            // is a route url. like '/site/notFound'
            if ( is_string($notFoundHandler) && '/' === $notFoundHandler{0} ) {
                $_GET['uri'] = $uri;
                $_SERVER['REQUEST_URI'] = $notFoundHandler;

                unset(self::$_eventCallbacks[self::NOT_FOUND]);
                return self::dispatch();
            }
        }

        // trigger not found event
        return $notFoundHandler($uri, $isAction);
    }

    /**
     * Set the route founded handler
     * @param callable $foundHandler
     */
    public static function setFoundHandler(callable $foundHandler)
    {
        self::$foundHandler = $foundHandler;
    }

    /**
     * @param array $settings
     */
    public static function config(array $settings)
    {
        foreach ($settings as $name => $value) {
            if ( isset(self::$_config[$name]) ) {
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
        if ( self::isSupportedEvent($event) ) {
            self::$_eventCallbacks[$event] = $handler;
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
        if ( isset(self::$_eventCallbacks[$event])) {
            $cb = self::$_eventCallbacks[$event];

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
        return isset(self::$_eventCallbacks[$event]);
    }

    /**
     * @return array
     */
    public static function supportedEvents()
    {
        return [ self::FOUND, self::NOT_FOUND ];
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
