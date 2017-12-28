<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/17
 * Time: 下午11:37
 */

namespace Inhere\Route;

/**
 * Class AbstractRouter
 * @package Inhere\Route
 * @method get(string $route, mixed $handler, array $opts = [])
 * @method post(string $route, mixed $handler, array $opts = [])
 * @method put(string $route, mixed $handler, array $opts = [])
 * @method delete(string $route, mixed $handler, array $opts = [])
 * @method options(string $route, mixed $handler, array $opts = [])
 * @method head(string $route, mixed $handler, array $opts = [])
 * @method search(string $route, mixed $handler, array $opts = [])
 * @method connect(string $route, mixed $handler, array $opts = [])
 * @method trace(string $route, mixed $handler, array $opts = [])
 * @method any(string $route, mixed $handler, array $opts = [])
 */
abstract class AbstractRouter implements RouterInterface
{
    /**
     * some available patterns regex
     * $router->get('/user/{id}', 'handler');
     * @var array
     */
    protected static $globalParams = [
        'all' => '.*',
        'any' => '[^/]+',   // match any except '/'
        'num' => '[0-9]+',  // match a number
        'int' => '\d+',     // match a number
        'id' => '[1-9][0-9]*',  // match a ID number
        'act' => '[a-zA-Z][\w-]+', // match a action name
    ];

    /** @var bool */
    protected $initialized = false;

    /** @var string */
    protected $currentGroupPrefix;

    /** @var array */
    protected $currentGroupOption;

    /**
     * static Routes - no dynamic argument match
     * 整个路由 path 都是静态字符串 e.g. '/user/login'
     * @var array[]
     * [
     *     '/user/login' => [
     *          // METHOD => int, (int: data index in the {@see $routesData})
     *          'GET' => int,
     *          'PUT' => int,
     *          ...
     *      ],
     *      ... ...
     * ]
     */
    protected $staticRoutes = [];

    /**
     * @var array
     * [
     *  '/user/login#GET' => int, (int: data index in the {@see $routesData})
     *  '/user/login#PUT' => int,
     * ]
     */
    protected $flatStaticRoutes = [];

    /**
     * regular Routes - have dynamic arguments, but the first node is normal string.
     * 第一节是个静态字符串，称之为有规律的动态路由。按第一节的信息进行分组存储
     * e.g '/hello/{name}' '/user/{id}'
     * @var array[]
     * [
     *     // 使用完整的第一节作为key进行分组
     *     'add' => [
     *          [
     *              'start' => '/add/',
     *              'regex' => '/add/(\w+)',
     *              'methods' => 'GET',
     *              'handler' => 'handler',
     *              'option' => [...],
     *          ],
     *          ...
     *      ],
     *     'blog' => [
     *        [
     *              'start' => '/blog/post-',
     *              'regex' => '/blog/post-(\w+)',
     *              'methods' => 'GET',
     *              'handler' => 'handler',
     *              'option' => [...],
     *        ],
     *        ...
     *     ],
     *     ... ...
     * ]
     */
    protected $regularRoutes = [];

    /**
     * vague Routes - have dynamic arguments,but the first node is exists regex.
     * 第一节就包含了正则匹配，称之为无规律/模糊的动态路由
     * e.g '/{name}/profile' '/{some}/{some2}'
     * @var array
     * [
     *     // 使用 HTTP METHOD 作为 key进行分组
     *     'GET' => [
     *          [
     *              // 必定包含的字符串
     *              'include' => '/profile',
     *              'regex' => '/(\w+)/profile',
     *              'handler' => 'handler',
     *              'option' => [...],
     *          ],
     *          ...
     *     ],
     *     'POST' => [
     *          [
     *              'include' => null,
     *              'regex' => '/(\w+)/(\w+)',
     *              'handler' => 'handler',
     *              'option' => [...],
     *          ],
     *          ...
     *     ],
     *      ... ...
     * ]
     */
    protected $vagueRoutes = [];

