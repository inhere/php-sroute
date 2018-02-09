<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/28
 * Time: 上午12:14
 */

namespace Inhere\Route\Example\Controllers;

/**
 * Class HomeController
 * @package Inhere\Route\example\controllers
 */
class HomeController
{
    public function indexAction()
    {
        echo 'hello, this is ' . __METHOD__ . '<br>';
    }

    public function testAction()
    {
        echo 'hello, this is ' . __METHOD__ . '<br>';
    }

    public function aboutAction()
    {
        echo 'hello, this is about page';
    }
}
