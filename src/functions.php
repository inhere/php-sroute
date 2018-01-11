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
function createRouter(\Closure $closure, array $config = []): ORouter {
    $router = new ORouter($config);

    $closure($router);

    return $router;
}

/**
 * @param \Closure $closure
 * @param array $config
 * @return CachedRouter
 */
function createCachedRouter(\Closure $closure, array $config = []): CachedRouter {
    $router = new CachedRouter($config);

    $closure($router);

    $router->completed();

    return $router;
}