<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午8:03
 */

namespace Inhere\Route\Dispatcher;

use Inhere\Route\Base\RouterInterface;

/**
 * Class Dispatcher
 * @package Inhere\Route\Dispatcher
 */
class Dispatcher extends SimpleDispatcher
{
    /*******************************************************************************
     * route callback handler dispatch
     ******************************************************************************/

    /**
     * Dispatch route handler for the given route info.
     * @param int $status
     * @param string $path
     * @param array $info
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     */
    public function dispatch(int $status, string $path, array $info)
    {
        $method = $info['requestMethod'] ?? null;

        // not found
        if ($status === RouterInterface::NOT_FOUND) {
            return $this->handleNotFound($path, $method);
        }

        // method not allowed
        if ($status === RouterInterface::METHOD_NOT_ALLOWED) {
            return $this->handleNotAllowed($path, $method, $info);
        }

        // trigger route found event
        $this->fire(self::ON_FOUND, [$path, $info]);

        $result = null;
        $options = [];

        if (isset($info['option'])) {
            $options = $info['option'];
            unset($info['option']);
        }

        // fire enter event
        // schema,domains ... metadata validate
        if (
            isset($options['enter']) &&
            false === $this->fireCallback($options['enter'], [$options, $path])
        ) {
            return $result;
        }

        $handler = $info['handler'];
        $args['matches'] = $info['matches'] ?? [];

        try {
            // trigger route exec_start event
            $this->fire(self::ON_EXEC_START, [$path, $info]);

            $result = $this->callRouteHandler($path, $method, $handler, $args);

            // fire leave event
            if (isset($options['leave'])) {
                $this->fireCallback($options['leave'], [$options, $path]);
            }

            // trigger route exec_end event
            $this->fire(self::ON_EXEC_END, [$path, $info]);
        } catch (\Exception $e) {
            // trigger route exec_error event
            if ($cb = $this->getOption(self::ON_EXEC_ERROR)) {
                return $this->fireCallback($cb, [$e, $path, $info]);
            }

            throw $e;
        } catch (\Throwable $e) {
            // trigger route exec_error event
            if ($cb = $this->getOption(self::ON_EXEC_ERROR)) {
                return $this->fireCallback($cb, [$e, $path, $info]);
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
    protected function validateMetadata(array $options)
    {
        // 1. validate Schema

        // 2. validate validateDomains
        // $serverName = $_SERVER['SERVER_NAME'];

        // 3. more something ...
    }

    /**
     * Trigger event
     * @param $event
     * @param array $args
     * @return mixed
     */
    protected function fire($event, array $args = [])
    {
        if (!$cb = $this->getOption($event)) {
            return false;
        }

        return $this->fireCallback($cb, $args);
    }
}
