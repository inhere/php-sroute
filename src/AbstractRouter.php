<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/17
 * Time: 下午11:37
 */

namespace Inhere\Route;

use Inhere\Route\Helper\RouteHelper;

/**
 * Class AbstractRouter
 * @package Inhere\Route
 */
abstract class AbstractRouter implements RouterInterface
{
    /** @var string The router name */
    private $name = '';

    /**
     * some available patterns regex
     * $router->get('/user/{id}', 'handler');
     * @var array
     */
    protected static $globalParams = [
        'all' => '.*',
        'any' => '[^/]+',        // match any except '/'
        'num' => '[1-9][0-9]*',  // match a number and gt 0
        'int' => '\d+',          // match a number
        'act' => '[a-zA-Z][\w-]+', // match a action name
    ];

    /** @var bool */
    protected $initialized = false;

    /** @var string */
    protected $currentGroupPrefix;

    /** @var array */
    protected $currentGroupOption;
    protected $currentGroupChains;

    /**
     * static Routes - no dynamic argument match
     * 整个路由 path 都是静态字符串 e.g. '/user/login'
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
    protected $staticRoutes = [];

    /**
     * regular Routes - have dynamic arguments, but the first node is normal string.
     * 第一节是个静态字符串，称之为有规律的动态路由。按第一节的信息进行分组存储
     * e.g '/hello/{name}' '/user/{id}'
     * @var array[]
     * [
     *     // 使用完整的第一节作为key进行分组
     *     'edit' => [
     *          Route, // '/edit/{id}'
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
     * @var array[]
     * [
     *     // 使用 HTTP METHOD 作为 key进行分组
     *     'GET' => [
     *          [
     *              // 开始的字符串
     *              'start' => '/profile',
     *              'regex' => '/(\w+)/profile',
     *              'handler' => 'handler',
     *              'option' => [...],
     *          ],
     *          ...
     *     ],
     *     'POST' => [
     *          [
     *              'start' => null,
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
     * middleware chains
     * @var array
     */
    protected $chains = [];

    /*******************************************************************************
     * router config
     ******************************************************************************/

    /**
     * Match all request.
     * 1. If is a valid URI path, will matchAll all request uri to the path.
     * 2. If is a closure, will matchAll all request then call it
     * eg: '/site/maintenance' or `function () { echo 'System Maintaining ... ...'; }`
     * @var mixed
     */
    public $matchAll;

    /**
     * Ignore last slash char('/'). If is True, will clear last '/'.
     * @var bool
     */
    public $ignoreLastSlash = false;

    /**
     * whether handle method not allowed. If True, will find allowed methods.
     * @var bool
     */
    public $handleMethodNotAllowed = false;

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
    public static function create(array $config = []): AbstractRouter
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
        $this->config($config);

