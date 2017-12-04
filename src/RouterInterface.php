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
interface RouterInterface
{
    /** match result status list */
    const FOUND = 1;
    const NOT_FOUND = 2;
    const METHOD_NOT_ALLOWED = 3;

    const FAV_ICON = '/favicon.ico';
    const DEFAULT_REGEX = '[^/]+';

    /** supported method list */
    const ANY = 'ANY';

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

    /** @var array supported methods */
    const SUPPORTED_METHODS = [
        'ANY',
        'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD',
        'COPY', 'PURGE', 'LINK', 'UNLINK', 'LOCK', 'UNLOCK', 'VIEW', 'SEARCH', 'CONNECT', 'TRACE',
    ];

    /** the matched result index key */
    const INDEX_STATUS = 0;
    const INDEX_PATH = 1;
    const INDEX_INFO = 2;

    /**
     * @param string|array $methods The match request method(s).
     * e.g
     *  string: 'get'
     *  array: ['get','post']
     * @param string $route The route path string. is allow empty string. eg: '/user/login'
     * @param callable|string $handler
     * @param array $opts some option data
     * [
     *     'params' => [ 'id' => '[0-9]+', ],
     *     'defaults' => [ 'id' => 10, ],
     *     'domains'  => [ 'a-domain.com', '*.b-domain.com'],
     *     'schemas' => ['https'],
     * ]
     * @return static
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    public function map($methods, $route, $handler, array $opts = []);

    /**
     * find the matched route info for the given request uri path
     * @param string $method
     * @param string $path
     * @return array
     *
     *  [self::NOT_FOUND, $path, null]
     *  [self::METHOD_NOT_ALLOWED, $path, ['GET', 'OTHER_ALLOWED_METHODS']]
     *  [self::FOUND, $path, array () // routeData ]
     *
     */
    public function match($path, $method = 'GET');

    /**
     * @return array
     */
    public static function getSupportedMethods();
}
