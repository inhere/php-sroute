<?php
namespace Inhere\Route\Tests;

use PHPUnit\Framework\TestCase;
use Inhere\Route\ORouter;

/**
 * @covers ORouter
 */
class ORouterTest extends TestCase
{
    private function createRouter()
    {
        $r = new ORouter();
        $r->get('/', 'handler0');
        $r->get('/test', 'handler1');
        $r->get('/{name}', 'handler2');
        $r->get('/hi/{name}', 'handler3', [
            'params' => [
                'name' => '\w+',
            ]
        ]);

        return $r;
    }

    public function testAddRoutes()
    {
        $router = $this->createRouter();

        $this->assertSame(4, $router->count());
        $this->assertCount(2, $router->getStaticRoutes());
        $this->assertCount(1, $router->getRegularRoutes());
        $this->assertCount(1, $router->getVagueRoutes());
    }
    
    public function testStaticRoute()
    {
        $router = $this->createRouter();

        // 1
        $ret = $router->match('/', 'GET');

        $this->assertCount(3, $ret);

        list($status, $path, $route) = $ret;

        $this->assertSame(ORouter::FOUND, $status);
        $this->assertSame('/', $path);
        $this->assertSame('handler0', $route['handler']);

    }

    public function testParamRoute()
    {
        $router = $this->createRouter();

        // route: /{name}
        $ret = $router->match('/tom', 'GET');

        $this->assertCount(3, $ret);

        list($status, $path, $route) = $ret;

        $this->assertSame(ORouter::FOUND, $status);
        $this->assertSame('/tom', $path);
        $this->assertSame('/{name}', $route['original']);
        $this->assertSame('handler2', $route['handler']);

        // route: /hi/{name}
        $ret = $router->match('/hi/tom', 'GET');

        $this->assertCount(3, $ret);

        list($status, $path, $route) = $ret;

        $this->assertSame(ORouter::FOUND, $status);
        $this->assertSame('/hi/tom', $path);
        $this->assertSame('/hi/{name}', $route['original']);
        $this->assertSame('handler3', $route['handler']);
    }
}
