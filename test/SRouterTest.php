<?php declare(strict_types=1);

namespace Inhere\RouteTest;

use Inhere\Route\Route;
use Inhere\Route\Router;
use Inhere\Route\SRouter;
use PHPUnit\Framework\TestCase;

/**
 * Class SRouterTest
 * @package Inhere\RouteTest
 */
class SRouterTest extends TestCase
{
    private function registerRoutes(): void
    {
        SRouter::get('/', 'handler0');
        SRouter::get('/test', 'handler1');
        SRouter::get('/{name}', 'handler2');
        SRouter::get('/hi/{name}', 'handler3', [
            'name' => '\w+',
        ]);
    }

    public function testBasic(): void
    {
        $router = Router::create(['name' => 'myRouter']);
        SRouter::setRouter($router);

        $r = SRouter::getRouter();
        $this->assertSame($router->getName(), $r->getName());

        $this->expectExceptionMessage('call invalid method: notExist');
        SRouter::notExist();
    }

    public function testStaticRoute(): void
    {
        $this->registerRoutes();

        /** @var Route $route */
        [$status, $path, $route] = SRouter::match('/');

        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('/', $path);
        $this->assertSame('handler0', $route->getHandler());
    }

    public function testParamRoute(): void
    {
        $this->registerRoutes();

        /** @var Route $route */

        // route: /{name}
        [$status, $path, $route] = SRouter::match('/tom');

        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('/tom', $path);
        $this->assertSame('handler2', $route->getHandler());

        // route: /hi/{name}
        [$status, $path, $route] = SRouter::match('/hi/tom');

        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('/hi/tom', $path);
        $this->assertSame('/hi/{name}', $route->getPath());
        $this->assertSame('handler3', $route->getHandler());
    }
}
