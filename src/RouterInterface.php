<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/16
 * Time: 下午10:43
 */

namespace Inhere\Route;

/**
 * Interface RouterInterface
 * @package Inhere\Route
 */
interface RouterInterface extends \IteratorAggregate, \Countable
{
    /** match result status list */
    const FOUND = 1;
    const NOT_FOUND = 2;
    const METHOD_NOT_ALLOWED = 3;

    const FAV_ICON = '/favicon.ico';
    const DEFAULT_REGEX = '[^/]+';

    /** supported method list */
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const PATCH = 'PATCH';
    const DELETE = 'DELETE';
    const OPTIONS = 'OPTIONS';
    const HEAD = 'HEAD';

    const COPY = 'COPY';
    const PURGE = 'PURGE';
    const LINK = 'LINK';
    const UNLINK = 'UNLINK';
    const LOCK = 'LOCK';
    const UNLOCK = 'UNLOCK';
    const SEARCH = 'SEARCH';
    const CONNECT = 'CONNECT';
    const TRACE = 'TRACE';

    /** supported methods name list */
    const METHODS_ARRAY = [
        'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD', 'CONNECT'
        // 'COPY', 'PURGE', 'LINK', 'UNLINK', 'LOCK', 'UNLOCK', 'VIEW', 'SEARCH', 'TRACE',
    ];

    // ,COPY,PURGE,LINK,UNLINK,LOCK,UNLOCK,VIEW,SEARCH,TRACE';
    /** supported methods name string */
    const METHODS_STRING = ',GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD,CONNECT,';

    /** the matched result index key */
    const INDEX_STATUS = 0;
    const INDEX_PATH = 1;
    const INDEX_INFO = 2;

    /**
     * @param string $method
     * @param string $path
     * @param $handler
     * @param array $binds route path var bind. eg. [ 'id' => '[0-9]+', ]
     * @param array $opts
     * @return Route
     */
    public function add(string $method, string $path, $handler, array $binds = [], array $opts = []): Route;

    /**
     * add a Route to the router
     * @param Route $route
     * @return Route
     */
    public function addRoute(Route $route): Route;

    /**
     * @param array|string $methods The match request method(s). e.g ['get','post']
     * @param string $path The route path string. is allow empty string. eg: '/user/login'
     * @param callable|string $handler
     * @param array $binds route path var bind. eg. [ 'id' => '[0-9]+', ]
     * @param array $opts some option data
     * [
     *     'defaults' => [ 'id' => 10, ],
     *     'domains'  => [ 'a-domain.com', '*.b-domain.com'],
     *     'schemas' => ['https'],
     * ]
     */
    public function map($methods, string $path, $handler, array $binds = [], array $opts = []);

    /**
     * find the matched route info for the given request uri path
     * @param string $method
     * @param string $path
     * @return array
     *
     *  [self::NOT_FOUND, $path, null]
     *  [self::METHOD_NOT_ALLOWED, $path, ['GET', 'OTHER_METHODS_ARRAY']]
     *  [self::FOUND, $path, array () // routeData ]
     *
     */
    public function match(string $path, string $method = 'GET'): array;

    /**
     * @return array
     */
    public function getChains(): array;

    /**
     * @return array
     */
    public static function getAllowedMethods(): array;
}
