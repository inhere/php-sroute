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
 *  - pre-match
 *  - un-complete
 * @package Inhere\Route
 */
class PreMatchRouter extends ORouter
{
    /**
     * @var string
     */
    private $curPath;

    /**
     * @var string
     */
    private $curMethod;

    /** @var array */
    private $preMatched = [];

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

        $this->curPath = $path;
        $this->curMethod = $method ? strtoupper($method) : $_SERVER['REQUEST_METHOD'];
    }

    public function map($methods, string $route, $handler, array $opts = []): AbstractRouter
    {
        if (!$this->initialized) {
            $this->initialized = true;
        }

        $hasPrefix = (bool)$this->currentGroupPrefix;
        $methods = $this->validateArguments($methods, $handler);

        // always add '/' prefix.
        if ($route = trim($route)) {
            $route = $route{0} === '/' ? $route : '/' . $route;
        } elseif (!$hasPrefix) {
            $route = '/';
        }

        $route = $this->currentGroupPrefix . $route;

        // setting 'ignoreLastSlash'
        if ($route !== '/' && $this->ignoreLastSlash) {
            $route = rtrim($route, '/');
        }

        $conf = [
            'handler' => $handler,
        ];

        if ($opts = array_merge($this->currentGroupOption, $opts)) {
            $conf['option'] = $opts;
        }

        // it is static route
        if (self::isStaticRoute($route)) {
            foreach ($methods as $method) {
                if ($method === 'ANY') {
                    continue;
                }

                // $this->routeCounter++;
                $this->staticRoutes[$route][$method] = $conf;
            }

            return $this;
        }

        // collect Param Route
        $this->collectParamRoute($route, $methods, $conf);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function match(string $path, string $method = self::GET): array
    {
        if ($method === $this->curMethod && $path === $this->curPath) {

        }

        return parent::match($path, $method);
    }

    /**
     * @return array
     */
    public function getPreMatched(): array
    {
        return $this->preMatched;
    }
}