        $this->currentGroupPrefix = '';
        $this->currentGroupOption = [];
    }

    /**
     * config the router
     * @param array $config
     * @throws \LogicException
     */
    public function config(array $config)
    {
        if ($this->initialized) {
            throw new \LogicException('Routing has been added, and configuration is not allowed!');
        }

        $props = [
            'name' => 1,
            'chains' => 1,
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
     * register a route, allow GET request method.
     * @param string $path
     * @param $handler
     * @param array $binds path var bind.
     * @param array $opts
     */
    public function get(string $path, $handler, array $binds = [], array $opts = [])
    {
        $this->add('GET', $path, $handler, $binds, $opts);
        // $this->map(['GET', 'HEAD'], $path, $handler, $binds, $opts);
    }

    /**
     * register a route, allow POST request method.
     * @param string $path
     * @param $handler
     * @param array $binds path var bind.
     * @param array $opts
     */
    public function post(string $path, $handler, array $binds = [], array $opts = [])
    {
        $this->add('POST', $path, $handler, $binds, $opts);
    }

    /**
     * register a route, allow PUT request method.
     * {@inheritdoc}
     */
    public function put(string $path, $handler, array $binds = [], array $opts = [])
    {
        $this->add('PUT', $path, $handler, $binds, $opts);
    }

    /**
     * register a route, allow PATCH request method.
     * {@inheritdoc}
     */
    public function patch(string $path, $handler, array $binds = [], array $opts = [])
    {
        $this->add('PATCH', $path, $handler, $binds, $opts);
    }

    /**
     * register a route, allow DELETE request method.
     * {@inheritdoc}
     */
    public function delete(string $path, $handler, array $binds = [], array $opts = [])
    {
        $this->add('DELETE', $path, $handler, $binds, $opts);
    }

    /**
     * register a route, allow HEAD request method.
     * {@inheritdoc}
     */
    public function head(string $path, $handler, array $binds = [], array $opts = [])
    {
        $this->add('HEAD', $path, $handler, $binds, $opts);
    }

    /**
     * register a route, allow OPTIONS request method.
     * {@inheritdoc}
     */
    public function options(string $path, $handler, array $binds = [], array $opts = [])
    {
        $this->add('OPTIONS', $path, $handler, $binds, $opts);
    }

    /**
     * register a route, allow CONNECT request method.
     * {@inheritdoc}
     */
    public function connect(string $path, $handler, array $binds = [], array $opts = [])
    {
        $this->add('CONNECT', $path, $handler, $binds, $opts);
    }

    /**
     * register a route, allow any request METHOD.
     * {@inheritdoc}
     */
    public function any(string $path, $handler, array $binds = [], array $opts = [])
    {
        $this->map(self::METHODS_ARRAY, $path, $handler, $binds, $opts);
    }

    /**
     * Create a route group with a common prefix.
     * All routes created in the passed callback will have the given group prefix prepended.
     * @param string $prefix
     * @param \Closure $callback
     * @param array $middleware
     * @param array $opts
     */
    public function group(string $prefix, \Closure $callback, array $middleware = [], array $opts = [])
    {
        // backups
        $previousGroupPrefix = $this->currentGroupPrefix;
        $previousGroupOption = $this->currentGroupOption;
        $previousGroupChains = $this->currentGroupChains;

        $this->currentGroupOption = $opts;
        $this->currentGroupChains = $middleware;
        $this->currentGroupPrefix = $previousGroupPrefix . '/' . \trim($prefix, '/');

        // run callback.
        $callback($this);

        // reverts
        $this->currentGroupPrefix = $previousGroupPrefix;
        $this->currentGroupOption = $previousGroupOption;
        $this->currentGroupChains = $previousGroupChains;
    }

    /**
     * parse param route
     * @param array $params
     * @param array $conf
     * @return array
     * @throws \LogicException
     */
    public function parseParamRoute(array $conf, array $params = []): array
    {
        $first = '';
        $backup = $path = $conf['original'];
        $argPos = \strpos($path, '{');

        // quote '.','/' to '\.','\/'
        if (false !== \strpos($path, '.')) {
            $path = \str_replace('.', '\.', $path);
        }

        // Parse the optional parameters
        if (false !== ($optPos = \strpos($path, '['))) {
            $withoutClosingOptionals = \rtrim($path, ']');
            $optionalNum = \strlen($path) - \strlen($withoutClosingOptionals);

            if ($optionalNum !== \substr_count($withoutClosingOptionals, '[')) {
                throw new \LogicException('Optional segments can only occur at the end of a route');
            }

            // '/hello[/{name}]' -> '/hello(?:/{name})?'
            $path = \str_replace(['[', ']'], ['(?:', ')?'], $path);

            // no params
            if ($argPos === false) {
                $noOptional = \substr($path, 0, $optPos);
                $conf['start'] = $noOptional;
                $conf['regex'] = '#^' . $path . '$#';

                // eg '/article/12'
                if ($pos = \strpos($noOptional, '/', 1)) {
                    $first = \substr($noOptional, 1, $pos - 1);
                }

                return [$first, $conf];
            }

            $floorPos = $argPos >= $optPos ? $optPos : $argPos;
        } else {
            $floorPos = (int)$argPos;
        }

        $start = \substr($backup, 0, $floorPos);

        // regular: first node is a normal string e.g '/user/{id}' -> 'user', '/a/{post}' -> 'a'
        if ($pos = \strpos($start, '/', 1)) {
            $first = \substr($start, 1, $pos - 1);
        }

        // Parse the parameters and replace them with the corresponding regular
        if (\preg_match_all('#\{([a-zA-Z_][\w-]*)\}#', $path, $m)) {
            /** @var array[] $m */
            $pairs = [];

            foreach ($m[1] as $name) {
                $regex = $params[$name] ?? self::DEFAULT_REGEX;
                $pairs['{' . $name . '}'] = '(' . $regex . ')';
                // $pairs['{' . $name . '}'] = \sprintf('(?P<%s>%s)', $name, $regex);
            }

            $path = \strtr($path, $pairs);
            $conf['matches'] = $m[1];
        }

        $conf['regex'] = '#^' . $path . '$#';
        $conf['start'] = $start === '/' ? null : $start;

        return [$first, $conf];
    }

    /**
     * @param array $matches
     * @param array[] $conf
     * @return array
     */
    protected function mergeMatches(array $matches, array $conf): array
    {
        $route = [
            'handler' =>  $conf['handler'],
            'original' => $conf['original'],
        ];

        if (!$matches || !isset($conf['matches'])) {
            $route['matches'] = $conf['option']['defaults'] ?? [];
            return $conf;
        }

        // first is full match.
        \array_shift($matches);
        $newMatches = [];
        foreach ($conf['matches'] as $k => $name) {
            if (isset($matches[$k])) {
                $newMatches[$name] = $matches[$k];
            }
        }

        // apply some default param value
        if (isset($conf['option']['defaults'])) {
            $route['matches'] = \array_merge($conf['option']['defaults'], $newMatches);
        } else {
            $route['matches'] = $newMatches;
        }

        return $route;
    }

    /**
     * handle auto route match, when config `'autoRoute' => true`
     * @param string $path The route path
     * @return bool|callable
     */
    public function matchAutoRoute(string $path)
    {
        if (!$cnp = \trim($this->controllerNamespace)) {
            return false;
        }

        $sfx = \trim($this->controllerSuffix);

        return RouteHelper::parseAutoRoute($path, $cnp, $sfx);
    }

    /**
     * push middleware(s) for the route
     * @param array ...$middleware
     * @return self
     */
    public function use(...$middleware): AbstractRouter
    {
        foreach ($middleware as $handler) {
            $this->chains[] = $handler;
        }

        return $this;
    }

    /**
     * is Static Route
     * @param string $route
     * @return bool
     */
    public static function isStaticRoute(string $route): bool
    {
        return \strpos($route, '{') === false && \strpos($route, '[') === false;
    }

    /**
     * @param array $tmpParams
     * @return array
     */
    public function getAvailableParams(array $tmpParams): array
    {
        $params = self::$globalParams;

        if ($tmpParams) {
            $params = \array_merge($params, $tmpParams);
        }

        return $params;
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
        $name = \trim($name, '{} ');
        self::$globalParams[$name] = $pattern;
    }

    /**
     * @return array
     */
    public static function getGlobalParams(): array
    {
        return self::$globalParams;
    }

    /**
     * @return array
     */
    public static function getSupportedMethods(): array
    {
        return self::METHODS_ARRAY;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
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
    public function getStaticRoutes(): array
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
    public function getRegularRoutes(): array
    {
        return $this->regularRoutes;
    }

    /**
     * @param array $vagueRoutes
     */
    public function setVagueRoutes(array $vagueRoutes)
    {
        $this->vagueRoutes = $vagueRoutes;
    }

    /**
     * @return array
     */
    public function getVagueRoutes(): array
    {
        return $this->vagueRoutes;
    }
}
