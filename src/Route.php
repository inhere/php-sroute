<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/4/24
 * Time: 上午10:32
 */

namespace Inhere\Route;

/**
 * Class Route
 * @package Inhere\Route
 */
class Route
{
    /**
     * @var string route pattern
     */
    public $pattern;

    /**
     * @var mixed route handler
     */
    public $handler;

    /**
     * @var string[] map where parameter name => regular expression pattern (or symbol name)
     */
    public $params;

    /**
     * @var array
     */
    public $options = [];
}
