<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/4/19 0019
 * Time: 23:56
 */

namespace Inhere\Route\Helper;

use InvalidArgumentException;
use function array_filter;
use function array_map;
use function array_pop;
use function class_exists;
use function count;
use function explode;
use function function_exists;
use function implode;
use function is_array;
use function is_object;
use function is_string;
use function lcfirst;
use function method_exists;
use function preg_replace;
use function preg_replace_callback;
use function rawurldecode;
use function rtrim;
use function sprintf;
use function strpos;
use function strtoupper;
use function trim;
use function ucfirst;

/**
 * Class RouteHelper
 * @package Inhere\Route
 */
class RouteHelper
{
    /**
     * is Static Route
     *
     * @param string $route
     *
     * @return bool
     */
    public static function isStaticRoute(string $route): bool
    {
        return strpos($route, '{') === false && strpos($route, '[') === false;
    }

    /**
     * format URI path
     *
     * @param string $path
     * @param bool   $ignoreLastSlash
     *
     * @return string
     */
    public static function formatPath(string $path, bool $ignoreLastSlash = true): string
    {
        if ($path === '/') {
            return '/';
        }

        // Clear '//', '///' => '/'
        if (false !== strpos($path, '//')) {
            $path = preg_replace('#\/\/+#', '/', $path);
        }

        // Must be start withs '/'
        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }

        // Decode
        $path = rawurldecode($path);

        return $ignoreLastSlash ? rtrim($path, '/') : $path;
    }

    /**
     * convert 'first-second' to 'firstSecond'
     *
     * @param string $str
     * @param bool   $ucFirst
     *
     * @return string
     */
    public static function str2Camel(string $str, bool $ucFirst = false): string
    {
        $str = trim($str, '-');

        // convert 'first-second' to 'firstSecond'
        if (strpos($str, '-')) {
            $str = preg_replace_callback('/-+([a-z])/', static function ($c) {
                return strtoupper($c[1]);
            }, trim($str, '- '));
        }

        return $ucFirst ? ucfirst($str) : $str;
    }

    /**
     * handle auto route match, when config `'autoRoute' => true`
     *
     * @param string $path The route path
     * @param string $cnp  controller namespace. eg: 'app\\controllers'
     * @param string $sfx  controller suffix. eg: 'Controller'
     * @param bool   $ucFirst
     *
     * @return bool|callable
     */
    public static function parseAutoRoute(string $path, string $cnp, string $sfx, bool $ucFirst = false)
    {
        $tmp = trim($path, '/- ');

        // one node. eg: 'home'
        if (!strpos($tmp, '/')) {
            $tmp   = self::str2Camel($tmp);
            $class = "$cnp\\" . ucfirst($tmp) . $sfx;

            return class_exists($class) ? $class : false;
        }

        $nodes = array_filter(explode('/', $tmp));

        if ($ucFirst) {
            foreach ($nodes as $i => $node) {
                $nodes[$i] = self::str2Camel($nodes[$i], true);
            }
        } else {
            $nodes = array_map(self::class . '::str2Camel', $nodes);
        }

        $count = count($nodes);

        // two nodes. eg: 'home/test' 'admin/user'
        if ($count === 2) {
            [$n1, $n2] = $nodes;

            // last node is an controller class name. eg: 'admin/user'
            $class = "$cnp\\$n1\\" . ucfirst($n2) . $sfx;

            if (class_exists($class)) {
                return $class;
            }

            // first node is an controller class name, second node is a action name,
            $class = "$cnp\\" . ucfirst($n1) . $sfx;

            return class_exists($class) ? $class . '@' . lcfirst($n2) : false;
        }

        // max allow 5 nodes
        if ($count > 5) {
            return false;
        }

        // last node is an controller class name
        $n2    = array_pop($nodes);
        $class = sprintf('%s\\%s\\%s', $cnp, implode('\\', $nodes), ucfirst($n2) . $sfx);

        if (class_exists($class)) {
            return $class;
        }

        // last second is an controller class name, last node is a action name,
        $n1    = array_pop($nodes);
        $class = sprintf('%s\\%s\\%s', $cnp, implode('\\', $nodes), ucfirst($n1) . $sfx);

        return class_exists($class) ? $class . '@' . lcfirst($n2) : false;
    }

    /**
     * @param callable|mixed $cb
     * string - func name, class name
     * array - [class, method]
     * object - Closure, Object
     *
     * @param array          $args
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public static function call($cb, array $args = [])
    {
        if (!$cb) {
            return true;
        }

        if (is_array($cb)) {
            [$obj, $mhd] = $cb;

            return is_object($obj) ? $obj->$mhd(...$args) : $obj::$mhd(...$args);
        }

        if (is_string($cb)) {
            if (function_exists($cb)) {
                return $cb(...$args);
            }

            // a class name
            if (class_exists($cb)) {
                $cb = new $cb;
            }
        }

        // a \Closure or Object implement '__invoke'
        if (is_object($cb) && method_exists($cb, '__invoke')) {
            return $cb(...$args);
        }

        throw new InvalidArgumentException('the callback handler is not callable!');
    }
}
