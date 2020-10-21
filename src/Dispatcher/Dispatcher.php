<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午8:03
 */

namespace Inhere\Route\Dispatcher;

use Inhere\Route\Helper\RouteHelper;
use Inhere\Route\Route;
use Throwable;

/**
 * Class Dispatcher
 * 相比 SimpleDispatcher，支持更多的自定义选项控制
 *
 * @package Inhere\Route\Dispatcher
 */
class Dispatcher extends SimpleDispatcher
{
    /*******************************************************************************
     * route callback handler dispatch
     ******************************************************************************/

    /**
     * @param string $path
     * @param string $method
     * @param Route  $route
     *
     * @return bool|mixed|null
     * @throws Throwable
     */
    protected function doDispatch(string $path, string $method, $route)
    {
        $options = $route->getOptions();

        // fire enter event
        // schema,domains ... metadata validate
        if (isset($options['enter']) && false === RouteHelper::call($options['enter'], [$options, $path])) {
            return null;
        }

        $result  = null;
        $handler = $route->getHandler();
        $params  = $route->getParams();

        try {
            // trigger route exec_start event
            $this->fire(self::ON_EXEC_START, [$path, $route]);
            $result = $this->callHandler($path, $method, $handler, $params);

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
