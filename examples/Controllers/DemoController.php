<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/28
 * Time: 上午12:14
 */

namespace Inhere\Route\Examples\Controllers;

/**
 * Class DemoController
 * @package Inhere\Route\Examples\Controllers
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