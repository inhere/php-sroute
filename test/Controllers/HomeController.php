<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/28
 * Time: 上午12:14
 */

namespace Inhere\RouteTest\Controllers;

/**
 * Class HomeController
 * @package Inhere\Route\example\controllers
 */
class HomeController
{
    public function indexAction(): void
    {
        echo 'hello, this is ' . __METHOD__ . '<br>';
    }

    public function testAction(): void
    {
        echo 'hello, this is ' . __METHOD__ . '<br>';
    }

    public function aboutAction(): void
    {
        echo 'hello, this is about page';
    }
}
