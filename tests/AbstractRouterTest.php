<?php
namespace Inhere\Route\Tests;

use PHPUnit\Framework\TestCase;
use Inhere\Route\AbstractRouter;

/**
 * @covers AbstractRouter
 */
class AbstractRouterTest extends TestCase
{
    public function testValidateArguments()
    {
        $ret = AbstractRouter::validateArguments('get', 'handler0');
        $this->assertEquals($ret, 'GET');

        $this->expectException(\InvalidArgumentException::class);

        AbstractRouter::validateArguments(null, null);
    }
}