    /**
     * There are last route caches
     * @see AbstractRouter::$staticRoutes
     * @var array[]
     * [
     *     '/user/login' => [
     *          // METHOD => [...]
     *          'GET' => [
     *              'handler' => 'handler',
     *              'option' => [...],
     *          ],
     *          'PUT' => [
     *              'handler' => 'handler',
     *              'option' => [...],
     *          ],
     *          ...
     *      ],
     *      ... ...
     * ]
     */
    protected $routeCaches = [];

    /** @var array[] */
    protected $routesData = [];

    /*******************************************************************************
     * router config
     ******************************************************************************/

    /**
     * Setting a routes file.
     * @var string
     */
    public $routesFile;

    /**
     * Flatten static routes info {@see $flatStaticRoutes}
     * @var bool
     */
    public $flattenStatic = false;

    /**
     * Ignore last slash char('/'). If is True, will clear last '/'.
     * @var bool
     */
    public $ignoreLastSlash = false;

    /**
     * The param route cache number.
     * @notice If is not daemon application, Please don't enable it.
     * @var int
     */
    public $tmpCacheNumber = 0;

    /**
     * Match all request.
     * 1. If is a valid URI path, will matchAll all request uri to the path.
     * 2. If is a closure, will matchAll all request then call it
     * eg: '/site/maintenance' or `function () { echo 'System Maintaining ... ...'; }`
     * @var mixed
     */
    public $matchAll;

    /**
     * @var bool NotAllowed As NotFound. If True, only two status value will be return(FOUND, NOT_FOUND).
     */
    public $notAllowedAsNotFound = false;

    /**
     * Auto route match @like yii framework
     * If is True, will auto find the handler controller file.
     * @var bool
     */
    public $autoRoute = false;

    /**
     * The default controllers namespace. eg: 'App\\Controllers'
     * @var string
     */
    public $controllerNamespace;

    /**
     * Controller suffix, is valid when '$autoRoute' = true. eg: 'Controller'
     * @var string
     */
    public $controllerSuffix = 'Controller';

    /**
     * object creator.
     * @param array $config
     * @return self
     * @throws \LogicException
     */
    public static function make(array $config = [])
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
        $this->setConfig($config);

        // $this->basicRoute = new Route();
        $this->currentGroupPrefix = '';
        $this->currentGroupOption = [];

