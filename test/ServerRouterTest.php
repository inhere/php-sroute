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
 */
class ServerRouterTest extends TestCase
{
    public function testRouteCache()
    {
        $router = new ServerRouter();
        $router->get('/test1[/optional]', 'handler');
        $router->get('/{name}', 'handler2');
        $router->get('/hi/{name}', 'handler3', [
            'name' => '\w+',
        ]);
        $router->post('/hi/{name}', 'handler4');
        $router->put('/hi/{name}', 'handler5');

        $this->assertTrue(4 < $router->count());

        /** @var Route $route */
        list($status, $path, $route) = $router->match('/hi/tom');

        $this->assertSame(ServerRouter::FOUND, $status);
        $this->assertSame('/hi/tom', $path);
        $this->assertSame('handler3', $route->getHandler());

        $this->assertEquals(1, $router->getCacheCount());

        $cachedRoutes = $router->getCacheRoutes();
        $this->assertCount(1, $cachedRoutes);

        $cached = \array_shift($cachedRoutes);
        $this->assertEquals($route, $cached);
    }

}
