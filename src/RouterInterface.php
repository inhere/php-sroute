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
    const ANY_METHOD = 'ANY';

    // match result status
    const STS_FOUND = 1;
    const STS_NOT_FOUND = 2;
    const STS_METHOD_NOT_ALLOWED = 3;

    const DEFAULT_REGEX = '[^/]+';

    /**
     * @return array
     */
    public static function getSupportedMethods();
}
