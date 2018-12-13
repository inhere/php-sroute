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
    public function testRouter()
    {
        $r = new Router([]);
        $r->get('/', 'handler0');
        $r->get('/test', 'handler1');
        $r->get('/test1[/optional]', 'handler');
        $r->get('/my[/{name}[/{age}]]', 'handler2', [
            'age' => '\d+'
        ]);

        $r->get('/hi/{name}', 'handler3', [
            'name' => '\w+',
        ]);

        $r->post('/hi/{name}', 'handler4');
        $r->put('/hi/{name}', 'handler5');

        $this->assertSame(7, $r->count());

    }

    public function testAddRoute()
    {
        $router = Router::create();

        $r1 = Route::create('GET', '/path1', 'handler0');
        $r1->setName('r1');
        $router->addRoute($r1);

        $r2 = Route::create('GET', '/path2', 'handler2');
        $r2->namedTo('r2', $router, true);

        $r3 = $router->add('get', '/path3', 'handler3');
        $r3->namedTo('r3', $router);

        $r4 = $router->add('get', '/path3', 'handler3', [], ['name' => 'r4']);

        $this->assertEmpty($router->getRoute('not-exist'));
        $this->assertEquals($r1, $router->getRoute('r1'));
        $this->assertEquals($r2, $router->getRoute('r2'));
        $this->assertEquals($r4, $router->getRoute('r4'));

        $ret = $router->getRoute('r3');
        $this->assertEquals($r3, $ret);
        $this->assertEquals([
            'path' => '/path3',
            'method' => 'GET',
            'handlerName' => 'handler3',
        ], $ret->info());

    }

    public function testStaticRoute()
    {
        /** @var Router $router */
        $router = Router::create();
        $router->get('/', 'handler0');
        $router->get('/about', 'handler1');

        /** @var Route $route */
        list($status, $path, $route) = $router->match('/', 'GET');

        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('/', $path);
        $this->assertSame('handler0', $route->getHandler());

        list($status, $path, $route) = $router->match('about', 'GET');
        $this->assertSame(Router::FOUND, $status);
        $this->assertSame('/about', $path);
        $this->assertSame('handler1', $route->getHandler());

        list($status, $path,) = $router->match('not-exist', 'GET');

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

    public function testMiddleware()
    {
        $router = Router::create();
        $router->use('func0', 'func1');

        // global middleware
        $this->assertSame(['func0', 'func1'], $router->getChains());

        $router->group('/grp', function (Router $r) use (&$r1) {
            $r1 = $r
                ->get('/path', 'h0')
                ->push('func2');
        }, ['func3', 'func4']);

        /** @var Route $route */
        list($status, , $route) = $router->match('/grp/path', 'get');

        $this->assertSame(Router::FOUND, $status);
        $this->assertSame($r1, $route);
        $this->assertSame(['func3', 'func4', 'func2'], $route->getChains());
    }
}
