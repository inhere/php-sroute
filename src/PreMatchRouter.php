<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/1/27 0027
 * Time: 17:32
 */

namespace Inhere\Route;

use Inhere\Route\Base\AbstractRouter;

/**
 * Class PreMatchRouter
 * 预匹配：适用于fpm环境，并且静态路由较多的应用
 *  - 收集路由前就将当前请求的 path 和 METHOD 提前设置进来。
 *  - 搜集时，所有的静态路由在添加时会挨个匹配。 匹配成功后不再接受添加路由。
 *  - 匹配时，若已经提前匹配成功直接返回匹配到的。
 * @package Inhere\Route
 */
final class PreMatchRouter extends ORouter
{
    /** @var string */
    private $reqPath;

    /** @var string */
    private $reqMethod;

    /** @var array */
    private $preFounded = [];

    /**
     * object constructor.
     * @param array $config
     * @param string|null $path
     * @param string|null $method
     * @throws \LogicException
     */
    public function __construct(array $config = [], string $path = null, string $method = null)
    {
        parent::__construct($config);

        if (!$path) {
            $path = $config['path'] ?? null;
        }

        if (!$method) {
            $method = $config['method'] ?? null;
        }

        $this->setRequest($path, $method);
    }

    /**
     * @param string|null $path
     * @param string|null $method
     */
    public function setRequest(string $path = null, string $method = null)
    {
        $path = $path ?: $_SERVER['REQUEST_URI'];

        if (strpos($path, '?')) {
            $path = parse_url($path, PHP_URL_PATH);
        }

        $this->reqPath = $this->formatUriPath($path, $this->ignoreLastSlash);
        $this->reqMethod = $method ? strtoupper($method) : $_SERVER['REQUEST_METHOD'];
    }

    /**
     * {@inheritdoc}
     */
    public function map($methods, string $route, $handler, array $opts = []): AbstractRouter
    {
        // has been matched. don't add again.
        if ($this->preFounded) {
            return $this;
        }

        $methods = $this->validateArguments($methods, $handler);
        list($route, $conf) = $this->prepareForMap($route, $handler, $opts);

        // it is param route
        if (!self::isStaticRoute($route)) {
            $this->collectParamRoute($route, $methods, $conf);

            return $this;
        }

        $founded = $route === $this->reqPath;

        foreach ($methods as $method) {
            if ($method === 'ANY') {
                continue;
            }

            // success matched
            if ($founded && $method === $this->reqMethod) {
                $this->preFounded = $conf;
                $this->routeCounter++;
                // discard other routes data.
                // $this->staticRoutes = $this->regularRoutes = [];

                return $this;
            }

            $this->routeCounter++;
            $this->staticRoutes[$route][$method] = $conf;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function match(string $path, string $method = self::GET): array
    {
        // if enable 'matchAll'
        if ($matchAll = $this->matchAll) {
            if (\is_string($matchAll) && $matchAll{0} === '/') {
                // $path = $matchAll;
                $path = $this->formatUriPath($matchAll, $this->ignoreLastSlash);
            } elseif (\is_callable($matchAll)) {
                return [self::FOUND, $path, [
                    'handler' => $matchAll,
                ]];
            }
        } else {
            $path = $this->reqPath;
        }

        // if this path has been pre-matched.
        if ($this->preFounded) {
            return [self::FOUND, $path, $this->preFounded];
        }

        $first = null;
        $method = strtoupper($method);
        $allowedMethods = [];

        if ($pos = strpos($path, '/', 1)) {
            $first = substr($path, 1, $pos - 1);
        }

        if ($first && isset($this->regularRoutes[$first])) {
            $result = $this->findInRegularRoutes($this->regularRoutes[$first], $path, $method);

            if ($result[0] === self::FOUND) {
                return $result;
            }

            $allowedMethods = $result[1];
        }

        if (isset($this->vagueRoutes[$method])) {
            $result = $this->findInVagueRoutes($this->vagueRoutes[$method], $path, $method);

            if ($result[0] === self::FOUND) {
                return $result;
            }
        }

        // For HEAD requests, attempt fallback to GET
        if ($method === 'HEAD') {
            if (isset($this->staticRoutes[$path]['GET'])) {
                return [self::FOUND, $path, $this->staticRoutes[$path]['GET']];
            }

            if ($first && isset($this->regularRoutes[$first])) {
                $result = $this->findInRegularRoutes($this->regularRoutes[$first], $path, 'GET');

                if ($result[0] === self::FOUND) {
                    return $result;
                }
            }

            if (isset($this->vagueRoutes['GET'])) {
                $result = $this->findInVagueRoutes($this->vagueRoutes['GET'], $path, 'GET');

                if ($result[0] === self::FOUND) {
                    return $result;
                }
            }
        }

        // If nothing else matches, try fallback routes. $router->any('*', 'handler');
        if ($this->staticRoutes && isset($this->staticRoutes['/*'][$method])) {
            return [self::FOUND, $path, $this->staticRoutes['/*'][$method]];
        }

        if ($this->notAllowedAsNotFound) {
            return [self::NOT_FOUND, $path, null];
        }

        // collect allowed methods from: staticRoutes, vagueRoutes OR return not found.
        return $this->findAllowedMethods($path, $method, $allowedMethods);
    }

    /**
     * @return array
     */
    public function getPreFounded(): array
    {
        return $this->preFounded;
    }

    /**
     * @return string
     */
    public function getReqPath(): string
    {
        return $this->reqPath;
    }
}
