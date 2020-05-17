<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/17
 * Time: 下午9:40
 */

namespace Inhere\Route\Dispatcher;

use Inhere\Route\Route;
use Inhere\Route\RouterInterface;
use Throwable;

/**
 * Interface DispatcherInterface
 * @package Inhere\Route\Dispatcher
 */
interface DispatcherInterface
{
    public const FAV_ICON = '/favicon.ico';

    // some route events
    public const ON_FOUND              = 'found';

    public const ON_NOT_FOUND          = 'notFound';

    public const ON_METHOD_NOT_ALLOWED = 'methodNotAllowed';

    public const ON_EXEC_START         = 'execStart';

    public const ON_EXEC_END           = 'execEnd';

    public const ON_EXEC_ERROR         = 'execError';

    /**
     * Runs the callback for the given path and method.
     *
     * @param string $path
     * @param string $method
     *
     * @return mixed
     * @throws Throwable
     */
    public function dispatchUri(string $path = '', string $method = '');

    /**
     * Dispatch route handler for the given route info.
     *
     * @param int              $status
     * @param string           $path
     * @param string           $method
     * @param Route|array|null $route matched route info
     *
     * @return mixed
     */
    public function dispatch(int $status, string $path, string $method, $route);

    /**
     * Defines callback on happen event.
     *
     * @param string   $event please see class constants ON_*
     * @param callable $handler
     */
    public function on(string $event, $handler): void;

    /**
     * @return RouterInterface
     */
    public function getRouter(): RouterInterface;

    /**
     * @return bool
     */
    public function hasRouter(): bool;

    /**
     * @param RouterInterface $router
     */
    public function setRouter(RouterInterface $router): void;

    /**
     * @return array
     */
    public static function getSupportedEvents(): array;
}
