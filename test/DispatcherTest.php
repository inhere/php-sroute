<?php
namespace Inhere\Route\Test;

use Inhere\Route\Router;
use PHPUnit\Framework\TestCase;
use Inhere\Route\Dispatcher\Dispatcher;

/**
 * @covers \Inhere\Route\Dispatcher\Dispatcher
 */
class DispatcherTest extends TestCase
{
    /**
     * @throws \Throwable
     */
    public function testDispatcher()
    {
        $handler = function(array $args = []) {
            return \sprintf('hello, welcome. args: %s', \json_encode($args));
        };

        $router = new Router();
        $router->get('/', $handler);
        $router->get('/user/info[/{int}]', $handler);
        $router->get('/my[/{name}[/{age}]]', $handler, [
            'params' => [
                'age' => '\d+'
            ],
            'defaults' => [
                'name' => 'God',
                'age' => 25,
            ]
        ]);

        $d = new Dispatcher();
        $d->setRouter($router);

        $ret = $d->dispatchUri('/', 'get');
        $this->assertStringStartsWith('hello', $ret);
        $this->assertStringEndsWith('[]', $ret);

        $ret = $d->dispatchUri('/user/info', 'get');
        $this->assertStringStartsWith('hello', $ret);
        $this->assertStringEndsWith('[]', $ret);

        $ret = $d->dispatchUri('/user/info/45', 'get');
        $this->assertStringStartsWith('hello', $ret);
        $this->assertStringEndsWith('"45"}', $ret);

        $ret = $d->dispatchUri('/my', 'get');
        $this->assertStringStartsWith('hello', $ret);
        $this->assertStringEndsWith('25}', $ret);
        $this->assertContains('{"name":"God","age":25}', $ret);

        $ret = $d->dispatchUri('/my/tom', 'get');
        $this->assertStringStartsWith('hello', $ret);
        $this->assertStringEndsWith('25}', $ret);
        $this->assertContains('{"name":"tom","age":25}', $ret);

        $ret = $d->dispatchUri('/my/tom/45', 'get');
        $this->assertStringStartsWith('hello', $ret);
        $this->assertStringEndsWith('"45"}', $ret);
        $this->assertContains('{"name":"tom","age":"45"}', $ret);
    }
}
