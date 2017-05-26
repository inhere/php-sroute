<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/28
 * Time: 上午12:14
 */

namespace inhere\sroute\examples\controllers;

use inhere\sroute\SRoute;

/**
 * Class HomeController
 * @package inhere\sroute\examples\controllers
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

    public function testDispatchTo()
    {
        echo 'hello, this is ' . __METHOD__ . "<br>";

        echo 'dispatchTo /demo/index <br>';

        SRoute::dispatchTo('/demo/index', 'GET', false);
    }
}