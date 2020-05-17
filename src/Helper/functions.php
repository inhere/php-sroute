<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-29
 * Time: 10:04
 */

namespace Inhere\Route;

use Closure;

/**
 * @param Closure $closure
 * @param array   $config
 *
 * @return Router
 */
function createRouter(Closure $closure, array $config = []): Router
{
    $closure($router = new Router($config));

    return $router;
}

/**
 * @param Closure $closure
 * @param array   $config
 *
 * @return CachedRouter
 */
function createCachedRouter(Closure $closure, array $config = []): CachedRouter
{
    $closure($router = new CachedRouter($config));

    $router->completed();

    return $router;
}

/**
 * @param Closure     $closure
 * @param string $path
 * @param string $method
 * @param array       $config
 *
 * @return PreMatchRouter
 */
function createPreMatchRouter(
    Closure $closure,
    string $path = '',
    string $method = '',
    array $config = []
): PreMatchRouter {
    $router = new PreMatchRouter($config, $path, $method);

    $closure($router);

    return $router;
}

/**
 * @param Closure $closure
 * @param array   $config
 *
 * @return ServerRouter
 */
function createServerRouter(Closure $closure, array $config = []): ServerRouter
{
    $closure($router = new ServerRouter($config));

    return $router;
}
