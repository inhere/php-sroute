<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/4/24
 * Time: 上午10:32
 */

namespace Inhere\Route;

use ArrayIterator;
use IteratorAggregate;
use LogicException;
use Traversable;
use function array_merge;
use function array_shift;
use function count;
use function get_class;
use function is_array;
use function is_object;
use function is_string;
use function preg_match;
use function preg_replace_callback;
use function property_exists;
use function rtrim;
use function sprintf;
use function str_replace;
use function strlen;
use function strpos;
use function strtoupper;
use function strtr;
use function substr_count;
use function trim;

/**
 * Class Route
 * @package Inhere\Route
 */
final class Route implements IteratorAggregate
{
    /**
     * @var string Route name
     */
    private string $name = '';

    /**
     * @var string route pattern path. eg "/users/{id}" "/user/login"
     */
    private string $path;

    /**
     * @var string allowed request method.
     */
    private string $method;

    /**
     * @var mixed route handler
     */
    private $handler;

    /**
     * map where parameter binds.
     * [param name => regular expression path (or symbol name)]
     * @var string[]
     */
    private array $bindVars;

    /**
     * dynamic route param values, only use for route cache
     * [param name => value]
     * @var string[]
     */
    private array $params = [];

    // -- match condition. it is parsed from route path string.

    /**
     * path var names.
     * @var array '/users/{id}' => ['id']
     */
    private array $pathVars = [];

    /**
     * @var string eg. '#^/users/(\d+)$#'
     */
    private string $pathRegex = '';

    /**
     * '/users/{id}' -> '/users/'
     * '/blog/post-{id}' -> '/blog/post-'
     * @var string
     */
    private string $pathStart = '';

    // -- extra properties

    /**
     * middleware handler chains
     * @var callable[]
     */
    private array $chains = [];

    /**
     * some custom route options data.
     * @var array
     */
    private array $options;

    /**
     * @param string $method
     * @param string $path
     * @param mixed  $handler
     * @param array  $pathParams
     * @param array  $options
     *
     * @return Route
     */
    public static function create(
        string $method,
        string $path,
        mixed $handler,
        array $pathParams = [],
        array $options = []
    ): Route {
        return new self($method, $path, $handler, $pathParams, $options);
    }

    /**
     * @param array $config
     *
     * @return Route
     */
    public static function createFromArray(array $config = []): self
    {
        $route = new self('GET', '/', '');

        foreach ($config as $property => $value) {
            if (property_exists($route, $property)) {
                $route->$property = $value;
            }
        }

        return $route;
    }

    /**
     * Route constructor.
     *
     * @param string $method
     * @param string $path
     * @param mixed  $handler
     * @param array  $pathParams
     * @param array  $options
     */
    public function __construct(string $method, string $path, mixed $handler, array $pathParams = [], array $options = [])
    {
        $this->initialize($method, $path, $handler, $pathParams, $options);
    }

    public function initialize(
        string $method,
        string $path,
        $handler,
        array $pathParams = [],
        array $options = []
    ): self {
        $this->path     = trim($path);
        $this->method   = strtoupper($method);
        $this->bindVars = $pathParams;
        $this->handler  = $handler;
        $this->options  = $options;

        if (isset($options['name'])) {
            $this->setName($options['name']);
        }

        return $this;
    }

    /**
     * register route to the router
     *
     * @param Router $router
     *
     * @return Route
     */
    public function attachTo(Router $router): self
    {
        $router->addRoute($this);
        return $this;
    }

    /**
     * name the route and bind name to router.
     *
     * @param string $name
     * @param Router $router
     * @param bool   $register
     */
    public function namedTo(string $name, Router $router, bool $register = false): void
    {
        // not empty
        if ($name = $this->setName($name)->name) {
            if ($register) {
                $router->addRoute($this);
            } else {
                $router->nameRoute($name, $this);
            }
        }
    }

    /*******************************************************************************
     * parse route path
     ******************************************************************************/

    /**
     * parse route path string. fetch route params
     *
     * @param array $bindParams
     *
     * @return string returns the first node string.
     */
    public function parseParam(array $bindParams = []): string
    {
        $argPos = strpos($this->path, '{');
        $optPos = strpos($this->path, '[');

        return $this->quickParseParams($argPos, $optPos, $bindParams);
    }

    /**
     * @param int|false $argPos
     * @param int|false $optPos
     * @param array     $bindParams
     *
     * @return string
     */
    public function quickParseParams($argPos, $optPos, array $bindParams = []): string
    {
        $first = $start = '';
        $path  = $this->path;

        // regular: first node is a normal string e.g '/user/{id}' -> 'user', '/a/{post}' -> 'a'
        if (preg_match('#^/([\w-]+)/(?:[\w\/-]*)#', $path, $m)) {
            [$start, $first] = $m;
            $this->pathStart = $start === '/' ? '' : $start;
        }

        // quote '.','/' to '\.','\/'
        if (false !== strpos($path, '.')) {
            $path = str_replace('.', '\.', $path);
        }

        // Parse the optional parameters
        if (false !== $optPos) {
            $noClosingOptionals = rtrim($path, ']');
            $optionalNum        = strlen($path) - strlen($noClosingOptionals);
            if ($optionalNum !== substr_count($noClosingOptionals, '[')) {
                throw new LogicException('Optional segments can only occur at the end of a route');
            }

            // '/hello[/{name}]' -> '/hello(?:/{name})?'
            $path = str_replace(['[', ']'], ['(?:', ')?'], $path);
            // No params
            if ($argPos === false) {
                $this->pathRegex = '#^' . $path . '$#';
                return $first;
            }
        }

        if ($bindVars = $this->getBindVars()) { // merge current route vars
            $bindParams = array_merge($bindParams, $bindVars);
        }

        // Parse the parameters and replace them with the corresponding regular
        $path = preg_replace_callback('#\{([a-zA-Z_][\w-]*)\}#', function ($m) use ($bindParams) {
            $this->pathVars[] = $m[1];

            $regex = $bindParams[$m[1]] ?? Router::DEFAULT_REGEX;
            return '(' . $regex . ')';
        }, $path);

        $this->pathRegex = '#^' . $path . '$#';
        return $first;
    }

