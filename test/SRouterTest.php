<?php

namespace Inhere\Route\Test;

use Inhere\Route\Route;
use Inhere\Route\Router;
use Inhere\Route\SRouter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Inhere\Route\SRouter
 */
class SRouterTest extends TestCase
{
    private function registerRoutes()
    {
        SRouter::get('/', 'handler0');
        SRouter::get('/test', 'handler1');
        SRouter::get('/{name}', 'handler2');
        SRouter::get('/hi/{name}', 'handler3', [
            'name' => '\w+',
        ]);
    }

    public function testStaticRoute()
    {
        $this->registerRoutes();

        /** @var Route $route */
        list($status, $path, $route) = SRouter::match('/', 'GET');

        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('/', $path);
        $this->assertSame('handler0', $route->getHandler());
    }

    public function testParamRoute()
    {
        $this->registerRoutes();

        /** @var Route $route */

        // route: /{name}
        list($status, $path, $route) = SRouter::match('/tom', 'GET');

        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('/tom', $path);
        $this->assertSame('handler2', $route->getHandler());

        // route: /hi/{name}
        list($status, $path, $route) = SRouter::match('/hi/tom', 'GET');

        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('/hi/tom', $path);
        $this->assertSame('/hi/{name}', $route->getPath());
        $this->assertSame('handler3', $route->getHandler());
    }
}
