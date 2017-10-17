<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/28
 * Time: 上午12:14
 */

namespace Inhere\Route\Examples\Controllers;

/**
 * Class HomeController
 * @package Inhere\Route\examples\controllers
 */
class HomeController
{
    public function indexAction()
    {
        echo 'hello, this is ' . __METHOD__ . "<br>";
    }

    public function testAction()
    {
        echo 'hello, this is ' . __METHOD__ . "<br>";
    }
}
