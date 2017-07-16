<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午8:03
 */

namespace inhere\sroute;

/**
 * Class SRouter
 * @package inhere\sroute
 *
 * @method $this get(string $route, mixed $handler)
 * @method post(string $route, mixed $handler)
 * @method put(string $route, mixed $handler)
 * @method delete(string $route, mixed $handler)
 * @method options(string $route, mixed $handler)
 * @method head(string $route, mixed $handler)
 * @method search(string $route, mixed $handler)
 * @method trace(string $route, mixed $handler)
 * @method any(string $route, mixed $handler)
 */
class SRouterOld
{
    // events
    const FOUND = 'found';
    const NOT_FOUND = 'notFound';
    const EXEC_START = 'execStart';
    const EXEC_END = 'execEnd';
    const EXEC_ERROR = 'execError';

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
    private $handlers;

    /**
     * The found route parser
     * @var callable
     */
    private static $matchedRouteParser;

    /**
     * some available patterns
     * @var array
     */
    public $patterns = [
        ':any' => '[^/]+',
        ':num' => '[0-9]+',  // match a number
        ':act' => '[a-zA-Z][\w-]+', // match a action name
        ':all' => '.*'
    ];

    /**
     * supported Methods
     * @var array
     */
    private static $supportedMethods = ['get', 'post', 'put', 'delete', 'options', 'head', 'search', 'trace', 'any'];

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

    /** @var string  */
    private $currentGroupPrefix;

    /**
     * SRouter constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->handlers = new \SplFixedArray(10);

        $this->config($config);
        $this->currentGroupPrefix = '';
    }

    /**
     * Defines a route w/ callback and method
     * @param string $method
     * @param array $args
     * @return SRouter
     */
    public function __call($method, array $args)
    {
        if (count($args) < 2) {
            throw new \InvalidArgumentException("The method [$method] parameters is required.");
        }

        return $this->map($method, trim($args[0]), $args[1]);
    }

    /**
     * @param array $config
     * @return $this
     */
    public function config(array $config)
    {
        foreach ($config as $name => $value) {
            if ($name === 'autoRoute') {
                $this->config['autoRoute'] = array_merge($this->config['autoRoute'], (array)$value);
            } elseif (isset($this->config[$name])) {
                $this->config[$name] = $value;
            }
        }

        return $this;
    }

    /**
     * @param string|array $method The match request method.
     * e.g
     *  string: 'get'
     *  array: ['get','post']
     * @param string $path The route path string. eg: '/user/login'
     * @param callable|string $handler
     * @param array|null $data
     * @return $this
     */
    public function map($method, $path, $handler, array $data = null)
    {
        // array
        if (is_array($method)) {
            foreach ((array)$method as $m) {
                $this->map($m, $path, $handler);
            }

            return $this;
        }

        // string - register route and callback

        $path = trim($path);
        $method = strtolower($method);
        $supStr = implode('|', self::$supportedMethods);

        if (false === strpos($supStr, $method)) {
            throw new \InvalidArgumentException("The method [$method] is not supported, Allow: $supStr");
        }

        if (!$handler || (!is_string($handler) && !is_object($handler))) {
            throw new \InvalidArgumentException('The route handler is not empty and type only allow: string,object');
        }

        if (is_object($handler) && !is_callable($handler)) {
            throw new \InvalidArgumentException('The route object handler must be is callable');
        }

        $s = $this->handlers->getSize();

        if (($c = $this->count()) >= $s) {
            $this->handlers->setSize($s+5);
        }

        // always add '/' prefix.
        self::$routes[] = $this->currentGroupPrefix . ($path{0} === '/' ? $path : '/' . $path);
        self::$methods[] = strtoupper($method);
        $this->handlers[$c] = $handler;

        return $this;
    }

