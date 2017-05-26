<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/30
 * Time: 下午5:01
 */

namespace inhere\sroute\examples\controllers\admin;

/**
 * Class UserController
 * @package inhere\sroute\examples\controllers\admin
 */
class UserController
{
    public function index()
    {
        echo 'hello, this is ' . __METHOD__ . "<br>";
    }

    public function info()
    {
        echo 'hello, this is ' . __METHOD__ . "<br>";
    }
}