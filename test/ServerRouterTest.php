<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-02-01
 * Time: 12:59
 */

namespace Inhere\Route\Test;

use Inhere\Route\Route;
use Inhere\Route\ServerRouter;
use PHPUnit\Framework\TestCase;

/**
 * Class ServerRouterTest
 * @package Inhere\Route\Test
 * @covers \Inhere\Route\ServerRouter
 */
class ServerRouterTest extends TestCase
{
    private function createRouter()
    {
        $r = new ServerRouter();
        $r->get('/', 'handler0');
        $r->get('/test', 'handler1');
        $r->get('/test1[/optional]', 'handler');
        $r->get('/{name}', 'handler2');
        $r->get('/hi/{name}', 'handler3', [
                'name' => '\w+',
        ]);
        $r->post('/hi/{name}', 'handler4');
        $r->put('/hi/{name}', 'handler5');

        return $r;
    }

    public function testRouteCacheExists()
    {
        $router = $this->createRouter();

        $this->assertTrue(4 < $router->count());
        $this->assertCount(2, $router->getStaticRoutes());

        $ret = $router->match('/hi/tom');

        $this->assertCount(3, $ret);
        $this->assertCount(1, $router->getCacheRoutes());

        /** @var Route $route */
        list($status, $path, $route) = $ret;

        $this->assertSame(ServerRouter::FOUND, $status);
        $this->assertSame('/hi/tom', $path);
        $this->assertSame('handler3', $route->getHandler());
    }

}
