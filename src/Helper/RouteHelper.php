<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/4/19 0019
 * Time: 23:56
 */

namespace Inhere\Route\Helper;

/**
 * Class RouteHelper
 * @package Inhere\Route
 */
class RouteHelper
{
    /**
     * @param string $path
     * @param bool $ignoreLastSlash
     * @return string
     */
    public static function formatUriPath(string $path, bool $ignoreLastSlash = true): string
    {
        if ($path === '/') {
            return '/';
        }

        // clear '//', '///' => '/'
        if (false !== \strpos($path, '//')) {
            $path = (string)\preg_replace('#\/\/+#', '/', $path);
        }

        // decode
        $path = \rawurldecode($path);

        return $ignoreLastSlash ? \rtrim($path, '/') : $path;
    }

    /**
     * @param string $str
     * @return string
     */
    public static function str2Camel(string $str): string
    {
        $str = \trim($str, '-');

        // convert 'first-second' to 'firstSecond'
        if (\strpos($str, '-')) {
            $str = (string)\preg_replace_callback('/-+([a-z])/', function ($c) {
                return \strtoupper($c[1]);
            }, \trim($str, '- '));
        }

        return $str;
    }

    /**
     * @param callable|mixed $cb
     * string - func name, class name
     * array - [class, method]
     * object - Closure, Object
     *
     * @param array $args
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public static function call($cb, array $args = [])
    {
        if (!$cb) {
            return true;
        }

        if (\is_array($cb)) {
            list($obj, $mhd) = $cb;

            return \is_object($obj) ? $obj->$mhd(...$args) : $obj::$mhd(...$args);
        }

        if (\is_string($cb)) {
            if (\function_exists($cb)) {
                return $cb(...$args);
            }

            // a class name
            if (\class_exists($cb)) {
                $cb = new $cb;
            }
        }

        // a \Closure or Object implement '__invoke'
        if (\is_object($cb) && \method_exists($cb, '__invoke')) {
            return $cb(...$args);
        }

        throw new \InvalidArgumentException('the callback handler is not callable!');
    }
}
