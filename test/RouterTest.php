<?php

namespace Inhere\Route\Test;

use Inhere\Route\Route;
use Inhere\Route\Router;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Inhere\Route\Router
 */
class RouterTest extends TestCase
{
    private function createRouter(array $config = [])
    {
        $r = new Router($config);
        $r->get('/', 'handler0');
        $r->get('/test', 'handler1');

        $r->get('/test1[/optional]', 'handler');

        $r->get('/my[/{name}[/{age}]]', 'handler2', [
            'age' => '\d+'
        ])->setOptions([
            'defaults' => [
                'name' => 'God',
                'age' => 25,
            ]
        ]);

        $r->get('/hi/{name}', 'handler3', [
            'name' => '\w+',
        ]);

        $r->post('/hi/{name}', 'handler4');
        $r->put('/hi/{name}', 'handler5');

        return $r;
    }

    public function testAddRoutes()
    {
        $router = $this->createRouter();

        $this->assertTrue(4 < $router->count());
        $this->assertCount(2, $router->getStaticRoutes());
    }

    public function testStaticRoute()
    {
        /** @var Router $router */
        $router = Router::create();
        $router->get('/', 'handler0');
        $router->get('/about', 'handler1');

        $ret = $router->match('/', 'GET');
        $this->assertCount(3, $ret);

        /** @var Route $route */
        list($status, $path, $route) = $ret;

        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('/', $path);
        $this->assertSame('handler0', $route->getHandler());

        $ret = $router->match('about', 'GET');
        $this->assertCount(3, $ret);

        /** @var Route $route */
        list($status, $path, $route) = $ret;

        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('/about', $path);
        $this->assertSame('handler1', $route->getHandler());

        $ret = $router->match('not-exist', 'GET');
        $this->assertCount(3, $ret);

        /** @var Route $route */
        list($status, $path,) = $ret;

        $this->assertSame(Router::NOT_FOUND, $status);
        $this->assertSame('/not-exist', $path);
    }

    public function testOptionalParamRoute()
    {
        /** @var Router $router */
        $router = Router::create();
        $router->get('/about[.html]', 'handler0');
        $router->get('/test1[/optional]', 'handler1');

        /** @var Route $route */

        // route: '/about'
        list($status, , $route) = $router->match('/about', 'GET');
        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('handler0', $route->getHandler());

        // route: '/about.html'
        list($status, , $route) = $router->match('/about.html', 'GET');
        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('handler0', $route->getHandler());

        // route: '/test1'
        list($status, , $route) = $router->match('/test1', 'GET');
        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('handler1', $route->getHandler());

        // route: '/test1/optional'
        list($status, , $route) = $router->match('/test1/optional', 'GET');
        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('handler1', $route->getHandler());

        // route: '/test1/other'
        list($status, ,) = $router->match('/test1/other', 'GET');
        $this->assertSame(Router::NOT_FOUND, $status);
    }

    public function testParamRoute()
    {
        $router = Router::create();
        /** @var Route $route */
        $route = $router->get('/hi/{name}', 'handler3', [
            'name' => '\w+',
        ]);

        $this->assertEquals('#^/hi/(\w+)$#', $route->getPathRegex());

        // int param
        list($status, $path, $route) = $router->match('/hi/3456', 'GET');

        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('/hi/3456', $path);
        $this->assertSame('/hi/{name}', $route->getPath());
        $this->assertSame('handler3', $route->getHandler());
        $this->assertSame('3456', $route->getParam('name'));

        // string param
        list($status, $path, $route) = $router->match('/hi/tom', 'GET');

        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('/hi/tom', $path);
        $this->assertSame('/hi/{name}', $route->getPath());
        $this->assertSame('handler3', $route->getHandler());
        $this->assertArrayHasKey('name', $route->getParams());
        $this->assertSame('tom', $route->getParam('name'));

        // invalid
        list($status, ,) = $router->match('/hi/dont-match', 'GET');
        $this->assertSame(Router::NOT_FOUND, $status);
    }

