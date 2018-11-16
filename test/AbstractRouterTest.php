<?php
namespace Inhere\Route\Test;

use PHPUnit\Framework\TestCase;
use Inhere\Route\AbstractRouter;

/**
 * @covers \Inhere\Route\AbstractRouter
 */
class AbstractRouterTest extends TestCase
{
    public function testStaticRouteCheck()
    {
        $ret = AbstractRouter::isStaticRoute('/abc');
        $this->assertTrue($ret);

        $ret = AbstractRouter::isStaticRoute('/hi/{name}');
        $this->assertFalse($ret);

        $ret = AbstractRouter::isStaticRoute('/hi/[tom]');
        $this->assertFalse($ret);
    }

}
