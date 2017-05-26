<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/28
 * Time: 上午12:14
 */

namespace inhere\sroute\examples\controllers;

/**
 * Class DemoController
 * @package inhere\sroute\examples\controllers
 */
class DemoController
{
    public function index()
    {
        echo 'hello, this is ' . __METHOD__ . "<br>";
    }

    public function test()
    {
        echo 'hello, this is ' . __METHOD__ . "<br>";
    }

    // you can access by '/demo/oneTwo' or '/demo/one-two'
    public function oneTwo()
    {
        echo 'hello, this is ' . __METHOD__ . "<br>";
    }
}