    public function testComplexRoute()
    {
        $router = Router::create();
        $router->handleMethodNotAllowed = true;

        /** @var Route $route */
        $route = $router->get('/my[/{name}[/{age}]]', 'handler2', [
            'age' => '\d+'
        ])->setOptions([
            'defaults' => [
                'name' => 'God',
                'age' => 25,
            ]
        ]);

        $this->assertSame('handler2', $route->getHandler());
        $this->assertContains('age', $route->getPathVars());
        $this->assertContains('name', $route->getPathVars());

        // access '/my'
        list($status, $path, $route) = $router->match('/my', 'GET');

        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('/my', $path);
        $this->assertSame('handler2', $route->getHandler());
        $this->assertArrayHasKey('defaults', $route->getOptions());
        $this->assertArrayHasKey('age', $route->getParams());
        $this->assertArrayHasKey('name', $route->getParams());
        $this->assertSame('God', $route->getParam('name'));
        $this->assertSame(25, $route->getParam('age'));

        // access '/my/tom'
        list($status, $path, $route) = $router->match('/my/tom', 'GET');

        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('/my/tom', $path);
        $this->assertSame('handler2', $route->getHandler());
        $this->assertSame('tom', $route->getParam('name'));
        $this->assertSame(25, $route->getParam('age'));

        // access '/my/tom/45'
        list($status, $path, $route) = $router->match('/my/tom/45', 'GET');

        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('/my/tom/45', $path);
        $this->assertSame('handler2', $route->getHandler());
        $this->assertSame('tom', $route->getParam('name'));
        $this->assertSame(45, (int)$route->getParam('age'));

        // use HEAD
        $ret = $router->match('/my/tom/45', 'HEAD');
        $this->assertSame(Router::FOUND, $ret[0]);

        // not allowed
        $ret = $router->match('/my/tom/45', 'POST');
        $this->assertSame(Router::METHOD_NOT_ALLOWED, $ret[0]);
        $this->assertEquals(['GET'], $ret[2]);

        // not found
        $ret = $router->match('/my/tom/not-match', 'GET');
        $this->assertSame(Router::NOT_FOUND, $ret[0]);
    }

    public function testNotFound()
    {
        $router = Router::create();
        $router->get('/hi/{name}', 'handler3', [
            'name' => '\w+',
        ]);

        list($status, $path,) = $router->match('/not-exist', 'GET');
        $this->assertSame(Router::NOT_FOUND, $status);
        $this->assertSame('/not-exist', $path);

        list($status, $path,) = $router->match('/hi', 'GET');
        $this->assertSame(Router::NOT_FOUND, $status);
        $this->assertSame('/hi', $path);
    }

    public function testRequestMethods()
    {
        $router = Router::create([
            'handleMethodNotAllowed' => true,
        ]);
        $router->get('/hi/{name}', 'handler3', [
            'name' => '\w+',
        ]);
        $router->map(['POST', 'PUT'], '/hi/{name}', 'handler4');

        /** @var Route $route */

        // GET
        list($status, , $route) = $router->match('/hi/tom', 'get');
        $this->assertSame(Router::FOUND, $status);
        $this->assertArrayHasKey('name', $route->getParams());
        $this->assertSame('handler3', $route->getHandler());

        // POST
        list($status, , $route) = $router->match('/hi/tom', 'post');
        $this->assertSame(Router::FOUND, $status);
        $this->assertArrayHasKey('name', $route->getParams());
        $this->assertSame('handler4', $route->getHandler());
        $this->assertEquals('tom', $route->getParam('name'));

        // PUT
        list($status, , $route) = $router->match('/hi/john', 'put');
        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('handler4', $route->getHandler());
        $this->assertArrayHasKey('name', $route->getParams());
        $this->assertEquals('john', $route->getParam('name'));

        // DELETE
        list($status, , $methods) = $router->match('/hi/tom', 'delete');
        $this->assertSame(Router::METHOD_NOT_ALLOWED, $status);
        $this->assertCount(3, $methods);
        $this->assertEquals(['GET', 'POST', 'PUT'], $methods);
    }
}
