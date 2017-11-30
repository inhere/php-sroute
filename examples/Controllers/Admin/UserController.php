<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/30
 * Time: 下午5:01
 */

namespace Inhere\Route\Examples\Controllers\Admin;

/**
 * Class UserController
 * @package Inhere\Route\Examples\Controllers\Admin
 */
class UserController
{
    public function indexAction()
    {
        echo 'hello, this is ' . __METHOD__ . '<br>';
    }

    public function infoAction()
    {
        echo 'hello, this is ' . __METHOD__ . '<br>';
    }
}
