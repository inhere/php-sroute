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
        $this->expectException(\InvalidArgumentException::class);
        AbstractRouter::validateArguments(null, null);
    }
}
