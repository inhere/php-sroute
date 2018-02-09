<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/17
 * Time: 下午9:40
 */

namespace Inhere\Route\Dispatcher;

use Inhere\Route\Base\RouterInterface;

/**
 * Interface DispatcherInterface
 * @package Inhere\Route\Dispatcher
 */
interface DispatcherInterface
{
    const FAV_ICON = '/favicon.ico';

    // events
    const ON_FOUND = 'found';
    const ON_NOT_FOUND = 'notFound';
    const ON_METHOD_NOT_ALLOWED = 'methodNotAllowed';
    const ON_EXEC_START = 'execStart';
    const ON_EXEC_END = 'execEnd';
    const ON_EXEC_ERROR = 'execError';

    /**
     * Runs the callback for the given path and method.
     * @param string $path
     * @param null|string $method
     * @return mixed
     * @throws \Throwable
     */
    public function dispatchUri(string $path = null, string $method = null);

    /**
     * Dispatch route handler for the given route info.
     * @param int $status
     * @param string $path
     * @param array $info
     * @return mixed
     */
    public function dispatch(int $status, string $path, array $info);

    /**
     * @return RouterInterface|null
     */
    public function getRouter();

    /**
     * @param RouterInterface $router
     * @return SimpleDispatcher
     */
    public function setRouter(RouterInterface $router): SimpleDispatcher;

    /**
     * @return array
     */
    public static function getSupportedEvents(): array;
}
