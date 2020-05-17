<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/1/27 0027
 * Time: 17:32
 */

namespace Inhere\Route;

use Inhere\Route\Helper\RouteHelper;
use LogicException;
use function parse_url;
use function strpos;
use function strtoupper;
use const PHP_URL_PATH;

/**
 * Class PreMatchRouter
 * 预匹配：适用于fpm环境，并且静态路由较多的应用
 *  - 收集路由前就将当前请求的 path 和 METHOD 提前设置进来。
 *  - 搜集时，所有的静态路由在添加时会挨个匹配。 匹配成功后不再接受添加路由。
 *  - 匹配时，若已经提前匹配成功直接返回匹配到的。
 * @package Inhere\Route
 */
final class PreMatchRouter extends Router
{
    /** @var string */
    private $reqPath;

    /** @var string */
    private $reqMethod;

    /** @var Route */
    private $preFounded;

    /**
     * object constructor.
     *
     * @param array  $config
     * @param string $path
     * @param string $method
     *
     * @throws LogicException
     */
    public function __construct(array $config = [], string $path = '', string $method = '')
    {
        parent::__construct($config);

        if (!$path) {
            $path = $config['path'] ?? '';
        }

        if (!$method) {
            $method = $config['method'] ?? '';
        }

        $this->setRequest($path, $method);
    }

    /**
     * @param string $path
     * @param string $method
     */
    public function setRequest(string $path = '', string $method = ''): void
    {
        if (!$path) {
            $path = $_SERVER['REQUEST_URI'] ?? '';
        }

        if (strpos($path, '?')) {
            $path = (string)parse_url($path, PHP_URL_PATH);
        }

        $this->reqPath   = RouteHelper::formatPath($path, $this->ignoreLastSlash);
        $this->reqMethod = $method ? strtoupper($method) : $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @param Route $route
     *
     * @return Route
     */
    public function addRoute(Route $route): Route
    {
        // Has been matched. don't add again.
        if ($this->preFounded) {
            return $route;
        }

        $path   = $route->getPath();
        $method = $route->getMethod();

        $this->routeCounter++;

        // Success match
        if ($path === $this->reqPath && $method === $this->reqMethod) {
            $this->preFounded = $route;
            return $route;
        }

        return parent::addRoute($route);
    }

    /**
     * {@inheritdoc}
     */
    public function match(string $path, string $method = 'GET'): array
    {
        // If has been pre-matched.
        if ($this->preFounded) {
            return [self::FOUND, $path, $this->preFounded];
        }

        return parent::match($path, $method);
    }

    /**
     * @return Route|null
     */
    public function getPreFounded(): ?Route
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
