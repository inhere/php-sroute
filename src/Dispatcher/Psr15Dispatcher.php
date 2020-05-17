<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/11/20
 * Time: 3:58 PM
 */

namespace Inhere\Route\Dispatcher;

use Exception;
use Inhere\Route\Helper\RouteHelper;
use Inhere\Route\Route;
use Inhere\Route\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use function array_merge;

/**
 * Class Psr15Dispatcher
 * @deps
 *   psr - http message package
 * @package Inhere\Route\Dispatcher
 * @deprecated un-completed
 */
class Psr15Dispatcher extends SimpleDispatcher
{
    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws Throwable
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $path   = $request->getUri()->getPath();
        $method = $request->getMethod();

        /** @var Route $route */
        [$status, $path, $route] = $this->getRouter()->match($path, $method);

        $chains = $this->getRouter()->getChains();

        switch ($status) {
            case RouterInterface::FOUND:
                $chains = array_merge($chains, $route->getChains());
                break;
            case RouterInterface::NOT_FOUND:

                break;
            case RouterInterface::METHOD_NOT_ALLOWED:

                break;
        }
    }

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

            $globChains = $this->getRouter()->getChains();
            $chains     = $route->getChains();

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
}
