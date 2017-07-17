<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午8:03
 */

namespace inhere\sroute;

/**
 * Class ORouter- this is object version
 * @package inhere\sroute
 *
 * @method get(string $route, mixed $handler, array $opts = [])
 * @method post(string $route, mixed $handler, array $opts = [])
 * @method put(string $route, mixed $handler, array $opts = [])
 * @method delete(string $route, mixed $handler, array $opts = [])
 * @method options(string $route, mixed $handler, array $opts = [])
 * @method head(string $route, mixed $handler, array $opts = [])
 * @method search(string $route, mixed $handler, array $opts = [])
 * @method trace(string $route, mixed $handler, array $opts = [])
 * @method any(string $route, mixed $handler, array $opts = [])
 */
class ORouter implements RouterInterface
{
    private $routeCounter = 0;

    /**
     * some available patterns regex
     * $router->get('/user/{num}', 'handler');
     * @var array
     */
    private static $globalTokens = [
        'any' => '[^/]+',   // match any except '/'
        'num' => '[0-9]+',  // match a number
        'act' => '[a-zA-Z][\w-]+', // match a action name
        'all' => '.*'
    ];

    /**
     * supported Methods
     * @var array
     */
    private static $supportedMethods = [
        'ANY',
        'GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'HEAD', 'SEARCH', 'CONNECT', 'TRACE',
    ];

    /**
     * event handlers
     * @var array
     */
    private static $events = [];

    /** @var string  */
    private $currentGroupPrefix;

    /** @var array  */
    private $currentGroupOption;

    /** @var bool  */
    private $initialized = false;

    /**
     * static Routes - no dynamic argument match
     * 整个路由 path 都是静态字符串
     * @var array
     * [
     *     '/user/login' => [
     *         'GET' => [
     *              'handler' => 'handler',
     *              'option' => null,
     *          ],
     *         'POST' => [
     *              'handler' => 'handler',
     *              'option' => null,
     *          ],
     *          ...
     *      ]
     * ]
     */
    private $staticRoutes = [];

    /**
     * regular Routes - have dynamic arguments, but the first node is normal.
     * 第一节是个静态字符串，称之为有规律的动态路由。按第一节的信息进行存储
     *
     * @var array[]
     * [
     *     // 先用第一个字符作为 key，进行分组
     *     'a' => [
     *          // 第一节只有一个字符, 使用关键字'__NO__'为 key 进行分组
     *         '__NO__' => [
     *              [
     *                  'first' => '/a',
     *                  'regex' => '/a/(\w+)',
     *                  'method' => 'GET',
     *                  'handler' => 'handler',
     *                  'option' => null,
     *              ]
     *          ],
     *          // 第一节有多个字符, 使用第二个字符 为 key 进行分组
     *         'd' => [
     *              [
     *                  'first' => '/add',
     *                  'regex' => '/add/(\w+)',
     *                  'method' => 'GET',
     *                  'handler' => 'handler',
     *                  'option' => null,
     *              ],
     *              ... ...
     *          ],
     *          ... ...
     *      ],
     *     'b' => [
     *        'l' => [
     *              [
     *                  'first' => '/blog',
     *                  'regex' => '/blog/(\w+)',
     *                  'method' => 'GET',
     *                  'handler' => 'handler',
     *                  'option' => null,
     *              ],
     *              ... ...
     *          ],
     *          ... ...
     *     ],
     * ]
     */
    private $regularRoutes = [];

    /**
     * vague Routes - have dynamic arguments,but the first node is exists regex.
     *
     * 第一节就包含了正则匹配，称之为无规律/模糊的动态路由
     *
     * @var array
     * [
     *     [
     *         'regex' => '/(\w+)/some',
     *         'method' => 'GET',
     *         'handler' => 'handler',
     *         'option' => null,
     *     ],
     *      ... ...
     * ]
     */
    private $vagueRoutes = [];

    /**
     * There are last route caches
     * @var array
     * [
     *     'path' => [
     *         'GET' => [
     *              'handler' => 'handler',
     *              'option' => null,
     *          ],
     *         'POST' => [
     *              'handler' => 'handler',
     *              'option' => null,
     *          ],
     *         ... ...
     *     ]
     * ]
     */
    private $routeCaches = [];

