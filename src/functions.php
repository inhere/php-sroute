<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-29
 * Time: 10:04
 */

namespace Inhere\Route;

/**
 * @param \Closure $closure
 * @param array $config
 * @return ORouter
 */
function createRouter(\Closure $closure, array $config = []): ORouter
{
    $closure($router = new ORouter($config));

    return $router;
}

/**
 * @param \Closure $closure
 * @param array $config
 * @return CachedRouter
 */
function createCachedRouter(\Closure $closure, array $config = []): CachedRouter
{
    $closure($router = new CachedRouter($config));

    $router->completed();

    return $router;
}

/**
 * @param \Closure $closure
 * @param string|null $path
 * @param string|null $method
 * @param array $config
 * @return PreMatchRouter
 */
function createPreMatchRouter(
    \Closure $closure,
    string $path = null,
    string $method = null,
    array $config = []
): PreMatchRouter {
    $router = new PreMatchRouter($config);
    $router->setRequest($path, $method);

    $closure($router);

    return $router;
}

/**
 * @param \Closure $closure
 * @param array $config
 * @return ServerRouter
 */
function createServerRouter(\Closure $closure, array $config = []): ServerRouter
{
    $closure($router = new ServerRouter($config));

    $router->flattenStatics();

    return $router;
}