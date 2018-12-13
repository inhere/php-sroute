<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-12-13
 * Time: 23:53
 */

namespace Inhere\Route\Test;

use Inhere\Route\Helper\RouteHelper;
use PHPUnit\Framework\TestCase;

/**
 * Class RouteHelperTest
 * @package Inhere\Route\Test
 */
class RouteHelperTest extends TestCase
{
    public function testIsStaticRoute()
    {
        $ret = RouteHelper::isStaticRoute('/abc');
        $this->assertTrue($ret);

        $ret = RouteHelper::isStaticRoute('/hi/{name}');
        $this->assertFalse($ret);

        $ret = RouteHelper::isStaticRoute('/hi/[tom]');
        $this->assertFalse($ret);
    }

}