    /**
     * some setting for self
     * @var array
     */
    private $config = [
        // Filter the `/favicon.ico` request.
        'filterFavicon' => false,
        // ignore last '/' char. If is True, will clear last '/'.
        'ignoreLastSep' => false,

        // 'tmpCacheNumber' => 100,
        'tmpCacheNumber' => 0,

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
     * ORouter creator.
     * @param array $config
     * @return ORouter
     * @throws \LogicException
     */
    public static function create(array $config = [])
    {
        return new self($config);
    }

    /**
     * ORouter constructor.
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
     * @param array $config
     * @throws \LogicException
     */
    public function config(array $config)
    {
        if ($this->initialized) {
            throw new \LogicException('Routing has been added, and configuration is not allowed!');
        }

        foreach ($config as $name => $value) {
            if ($name === 'autoRoute') {
                $this->config['autoRoute'] = array_merge($this->config['autoRoute'], (array)$value);
            } elseif (isset($this->config[$name])) {
                $this->config[$name] = $value;
            }
        }
    }

//////////////////////////////////////////////////////////////////////
/// route collection
//////////////////////////////////////////////////////////////////////

    /**
     * Defines a route callback and method
     * @param string $method
     * @param array $args
     * @return ORouter
     * @throws \InvalidArgumentException
     */
    public function __call($method, array $args)
    {
        if (count($args) < 2) {
            throw new \InvalidArgumentException("The method [$method] parameters is missing.");
        }

        return $this->map($method, $args[0], $args[1], isset($args[2]) ? $args[2] : []);
    }

    /**
     * Create a route group with a common prefix.
     * All routes created in the passed callback will have the given group prefix prepended.
     *
     * @from package 'nikic/fast-route'
     * @param string $prefix
     * @param \Closure $callback
     * @param array $opts
     */
    public function group($prefix, \Closure $callback, array $opts = [])
    {
        $prefix = '/' . trim($prefix, '/');
        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;

        $previousGroupOption = $this->currentGroupOption;
        $this->currentGroupOption = $opts;

        $callback($this);

        $this->currentGroupPrefix = $previousGroupPrefix;
        $this->currentGroupOption = $previousGroupOption;
    }

    /**
     * @param string|array $method The match request method.
     * e.g
     *  string: 'get'
     *  array: ['get','post']
     * @param string $route The route path string. eg: '/user/login'
     * @param callable|string $handler
     * @param array $opts some option data
     * [
     *     'tokens' => [ 'id' => '[0-9]+', ],
     *     'domains'  => [ 'a-domain.com', '*.b-domain.com'],
     *     'schema' => 'https',
     * ]
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function map($method, $route, $handler, array $opts = [])
    {
        if (!$this->initialized) {
            $this->initialized = true;
        }

        // array
        if (is_array($method)) {
            foreach ((array)$method as $m) {
                $this->map($m, $route, $handler, $opts);
            }

            return $this;
        }

        // string - register route and callback

        $method = strtoupper($method);
        $hasPrefix = (bool)$this->currentGroupPrefix;

        // validate arguments
        $this->validateArguments($method, $handler);

        if ($route = trim($route)) {
            // always add '/' prefix.
            $route = $route{0} === '/' ? $route : '/' . $route;
        } elseif (!$hasPrefix){
            $route = '/';
        }

        $route = $this->currentGroupPrefix . $route;

        // setting 'ignoreLastSep'
        if ($route !== '/' && $this->config['ignoreLastSep']) {
            $route = rtrim($route, '/');
        }

        $this->routeCounter++;
        $opts = array_replace([
           'tokens' => null,
           'domains'  => null,
           'schema' => null, // ['http','https'],
            // route event
           'enter' => null,
           'leave' => null,
        ], $this->currentGroupOption, $opts);

        $conf = [
            'method' => $method,
            'handler' => $handler,
            'option' => $opts,
        ];

        // no dynamic param tokens
        if (strpos($route, '{') === false) {
            $this->staticRoutes[$route][$method] = $conf;

            return $this;
        }

        // have dynamic param tokens

        $tmp = $route;
        // '/user/{id}' first: 'user', '/a/{post}' first: 'a'
        list($first,) = explode('/', trim($tmp, '/'), 2);

        // like '/hello[/{name}]' parse fisrt to: 'hello'
        $first = trim($first, '[');

        // replace token name To pattern regex
        $route = $this->replaceTokenToPattern($route, $opts);

        // first node is a normal string
        if (preg_match('#^(?:[\w-]+)$#', $first, $m)) {
            $conf = [
                'first' => '/' . $first,
                'regex' => '#^' . $route . '$#',
            ] + $conf;

            $twoLevelKey = isset($first{1}) ? $first{1} : '__NO__';
            $this->regularRoutes[$first{0}][$twoLevelKey][] = $conf;

            // first node contain regex param '/{some}/{some2}'
        } else {
            $conf['regex'] = '#^' . $route . '$#';
            $this->vagueRoutes[] = $conf;
        }

        return $this;
    }

    /**
     * @param $method
     * @param $handler
     * @throws \InvalidArgumentException
     */
    private function validateArguments($method, $handler)
    {
        $supStr = implode('|', self::$supportedMethods);

        if (false === strpos('|' . $supStr . '|', '|' . $method . '|')) {
            throw new \InvalidArgumentException("The method [$method] is not supported, Allow: $supStr");
        }

        if (!$handler || (!is_string($handler) && !is_object($handler))) {
            throw new \InvalidArgumentException('The route handler is not empty and type only allow: string,object');
        }

        if (is_object($handler) && !is_callable($handler)) {
            throw new \InvalidArgumentException('The route object handler must be is callable');
        }
    }

    /**
     * @param string $route
     * @param array $opts
     * @return string
     */
    private function replaceTokenToPattern($route, array $opts)
    {
        $tokens = $this->getAvailableTokens($opts);

        // 解析可选参数位
        // '/hello[/{name}]'      match: /hello/tom   /hello
        // '/my[/{name}[/{age}]]' match: /my/tom/78  /my/tom
        if (false !== strpos($route, ']')) {
            $withoutClosingOptionals = rtrim($route, ']');
            $optionalNum = strlen($route) - strlen($withoutClosingOptionals);
            $nodes = explode('[', $withoutClosingOptionals);

            if ($optionalNum !== count($nodes) - 1) {
                throw new \LogicException('Optional segments can only occur at the end of a route');
            }

            // '/hello[/{name}]' -> '/hello(?:/{name})?'
            $route = str_replace(['[', ']'], ['(?:', ')?'], $route);
        }

        // 解析参数，替换为对应的 正则
        if (preg_match_all('#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#', $route, $m)) {
            /** @var array[] $m */
            $replacePairs = [];

            foreach ($m[1] as $name) {
                $key = '{' . $name . '}';
                // 匹配定义的 token  , 未匹配到的使用默认 self::DEFAULT_REGEX
                $regex = isset($tokens[$name]) ? $tokens[$name] : self::DEFAULT_REGEX;

                // 将匹配结果命名 (?P<arg1>[^/]+)
                // $replacePairs[$key] = '(?P<' . $name . '>' . $pattern . ')';
                $replacePairs[$key] = '(' . $regex . ')';
            }

            $route = strtr($route, $replacePairs);
        }

        return $route;
    }

    private function getAvailableTokens(array $opts)
    {
        /** @var array $tokens */
        $tokens = self::$globalTokens;

        if ($opts['tokens']) {
            foreach ((array)$opts['tokens'] as $name => $pattern) {
                $key = trim($name, '{}');
                $tokens[$key] = $pattern;
            }
        }

        return $tokens;
    }

//////////////////////////////////////////////////////////////////////
/// route match
//////////////////////////////////////////////////////////////////////

    /**
     * find the matched route info for the given request uri path
     * @param string $method
     * @param string $path
     * @return mixed
     */
    public function match($path, $method)
    {
        // if enable 'matchAll'
        if ($matchAll = $this->config['matchAll']) {
            if (is_string($matchAll) && $matchAll{0} === '/') {
                $path = $matchAll;
            } elseif (is_callable($matchAll)) {
                return [$path, $matchAll];
            }
        }

        // clear '//', '///' => '/'
        $path = rawurldecode(preg_replace('#\/\/+#', '/', $path));
        $method = strtoupper($method);
        $number = $this->config['tmpCacheNumber'];

        // setting 'ignoreLastSep'
        if ($path !== '/' && $this->config['ignoreLastSep']) {
            $path = rtrim($path, '/');
        }

        // find in route caches.
        if ($this->routeCaches && isset($this->routeCaches[$path])) {
            if (isset($this->routeCaches[$path][$method])) {
                return [$path, $this->routeCaches[$path][$method]];
            }

            if (isset($this->routeCaches[$path][self::MATCH_ANY])) {
                return [$path, $this->routeCaches[$path][self::MATCH_ANY]];
            }
        }

        // is a static route path
        if ($this->staticRoutes && isset($this->staticRoutes[$path])) {
            if (isset($this->staticRoutes[$path][$method])) {
                return [$path, $this->staticRoutes[$path][$method]];
            }

            if (isset($this->staticRoutes[$path][self::MATCH_ANY])) {
                return [$path, $this->staticRoutes[$path][self::MATCH_ANY]];
            }
        }

        $tmp = trim($path, '/'); // clear first '/'

        // is a regular dynamic route(the first char is 1th level index key).
        if ($this->regularRoutes && isset($this->regularRoutes[$tmp{0}])) {
            $twoLevelArr = $this->regularRoutes[$tmp{0}];
            $twoLevelKey = isset($tmp{1}) ? $tmp{1} : '__NO__';

            // not found
            if (!isset($twoLevelArr[$twoLevelKey])) {
                return false;
            }

            foreach ((array)$twoLevelArr[$twoLevelKey] as $conf) {
                if (0 === strpos($path, $conf['first']) && preg_match($conf['regex'], $path, $matches)) {
                    // method not allowed
                    if ($method !== $conf['method'] && self::MATCH_ANY !== $conf['method']) {
                        return false;
                    }

                    $conf['matches'] = $matches;

                    // cache latest $number routes.
                    if ($number > 0) {
                        if (count($this->routeCaches) === $number) {
                            array_shift($this->routeCaches);
                        }

                        $this->routeCaches[$path][$conf['method']] = $conf;
                    }

                    return [$path, $conf];
                }
            }
        }

        // is a irregular dynamic route
        foreach ($this->vagueRoutes as $conf) {
            if (preg_match($conf['regex'], $path, $matches)) {
                // method not allowed
                if ($method !== $conf['method'] && self::MATCH_ANY !== $conf['method']) {
                    return false;
                }

                $conf['matches'] = $matches;

                // cache last $number routes.
                if ($number > 0) {
                    if (count($this->routeCaches) === $number) {
                        array_shift($this->routeCaches);
                    }

                    $this->routeCaches[$path][$conf['method']] = $conf;
                }

                return [$path, $conf];
            }
        }

        // handle Auto Route
        if ($handler = $this->matchAutoRoute($path)) {
            return [$path, [
                'handler' => $handler
            ]];
        }

        // oo ... not found
        return false;
    }

    /**
     * handle Auto Route
     *  when config `'autoRoute' => true`
     * @param string $path The route path
     * @return bool|callable
     */
    private function matchAutoRoute($path)
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

//////////////////////////////////////////////////////////////////////
/// route callback handler dispatch
//////////////////////////////////////////////////////////////////////

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

//////////////////////////////////////////////////////////////////////
/// helper methods
//////////////////////////////////////////////////////////////////////

    /**
     * @param array $tokens
     */
    public function addTokens(array $tokens)
    {
        foreach ($tokens as $name => $pattern) {
            $this->addToken($name, $pattern);
        }
    }

    /**
     * @param $name
     * @param $pattern
     */
    public function addToken($name, $pattern)
    {
        $name = trim($name, '{} ');
        self::$globalTokens[$name] = $pattern;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->routeCounter;
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
     * @return array
     */
    public function getStaticRoutes()
    {
        return $this->staticRoutes;
    }

    /**
     * @return \array[]
     */
    public function getRegularRoutes()
    {
        return $this->regularRoutes;
    }

    /**
     * @return array
     */
    public function getVagueRoutes()
    {
        return $this->vagueRoutes;
    }

    /**
     * @return array
     */
    public function getGlobalTokens()
    {
        return self::$globalTokens;
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