    /*******************************************************************************
     * route match
     ******************************************************************************/

    /**
     * @param string $path
     *
     * @return array returns match result. has two elements.
     * [
     *  match ok?,
     *  route params values
     * ]
     */
    public function match(string $path): array
    {
        // Check start string
        if ($this->pathStart !== '' && strpos($path, $this->pathStart) !== 0) {
            return [false,];
        }

        return $this->matchRegex($path);
    }

    /**
     * @param string $path
     *
     * @return array
     * [
     *  match ok?,
     *  route params values
     * ]
     */
    public function matchRegex(string $path): array
    {
        // If don't match
        if (!preg_match($this->pathRegex, $path, $matches)) {
            return [false,];
        }

        // No params. eg: only use optional. '/about[.html]'
        if (count($this->pathVars) === 0) {
            return [true, []];
        }

        $params = [];

        // First is full match.
        array_shift($matches);
        foreach ($matches as $index => $value) {
            $params[$this->pathVars[$index]] = $value;
        }

        // If has default values
        if (isset($this->options['defaults'])) {
            $params = array_merge($this->options['defaults'], $params);
        }

        return [true, $params];
    }

    /*******************************************************************************
     * helper methods
     ******************************************************************************/

    /**
     * @param array $params
     *
     * @return Route
     */
    public function copyWithParams(array $params): self
    {
        $route         = clone $this;
        $route->params = $params;

        return $route;
    }

    /**
     * push middleware(s) to the route
     *
     * @param array ...$middleware
     *
     * @return Route
     */
    public function middleware(...$middleware): self
    {
        foreach ($middleware as $handler) {
            $this->chains[] = $handler;
        }

        return $this;
    }

    /**
     * alias of the method: middleware()
     *
     * @param mixed ...$middleware
     *
     * @return Route
     * @see middleware()
     */
    public function push(...$middleware): self
    {
        return $this->middleware(...$middleware);
    }

    /**
     * replace set chains.
     *
     * @param callable[] $chains
     */
    public function setChains(array $chains): void
    {
        $this->chains = $chains;
    }

    /**
     * build uri string.
     *
     * @param array $pathVars
     *
     * @return string
     */
    public function toUri(array $pathVars = []): string
    {
        if ($pathVars) {
            return strtr($this->path, $pathVars);
        }

        return $this->path;
    }

    /**
     * get basic info data
     * @return array
     */
    public function info(): array
    {
        return [
            'path'        => $this->path,
            'method'      => $this->method,
            'handlerName' => $this->getHandlerName(),
        ];
    }

    /**
     * get all info data
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name'      => $this->name,
            'path'      => $this->path,
            'method'    => $this->method,
            'handler'   => $this->handler,
            'binds'     => $this->bindVars,
            'params'    => $this->params,
            'options'   => $this->options,
            //
            'pathVars'  => $this->pathVars,
            'pathStart' => $this->pathStart,
            'pathRegex' => $this->pathRegex,
            //
            'chains'    => $this->chains,
        ];
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return sprintf(
            '%-7s %-25s --> %s (%d middleware)',
            $this->method,
            $this->path,
            $this->getHandlerName(),
            count($this->chains)
        );
    }

    /**
     * @param string $name
     *
     * @return Route
     */
    public function setName(string $name): self
    {
        if ($name = trim($name)) {
            $this->name = $name;
        }

        return $this;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = trim($path);
    }

    /**
     * @param string $name
     * @param        $value
     *
     * @return Route
     */
    public function addOption(string $name, $value): self
    {
        $this->options[$name] = $value;
        return $this;
    }

    /**
     * @param array $options
     *
     * @return Route
     */
    public function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    /*******************************************************************************
     * getter methods
     ******************************************************************************/

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return mixed
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @return string[]
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param string     $name
     * @param null|mixed $default
     *
     * @return string|mixed
     */
    public function getParam(string $name, $default = null)
    {
        return $this->params[$name] ?? $default;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return string[]
     */
    public function getBindVars(): array
    {
        return $this->bindVars;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getPathVars(): array
    {
        return $this->pathVars;
    }

    /**
     * @return string
     */
    public function getPathRegex(): string
    {
        return $this->pathRegex;
    }

    /**
     * @return string
     */
    public function getPathStart(): string
    {
        return $this->pathStart;
    }

    /**
     * @return array
     */
    public function getChains(): array
    {
        return $this->chains;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getHandlerName(): string
    {
        $handlerName = 'unknown';

        if (is_object($this->handler)) {
            $handlerName = get_class($this->handler);
        } elseif (is_array($this->handler)) {
            $handlerName = '[array callback]';
        } elseif (is_string($this->handler)) {
            $handlerName = $this->handler;
        }

        return $handlerName;
    }
}
