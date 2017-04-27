<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/28
 * Time: 上午12:14
 */

namespace examples;

/**
 * Class HomeController
 * @package examples
 */
class HomeController
{
    public function index()
    {
        echo 'hello, this is ' . __METHOD__ . "<br>";
    }

    public function test()
    {
        echo 'hello, this is ' . __METHOD__ . "<br>";
    }
}