    /**
     * Runs the callback for the given request
     * @param null|string $method
     * @param null|string $path
     * @param bool $returnMatched
     * @return mixed
     */
    public function dispatch($method = null, $path = null, $returnMatched = false)
    {
        $result = null;
        $path = $path ?: parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $method ?: $_SERVER['REQUEST_METHOD'];

        // if 'filterFavicon' setting is TRUE
        if ($path === self::MATCH_FAV_ICO && $this->config['filterFavicon']) {
            return $result;
        }

        // if enable 'matchAll'
        if ($matchAll = $this->config['matchAll']) {
            if (is_string($matchAll) && $matchAll{0} === '/') {
                $path = $matchAll;
            } elseif (is_callable($matchAll)) {
                return is_object($matchAll) ? $matchAll($path) : call_user_func($matchAll, $path);
            }
        }

        // clear '//', '///' => '/'
        $path = preg_replace('/\/\/+/', '/', $path);
        $founded = false;
        $stopOnMatch = (bool)$this->config['stopOnMatch'];

        $routes = $this->formatRoutes();

        // Check if route is defined without regex
        if ($poses = array_keys($routes, $path, true)) {
            foreach ($poses as $pos) {
                // Using an ANY option to match both GET and POST requests
                if (self::$methods[$pos] === $method || self::$methods[$pos] === self::MATCH_ANY) {
                    $founded = true;
                    $result = $this->handleMatchedRoute($path, $this->handlers[$pos]);

                    if ($stopOnMatch) {
                        break;
                    }
                }
            }

            // Check if defined with regex
        } else {
            $searches = array_keys($this->patterns);
            $replaces = array_values($this->patterns);

            foreach ($routes as $pos => $route) {
                if (strpos($route, ':') !== false) {
                    $route = str_replace($searches, $replaces, $route);
                }

                if (
                    (self::$methods[$pos] === $method || self::$methods[$pos] === self::MATCH_ANY) &&
                    preg_match('#^' . $route . '$#', $path, $matches)
                ) {
                    $founded = true;
                    $result = $this->handleMatchedRoute($path, $this->handlers[$pos], $matches);

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
        if ($handler = $this->handleAutoRoute($path)) {
            return $this->handleMatchedRoute($path, $handler);
        }

        return $this->handleNotFound($path);
    }

    /**
     * Create a route group with a common prefix.
     *
     * All routes created in the passed callback will have the given group prefix prepended.
     *
     * @from package 'nikic/fast-route'
     * @param string $prefix
     * @param \Closure $callback
     */
    public function group($prefix, \Closure $callback)
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;

        $callback($this);

        $this->currentGroupPrefix = $previousGroupPrefix;
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
     * @param string $path
     * @param callable $handler
     * @param array $matches
     * @return mixed
     */
    protected function handleMatchedRoute($path, $handler, array $matches = [])
    {
        // trigger route found event
        self::fire(self::FOUND, [$path, $handler, $matches]);

        $result = 0;

        try {
            // trigger route exec_start event
            self::fire(self::EXEC_START, [$path, $handler, $matches]);

            if (self::$matchedRouteParser) {
                $result = call_user_func(self::$matchedRouteParser, $path, $handler, $matches);

                // if not setting `$matchedRouteParser`, use default handler.
            } else {
                $result = $this->defaultMatchedRouteParser($path, $handler, $matches);
            }

            // trigger route exec_end event
            self::fire(self::EXEC_END, [$path, $handler]);
        } catch (\Exception $e) {
            // trigger route exec_error event
            self::fire(self::EXEC_ERROR, [$e, $path, $handler]);
        }

        return $result;
    }

    /**
     * handle Auto Route
     *  when config `'autoRoute' => true`
     * @param string $path The route path
     * @return bool|callable
     */
    protected function handleAutoRoute($path)
    {
        /**
         * @var array $opts
         * contains: [
         *  'controllerNamespace' => '', // controller namespace. eg: 'app\\controllers'
         *  'controllerSuffix' => '',    // controller suffix. eg: 'Controller'
         * ]
         */
        $opts = $this->config['autoRoute'];

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
    protected function handleNotFound($path, $isActionNotExist = false)
    {
        // Run the 'notFound' callback if the route was not found
        if (!isset(self::$events[self::NOT_FOUND])) {
            $notFoundHandler = function ($path) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
                echo "<h1 style='width: 60%; margin: 5% auto;'>:( 404<br>Page Not Found <code style='font-weight: normal;'>$path</code></h1>";
            };

            $this->on(self::NOT_FOUND, $notFoundHandler);
        } else {
            $notFoundHandler = self::$events[self::NOT_FOUND];

            // is a route path. like '/site/notFound'
            if (is_string($notFoundHandler) && '/' === $notFoundHandler{0}) {
                $_GET['path'] = $path;
                $_SERVER['REQUEST_URI'] = $notFoundHandler;

                unset(self::$events[self::NOT_FOUND]);
                return $this->dispatch();
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
    private function defaultMatchedRouteParser($path, $pathHandler, array $matches = [])
    {
        // Remove $matches[0] as [1] is the first parameter.
        if ($matches) {
            array_shift($matches);
        }

        // is a \Closure or a callable object
        if (is_object($pathHandler)) {
            return $matches ? $pathHandler(...$matches) : $pathHandler();
        }

        //// $pathHandler is string

        // e.g `controllers\Home@index` Or only `controllers\Home`
        $segments = explode('@', trim($pathHandler));

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
     * formatRoutes
     * @return array
     */
    protected function formatRoutes()
    {
        static $formatted;

        if (!$formatted) {
            $ignoreLastSep = (bool)$this->config['ignoreLastSep'];

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
            }, trim($str, '- '));
        }

        return $str;
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
    public function count()
    {
        return count(self::$routes);
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
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return bool
     */
    public function isStopOnMatch()
    {
        return (bool)$this->config['stopOnMatch'];
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
    protected static function fire($event, array $args = [])
    {
        if (isset(self::$events[$event]) && ($cb = self::$events[$event])) {
            return is_object($cb) ? $cb(...$args) : call_user_func_array($cb, $args);
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
    public static function supportedEvents()
    {
        return [self::FOUND, self::NOT_FOUND, self::EXEC_START, self::EXEC_END, self::EXEC_ERROR];
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
