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
function createRouter(\Closure $closure, array $config = [])
{
    $router = new ORouter($config);

    $closure($router);

    return $router;
}

/**
 * @param \Closure $closure
 * @param array $config
 * @return CachedRouter
 */
function createCachedRouter(\Closure $closure, array $config = [])
{
    $router = new CachedRouter($config);

    $closure($router);
    $router->dumpCache();

    return $router;
}

/**
 * @param \Closure $closure
 * @param array $config
 * @return DaemonRouter
 */
function createDaemonRouter(\Closure $closure, array $config = [])
{
    $router = new DaemonRouter($config);

    $closure($router);
    $router->flattenStatics();

    return $router;
}
