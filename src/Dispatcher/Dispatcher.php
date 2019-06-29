<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午8:03
 */

namespace Inhere\Route\Dispatcher;

use Exception;
use Inhere\Route\Helper\RouteHelper;
use Inhere\Route\RouterInterface;
use Throwable;

/**
 * Class Dispatcher
 * 相比 SimpleDispatcher
 * @package Inhere\Route\Dispatcher
 */
class Dispatcher extends SimpleDispatcher
{
    /*******************************************************************************
     * route callback handler dispatch
     ******************************************************************************/

    /**
     * Dispatch route handler for the given route info.
     * {@inheritdoc}
     * @throws Exception
     * @throws Throwable
     */
    public function dispatch(int $status, string $path, string $method, $route)
    {
        // not found
        if ($status === RouterInterface::NOT_FOUND) {
            return $this->handleNotFound($path, $method);
        }

        // method not allowed
        if ($status === RouterInterface::METHOD_NOT_ALLOWED) {
            return $this->handleNotAllowed($path, $method, $route);
        }

        // trigger route found event
        $this->fire(self::ON_FOUND, [$path, $route]);

        $result  = null;
        $options = $route->getOptions();

        // fire enter event
        // schema,domains ... metadata validate
        if (isset($options['enter']) && false === RouteHelper::call($options['enter'], [$options, $path])) {
            return $result;
        }

        $handler = $route->getHandler();
        $args    = $route->getParams();

        try {
            // trigger route exec_start event
            $this->fire(self::ON_EXEC_START, [$path, $route]);
            $result = $this->callHandler($path, $method, $handler, $args);

            // fire leave event
            if (isset($options['leave'])) {
                RouteHelper::call($options['leave'], [$options, $path]);
            }

            // trigger route exec_end event
            $this->fire(self::ON_EXEC_END, [$path, $route, $result]);
        } catch (Throwable $e) {
            // trigger route exec_error event
            if ($cb = $this->getOption(self::ON_EXEC_ERROR)) {
                return RouteHelper::call($cb, [$e, $path, $route]);
            }

            throw $e;
        }

        return $result;
    }

    /**
     * @param array $options
     * [
     *     'domains'  => [ 'a-domain.com', '*.b-domain.com'],
     *     'schemes' => ['https'],
     * ]
     */
    protected function validateMetadata(array $options): void
    {
        // 1. validate Schema

        // 2. validate validateDomains
        // $serverName = $_SERVER['SERVER_NAME'];

        // 3. more something ...
    }
}
