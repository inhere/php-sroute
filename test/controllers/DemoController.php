<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/28
 * Time: 上午12:14
 */

namespace Inhere\RouteTest\controllers;

/**
 * Class DemoController
 * @package Inhere\RouteTest\controllers
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
