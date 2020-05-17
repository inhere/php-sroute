<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-02-01
 * Time: 12:59
 */

namespace Inhere\RouteTest;

use Inhere\Route\Route;
use Inhere\Route\ServerRouter;
use PHPUnit\Framework\TestCase;
use function array_shift;

/**
 * Class ServerRouterTest
 * @package Inhere\RouteTest
 */
class ServerRouterTest extends TestCase
{
    public function testRouteCache(): void
    {
        $router = new ServerRouter([
            'tmpCacheNumber' => 10,
        ]);
        $router->get('/path', 'handler0');
        $router->get('/test1[/optional]', 'handler');
        $router->get('/{name}', 'handler2');
        $router->get('/hi/{name}', 'handler3', [
            'name' => '\w+',
        ]);
        $router->post('/hi/{name}', 'handler4');
        $router->put('/hi/{name}', 'handler5');

        $this->assertTrue(4 < $router->count());

        /** @var Route $route */
        [$status, $path, $route] = $router->match('/hi/tom');
        $this->assertSame(ServerRouter::FOUND, $status);
        $this->assertSame('/hi/tom', $path);
        $this->assertSame('handler3', $route->getHandler());

        $this->assertEquals(1, $router->getCacheCount());

        $cachedRoutes = $router->getCacheRoutes();
        $this->assertCount(1, $cachedRoutes);

        $cached = array_shift($cachedRoutes);
        $this->assertEquals($route, $cached);

        // repeat request
        /** @var Route $route */
        [$status, $path, $route] = $router->match('/hi/tom');
        $this->assertSame(ServerRouter::FOUND, $status);
        $this->assertSame('/hi/tom', $path);
        $this->assertSame('handler3', $route->getHandler());

        // match use HEAD
        [$status, ,] = $router->match('/path', 'HEAD');
        $this->assertSame(ServerRouter::FOUND, $status);

        // match not exist
        [$status, $path,] = $router->match('/not/exist');
        $this->assertSame(ServerRouter::NOT_FOUND, $status);
        $this->assertSame('/not/exist', $path);

        // add fallback route.
        $router->any('/*', 'fb_handler');
        [$status, $path,] = $router->match('/not/exist');
        $this->assertSame(ServerRouter::FOUND, $status);
        $this->assertSame('/not/exist', $path);
    }
}
