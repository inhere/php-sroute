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
     * is Static Route
     * @param string $route
     * @return bool
     */
    public static function isStaticRoute(string $route): bool
    {
        return \strpos($route, '{') === false && \strpos($route, '[') === false;
    }

    /**
     * format URI path
     * @param string $path
     * @param bool $ignoreLastSlash
     * @return string
     */
    public static function formatPath(string $path, bool $ignoreLastSlash = true): string
    {
        if ($path === '/') {
            return '/';
        }

        // clear '//', '///' => '/'
        if (false !== \strpos($path, '//')) {
            $path = (string)\preg_replace('#\/\/+#', '/', $path);
        }

        // must be start withs '/'
        if (\strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }

        // decode
        $path = \rawurldecode($path);

        return $ignoreLastSlash ? \rtrim($path, '/') : $path;
    }

    /**
     * @param string $path
     * @return string
     */
    public static function findFirstNode(string $path): string
    {
        // eg '/article/12' -> 'article'
        if ($pos = \strpos($path, '/', 1)) {
            return \substr($path, 1, $pos - 1);
        }

        return '';
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
     * handle auto route match, when config `'autoRoute' => true`
     * @param string $path The route path
     * @param string $cnp controller namespace. eg: 'app\\controllers'
     * @param string $sfx controller suffix. eg: 'Controller'
     * @return bool|callable
     */
    public static function parseAutoRoute(string $path, string $cnp, string $sfx)
    {
        $tmp = \trim($path, '/- ');

        // one node. eg: 'home'
        if (!\strpos($tmp, '/')) {
            $tmp = self::str2Camel($tmp);
            $class = "$cnp\\" . \ucfirst($tmp) . $sfx;

            return \class_exists($class) ? $class : false;
        }

        $ary = \array_map(self::class . '::str2Camel', \explode('/', $tmp));
        $cnt = \count($ary);

        // two nodes. eg: 'home/test' 'admin/user'
        if ($cnt === 2) {
            list($n1, $n2) = $ary;

            // last node is an controller class name. eg: 'admin/user'
            $class = "$cnp\\$n1\\" . \ucfirst($n2) . $sfx;

            if (\class_exists($class)) {
                return $class;
            }

            // first node is an controller class name, second node is a action name,
            $class = "$cnp\\" . \ucfirst($n1) . $sfx;

            return \class_exists($class) ? "$class@$n2" : false;
        }

        // max allow 5 nodes
        if ($cnt > 5) {
            return false;
        }

        // last node is an controller class name
        $n2 = \array_pop($ary);
        $class = \sprintf('%s\\%s\\%s', $cnp, \implode('\\', $ary), \ucfirst($n2) . $sfx);

        if (\class_exists($class)) {
            return $class;
        }

        // last second is an controller class name, last node is a action name,
        $n1 = \array_pop($ary);
        $class = \sprintf('%s\\%s\\%s', $cnp, \implode('\\', $ary), \ucfirst($n1) . $sfx);

        return \class_exists($class) ? "$class@$n2" : false;
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
