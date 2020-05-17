<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/28
 * Time: 上午12:14
 */

namespace Inhere\RouteTest\Controllers;

/**
 * Class DemoController
 * @package Inhere\RouteTest\Controllers
 */
class DemoController
{
    public function indexAction(): void
    {
        echo 'hello, this is ' . __METHOD__ . '<br>';
    }

    public function testAction(): void
    {
        echo 'hello, this is ' . __METHOD__ . '<br>';
    }

    // you can access by '/demo/oneTwo' or '/demo/one-two'
    public function oneTwoAction(): void
    {
        echo 'hello, this is ' . __METHOD__ . '<br>';
    }
}
