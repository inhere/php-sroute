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
    const ANY_METHOD = 'ANY';

    // match result status
    const FOUND = 1;
    const NOT_FOUND = 2;
    const METHOD_NOT_ALLOWED = 3;

    const DEFAULT_REGEX = '[^/]+';
    const DEFAULT_TWO_LEVEL_KEY = '_NO_';

    /**
     * supported Methods
     * @var array
     */
    const SUPPORTED_METHODS = [
        'ANY',
        'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD', 'SEARCH', 'CONNECT', 'TRACE',
    ];

    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const PATCH = 'PATCH';
    const DELETE = 'DELETE';
    const OPTIONS = 'OPTIONS';
    const HEAD = 'HEAD';
    const SEARCH = 'SEARCH';
    const CONNECT = 'CONNECT';
    const TRACE = 'TRACE';

    const ANY = 'ANY';

    /**
     * @return array
     */
    public static function getSupportedMethods();
}
