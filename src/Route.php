<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-27
 * Time: 9:47
 */

namespace Inhere\Route;

/**
 * Class Route
 * @package Inhere\Route
 */
class Route
{
    /** @var mixed Uri path */
    public $path;

    /** @var mixed Route handler */
    public $handler;

    /** @var string Allow method */
    public $method;

    /** @var string Original route pattern */
    public $original;

    /** @var array Matched route param values. */
    public $matches;

    /** @var array Route option. */
    public $option;

    public function init()
    {

    }
}
