<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/16
 * Time: 下午10:43
 */

namespace inhere\sroute;

/**
 * Interface RouterInterface
 * @package inhere\sroute
 */
interface RouterInterface
{
    // events
    const FOUND = 'found';
    const NOT_FOUND = 'notFound';
    const EXEC_START = 'execStart';
    const EXEC_END = 'execEnd';
    const EXEC_ERROR = 'execError';

    const MATCH_ANY = 'ANY';
    const MATCH_FAV_ICO = '/favicon.ico';

    // match result status
    const STS_FOUND = 1;
    const STS_NOT_FOUND = 2;
    const STS_METHOD_NOT_ALLOWED = 3;

    // "/user/{name}[/{id:[0-9]+}]"
    const VARIABLE_REGEX = <<<REGEX
\{
    \s* ([a-zA-Z_][a-zA-Z0-9_-]*) \s*
    (?:
        : \s* ([^{}]*(?:\{(?-1)\}[^{}]*)*)
    )?
\}
REGEX;

    const DEFAULT_REGEX = '[^/]+';

    /**
     * @return array
     */
    public static function getSupportedMethods();

    /**
     * @return array
     */
    public static function getSupportedEvents();
}
