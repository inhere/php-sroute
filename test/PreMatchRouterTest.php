<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-02-01
 * Time: 15:13
 */

namespace Inhere\RouteTest;

use Inhere\Route\PreMatchRouter;
use Inhere\Route\Route;
use Inhere\Route\RouterInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class PreMatchRouterTest
 * @package Inhere\RouteTest
 * @covers \Inhere\Route\PreMatchRouter
 */
class PreMatchRouterTest extends TestCase
{
    private function createRouter($p, $m): PreMatchRouter
    {
        $r = new PreMatchRouter([], $p, $m);

        $r->get('/', 'handler0');
        $r->get('/test', 'handler1');
        $r->get('/test1[/optional]', 'handler');
        $r->get('/{name}', 'handler2');
        $r->get('/hi/{name}', 'handler3', [
            'params' => [
                'name' => '\w+',
            ]
        ]);
        $r->post('/hi/{name}', 'handler4');
        $r->put('/hi/{name}', 'handler5');

        return $r;
    }

    public function testRouteCacheExists(): void
    {
        $p      = '/test';
        $m      = 'GET';
        $router = $this->createRouter($p, $m);

        $this->assertInstanceOf(Route::class, $router->getPreFounded());

        $ret = $router->match($p);
        $this->assertCount(3, $ret);

        /** @var Route $route */
        [$status, $path, $route] = $ret;

        $this->assertSame(RouterInterface::FOUND, $status);
        $this->assertSame($p, $path);
        $this->assertSame('handler1', $route->getHandler());
    }
}
