<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午8:03
 */

namespace Inhere\Route;

use Inhere\Route\Dispatcher\DispatcherInterface;

/**
 * Class SRoute - this is static class version
 * @package Inhere\Route
 * @method static get(string $route, mixed $handler, array $opts = [])
 * @method static post(string $route, mixed $handler, array $opts = [])
 * @method static put(string $route, mixed $handler, array $opts = [])
 * @method static delete(string $route, mixed $handler, array $opts = [])
 * @method static options(string $route, mixed $handler, array $opts = [])
 * @method static head(string $route, mixed $handler, array $opts = [])
 * @method static search(string $route, mixed $handler, array $opts = [])
 * @method static connect(string $route, mixed $handler, array $opts = [])
 * @method static trace(string $route, mixed $handler, array $opts = [])
 * @method static any(string $route, mixed $handler, array $opts = [])
 * @method static map(string|array $methods, string $route, mixed $handler, array $opts = [])
 * @method static group(string $prefix, \Closure $callback, array $opts = [])
 * @method static setConfig(array $config)
 * @method static match($path, $method = 'GET')
 * @method static dispatch(DispatcherInterface|array $dispatcher, $path = null, $method = null)
 */
final class SRouter
{
    /** @var ORouter */
    private static $router;

    /**
     * Defines a route callback and method
     * @param string $method
     * @param array $args
     * @return ORouter|mixed
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public static function __callStatic($method, array $args)
    {
        return self::getRouter()->$method(...$args);
    }

    /**
     * @return ORouter
     * @throws \LogicException
     */
    public static function getRouter(): ORouter
    {
        if (!self::$router) {
            self::$router = new ORouter();
        }

        return self::$router;
    }

    /**
     * @param ORouter $router
     */
    public static function setRouter(ORouter $router)
    {
        self::$router = $router;
    }

    private function __construct()
    {}
}
