<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/4/24
 * Time: 上午10:32
 */

namespace Inhere\Route;

/**
 * Class Route
 * @package Inhere\Route
 */
final class Route implements \IteratorAggregate
{
    /**
     * @var string route pattern path. eg "/users/{id}" "/user/login"
     */
    private $path;

    /**
     * @var string allowed request method.
     */
    private $method;

    /**
     * @var mixed route handler
     */
    private $handler;

    /**
     * map where parameter binds.
     * [param name => regular expression path (or symbol name)]
     * @var string[]
     */
    private $bindVars;

    /**
     * dynamic route param values, only use for route cache
     * [param name => value]
     * @var string[]
     */
    private $params = [];

    // -- match condition. it is parsed from route path string.

    /**
     * path var names.
     * @var array '/users/{id}' => ['id']
     */
    private $pathVars = [];

    /**
     * @var string eg. '#^/users/(\d+)$#'
     */
    private $pathRegex = '';

    /**
     * '/users/{id}' -> '/users/'
     * '/blog/post-{id}' -> '/blog/post-'
     * @var string
     */
    private $pathStart = '';

    // -- extra properties

    /**
     * middleware chains
     * @var array
     */
    public $chains = [];

    /**
     * some custom route options data.
     * @var array
     */
    private $options;

    /**
     * @param string $method
     * @param string $path
     * @param $handler
     * @param array $paramBinds
     * @param array $options
     * @return Route
     */
    public static function create(string $method, string $path, $handler, array $paramBinds = [], array $options = []): Route
    {
        return new self($method, $path, $handler, $paramBinds, $options);
    }

    /**
     * Route constructor.
     * @param string $method
     * @param string $path
     * @param mixed $handler
     * @param array $paramBinds
     * @param array $options
     */
    public function __construct(string $method, string $path, $handler, array $paramBinds = [], array $options = [])
    {
        $this->path = $path;
        $this->method = \strtoupper($method);
        $this->bindVars = $paramBinds;
        $this->handler = $handler;
        $this->options = $options;
    }

    /*******************************************************************************
     * parse route path
     ******************************************************************************/

    /**
     * parse route path string. fetch route params
     * @param array $bindParams
     * @return string returns the first node string.
     */
    public function parseParam(array $bindParams): string
    {
        $first = '';
        $backup = $path = $this->path;
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
                $this->pathStart = $noOptional;
                $this->pathRegex = '#^' . $path . '$#';

                // eg '/article/12'
                if ($pos = \strpos($noOptional, '/', 1)) {
                    $first = \substr($noOptional, 1, $pos - 1);
                }

                return $first;
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
                $regex = $bindParams[$name] ?? RouterInterface::DEFAULT_REGEX;
                $pairs['{' . $name . '}'] = '(' . $regex . ')';
                // $pairs['{' . $name . '}'] = \sprintf('(?P<%s>%s)', $name, $regex);
            }

            $path = \strtr($path, $pairs);
            $this->pathVars = $m[1];
        }

        $this->pathRegex = '#^' . $path . '$#';
        $this->pathStart = $start === '/' ? '' : $start;

        return $first;
    }

    /*******************************************************************************
     * route match
     ******************************************************************************/

    /**
     * @param string $path
     * @return array returns match result.
     * [is ok?, route params values]
     */
    public function match(string $path): array
    {
        // check start string
        if ($this->pathStart !== '' && \strpos($path, $this->pathStart) !== 0) {
            return [false,];
        }

        // regex match
        if (!\preg_match($this->pathRegex, $path, $matches)) {
            return [false,];
        }

        $params = [];

        // no params. eg only use optional. '/about[.html]'
        if (\count($this->pathVars) === 0) {
            return [true, $params];
        }

        // first is full match.
        \array_shift($matches);
        foreach ($matches as $index => $value) {
            $params[$this->pathVars[$index]] = $value;
        }

        // if has default values
        if (isset($this->options['defaults'])) {
            $params = \array_merge($this->options['defaults'], $params);
        }

        return [true, $params];
    }

    /**
     * param array $params matched path params values.
     * @return array
     */
    public function info(): array
    {
        return [
            'params' => [],
            'handler' => $this->handler,
            'chains' => $this->chains,
            'options' => $this->options,
        ];
    }

    /**
     * @param array $params
     * @return Route
     */
    public function copyWithParams(array $params): self
    {
        $route = clone $this;
        $route->params = $params;

        return $route;
    }

    /*******************************************************************************
     * helper methods
     ******************************************************************************/

    /**
     * push middleware for the route
     * @param array ...$middleware
     * @return Route
     */
    public function push(...$middleware): self
    {
        foreach ($middleware as $handler) {
            $this->chains[] = $handler;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'method' => $this->method,
            'handler' => $this->handler,
            'binds' => $this->bindVars,
            'params' => $this->params,
            'options' => $this->options,
            //
            'pathVars' => $this->pathVars,
            'pathStart' => $this->pathStart,
            'pathRegex' => $this->pathRegex,
            //
            'chains' => $this->chains,
        ];
    }

    /**
     * @param array $bindVars
     * @return Route
     */
    public function setBindVars(array $bindVars): Route
    {
        $this->bindVars = $bindVars;
        return $this;
    }

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    public function addOption(string $name, $value)
    {
        $this->options[$name] = $value;
        return $this;
    }

    /**
     * @param array $options
     * @return Route
     */
    public function setOptions(array $options): Route
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
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
     * @param string $name
     * @param null|mixed $default
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
}