        // load routes
        if (($file = $this->routesFile) && is_file($file)) {
            require $file;
        }
    }

    /**
     * @param array $config
     * @throws \LogicException
     */
    public function setConfig(array $config)
    {
        if ($this->initialized) {
            throw new \LogicException('Routing has been added, and configuration is not allowed!');
        }

        static $props = [
            'routesFile' => 1,
            'ignoreLastSlash' => 1,
            'tmpCacheNumber' => 1,
            'notAllowedAsNotFound' => 1,
            'matchAll' => 1,
            'autoRoute' => 1,
            'controllerNamespace' => 1,
            'controllerSuffix' => 1,
        ];

        foreach ($config as $name => $value) {
            if (isset($props[$name])) {
                $this->$name = $value;
            }
        }
    }

    /*******************************************************************************
     * route collection
     ******************************************************************************/

    /**
     * Defines a route callback and method
     * @param string $method
     * @param array $args
     * @return static
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    public function __call($method, array $args)
    {
        if (\in_array(strtoupper($method), self::ALLOWED_METHODS, true)) {
            if (\count($args) < 2) {
                throw new \InvalidArgumentException("The method [$method] parameters is missing.");
            }

            return $this->map($method, ...$args);
        }

        throw new \InvalidArgumentException("The method [$method] not exists in the class.");
    }

    /**
     * quick register a group restful routes for the controller class.
     * ```php
     * $router->rest('/users', UserController::class);
     * ```
     * @param string $prefix eg '/users'
     * @param string $controllerClass
     * @param array $map You can append or change default map list.
     * [
     *      'index' => null, // set value is empty to delete.
     *      'list' => 'get', // add new route
     * ]
     * @param array $opts Common options
     * @return static
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    public function rest($prefix, $controllerClass, array $map = [], array $opts = [])
    {
        $map = array_merge([
            'index' => ['GET'],
            'create' => ['POST'],
            'view' => ['GET', '{id}', ['id' => '[1-9]\d*']],
            'update' => ['PUT', '{id}', ['id' => '[1-9]\d*']],
            'patch' => ['PATCH', '{id}', ['id' => '[1-9]\d*']],
            'delete' => ['DELETE', '{id}', ['id' => '[1-9]\d*']],
        ], $map);
        //$opts = array_merge([], $opts);

        foreach ($map as $action => $conf) {
            if (!$conf || !$action) {
                continue;
            }

            $route = $prefix;

            // '/users/{id}'
            if (isset($conf[1]) && ($subPath = trim($conf[1]))) {
                // allow define a abs route. '/user-other-info'. it's not prepend prefix.
                $route = $subPath[0] === '/' ? $subPath : $prefix . '/' . $subPath;
            }

            if (isset($conf[2])) {
                $opts['params'] = $conf[2];
            }

            $this->map($conf[0], $route, $controllerClass . '@' . $action, $opts);
        }

        return $this;
    }

    /**
     * quick register a group universal routes for the controller class.
     *
     * ```php
     * $router->rest('/users', UserController::class, [
     *      'index' => 'get',
     *      'create' => 'post',
     *      'update' => 'post',
     *      'delete' => 'delete',
     * ]);
     * ```
     *
     * @param string $prefix eg '/users'
     * @param string $controllerClass
     * @param array $map You can append or change default map list.
     * [
     *      'index' => null, // set value is empty to delete.
     *      'list' => 'get', // add new route
     * ]
     * @param array $opts Common options
     * @return static
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    public function ctrl($prefix, $controllerClass, array $map = [], array $opts = [])
    {
        foreach ($map as $action => $method) {
            if (!$method || !\is_string($action)) {
                continue;
            }

            if ($action) {
                $route = $prefix . '/' . $action;
            } else {
                $route = $prefix;
                $action = 'index';
            }

            $this->map($method, $route, $controllerClass . '@' . $action, $opts);
        }

        return $this;
    }

    /**
     * Create a route group with a common prefix.
     * All routes created in the passed callback will have the given group prefix prepended.
     * @ref package 'nikic/fast-route'
     * @param string $prefix
     * @param \Closure $callback
     * @param array $opts
     */
    public function group($prefix, \Closure $callback, array $opts = [])
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix = $previousGroupPrefix . '/' . trim($prefix, '/');

        $previousGroupOption = $this->currentGroupOption;
        $this->currentGroupOption = $opts;

        $callback($this);

        $this->currentGroupPrefix = $previousGroupPrefix;
        $this->currentGroupOption = $previousGroupOption;
    }

    /**
     * validate and format arguments
     * @param string|array $methods
     * @param mixed $handler
     * @return array
     * @throws \InvalidArgumentException
     */
    public function validateArguments($methods, $handler)
    {
        if (!$methods || !$handler) {
            throw new \InvalidArgumentException('The method and route handler is not allow empty.');
        }

        $allow = self::ALLOWED_METHODS_STR . ',';
        $hasAny = false;
        $methods = array_map(function ($m) use ($allow, &$hasAny) {
            $m = strtoupper(trim($m));

            if (!$m || false === strpos($allow, $m . ',')) {
                throw new \InvalidArgumentException("The method [$m] is not supported, Allow: " . trim($allow, ','));
            }

            if (!$hasAny && $m === self::ANY) {
                $hasAny = true;
            }

            return $m;
        }, (array)$methods);

        if ($hasAny) {
            return self::ALLOWED_METHODS;
        }

        return $methods;
    }

    /**
     * is Static Route
     * @param string $route
     * @return bool
     */
    public static function isStaticRoute($route)
    {
        return strpos($route, '{') === false && strpos($route, '[') === false;
    }

    /**
     * @param string|null $path
     * @param bool $ignoreLastSlash
     * @return string
     */
    protected function formatUriPath($path, $ignoreLastSlash)
    {
        // clear '//', '///' => '/'
        if (false !== strpos($path, '//')) {
            $path = str_replace('//', '/', $path);
        }

        // decode
        $path = rawurldecode($path);

        // setting 'ignoreLastSlash'
        if ($path !== '/' && $ignoreLastSlash) {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    /**
     * @param array $matches
     * @param array $data
     */
    protected function filterMatches(array $matches, &$data)
    {
        if (!$matches) {
            $data['matches'] = [];

            return;
        }

        // clear all int key
        $matches = array_filter($matches, '\is_string', ARRAY_FILTER_USE_KEY);

        // apply some default param value
        if (isset($data['option']['defaults'])) {
            $data['matches'] = array_merge($data['option']['defaults'], $matches);
        } else {
            $data['matches'] = $matches;
        }
    }

    /**
     * @param string $route
     * @param array $params
     * @param array $conf
     * @return array
     * @throws \LogicException
     */
    public function parseParamRoute($route, array $params, array $conf = [])
    {
        $original = $route;

        // 解析可选参数位
        if (false !== ($pos = strpos($route, '['))) {
            $original = substr($route, 0, $pos);
            $withoutClosingOptionals = rtrim($route, ']');
            $optionalNum = \strlen($route) - \strlen($withoutClosingOptionals);

            if ($optionalNum !== substr_count($withoutClosingOptionals, '[')) {
                throw new \LogicException('Optional segments can only occur at the end of a route');
            }

            // '/hello[/{name}]' -> '/hello(?:/{name})?'
            $route = str_replace(['[', ']'], ['(?:', ')?'], $route);
        }

        $hasParam = strpos($original, '{') === false;

        // quote '.','/' to '\.','\/'
        if (false !== strpos($route, '.')) {
            // $route = preg_quote($route, '/');
            $route = str_replace('.', '\.', $route);
        }

        // 解析参数，替换为对应的 正则
        if (!$hasParam && preg_match_all('#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#', $route, $m)) {
            /** @var array[] $m */
            $replacePairs = [];

            foreach ($m[1] as $name) {
                $key = '{' . $name . '}';
                $regex = isset($params[$name]) ? $params[$name] : self::DEFAULT_REGEX;

                // 将匹配结果命名 (?P<arg1>[^/]+)
                $replacePairs[$key] = '(?P<' . $name . '>' . $regex . ')';
                // $replacePairs[$key] = '(' . $regex . ')';
            }

            $route = strtr($route, $replacePairs);
        }

        // 分析路由字符串是否是有规律的
        $first = null;
        $conf['regex'] = '#^' . $route . '$#';
        // $conf['regex'] = '#^(?|' . $route . ')$#';

        // first node is a normal string
        // e.g '/user/{id}' first: 'user'; '/a/{post}' first: 'a'
        /** @var string[] $m */
        if (preg_match('#^/([\w-]+)/[\w-]*/?#', $original, $m)) {
        // if (preg_match('#^/([\w-]+)/#', $original, $m)) {
            $first = $m[1];
            $conf['start'] = $m[0];

            // first node contain regex param '/hello[/{name}]' '/{some}/{some2}/xyz'
        } else {
            $include = null;

            if ($hasParam) {
                $include = $original;
            } elseif (preg_match('#/([\w-]+)/?[\w-]*#', $original, $m)) {
                $include = $m[0];
            }

            $conf['include'] = $include;
        }

        return [$first, $conf];
    }

    /**
     * @param string $path
     * @param string $method
     * @return array|false
     */
    abstract protected function findInStaticRoutes($path, $method);

    /**
     * @param array $routesInfo
     * @param string $path
     * @param string $method
     * @return array
     */
    abstract protected function findInRegularRoutes(array $routesInfo, $path, $method);

    /**
     * @param array $routesInfo
     * @param string $path
     * @param string $method
     * @return array
     */
    abstract protected function findInVagueRoutes(array $routesInfo, $path, $method);

    /**
     * @param string $path
     * @param string $method
     * @param array $data
     */
    abstract protected function cacheMatchedParamRoute($path, $method, $data);

    /**
     * handle auto route match, when config `'autoRoute' => true`
     * @param string $path The route path
     * @internal string $cnp controller namespace. eg: 'app\\controllers'
     * @internal string $sfx controller suffix. eg: 'Controller'
     * @return bool|callable
     */
    public function matchAutoRoute($path)
    {
        if (!$cnp = trim($this->controllerNamespace)) {
            return false;
        }

        $sfx = trim($this->controllerSuffix);
        $tmp = trim($path, '/- ');

        // one node. eg: 'home'
        if (!strpos($tmp, '/')) {
            $tmp = self::convertNodeStr($tmp);
            $class = "$cnp\\" . ucfirst($tmp) . $sfx;

            return class_exists($class) ? $class : false;
        }

        $ary = array_map([self::class, 'convertNodeStr'], explode('/', $tmp));
        $cnt = \count($ary);

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
     * @param array $tmpParams
     * @return array
     */
    public function getAvailableParams(array $tmpParams)
    {
        $params = self::$globalParams;

        if ($tmpParams) {
            foreach ($tmpParams as $name => $pattern) {
                $key = trim($name, '{}');
                $params[$key] = $pattern;
            }
        }

        return $params;
    }

    /**
     * convert 'first-second' to 'firstSecond'
     * @param $str
     * @return mixed|string
     */
    public static function convertNodeStr($str)
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
     * @param array $params
     */
    public function addGlobalParams(array $params)
    {
        foreach ($params as $name => $pattern) {
            $this->addGlobalParam($name, $pattern);
        }
    }

    /**
     * @param $name
     * @param $pattern
     */
    public function addGlobalParam($name, $pattern)
    {
        $name = trim($name, '{} ');
        self::$globalParams[$name] = $pattern;
    }

    /**
     * @return array
     */
    public static function getGlobalParams()
    {
        return self::$globalParams;
    }

    /**
     * @return array
     */
    public static function getSupportedMethods()
    {
        return self::ALLOWED_METHODS;
    }

    /**
     * @param array $staticRoutes
     */
    public function setStaticRoutes(array $staticRoutes)
    {
        $this->staticRoutes = $staticRoutes;
    }

    /**
     * @return array
     */
    public function getStaticRoutes()
    {
        return $this->staticRoutes;
    }

    /**
     * @param \array[] $regularRoutes
     */
    public function setRegularRoutes(array $regularRoutes)
    {
        $this->regularRoutes = $regularRoutes;
    }

    /**
     * @return \array[]
     */
    public function getRegularRoutes()
    {
        return $this->regularRoutes;
    }

    /**
     * @param array $vagueRoutes
     */
    public function setVagueRoutes($vagueRoutes)
    {
        $this->vagueRoutes = $vagueRoutes;
    }

    /**
     * @return array
     */
    public function getVagueRoutes()
    {
        return $this->vagueRoutes;
    }

    /**
     * @param array[] $routesData
     */
    public function setRoutesData($routesData)
    {
        $this->routesData = $routesData;
    }

    /**
     * @return array[]
     */
    public function getRoutesData()
    {
        return $this->routesData;
    }

    /**
     * @return array
     */
    public function getRouteCaches()
    {
        return $this->routeCaches;
    }
}
