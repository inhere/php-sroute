<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/30
 * Time: 下午5:01
 */

namespace Inhere\Route\Example\Controllers\Admin;

/**
 * Class UserController
 * @package Inhere\Route\Example\Controllers\Admin
 */
class UserController
{
    public function indexAction(): void
    {
        echo 'hello, this is ' . __METHOD__ . '<br>';
    }

    public function infoAction(): void
    {
        echo 'hello, this is ' . __METHOD__ . '<br>';
    }
}
