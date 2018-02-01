<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/1/27 0027
 * Time: 17:32
 */

namespace Inhere\Route;

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
        $path = $this->formatUriPath($path, $this->ignoreLastSlash);

        // if this path has been pre-matched.
        if ($method === $this->reqMethod && $path === $this->reqPath) {
            return [self::FOUND, $path, $this->preFounded];
        }

        return parent::match($path, $method);
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
