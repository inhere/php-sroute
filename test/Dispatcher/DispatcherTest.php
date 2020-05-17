<?php declare(strict_types=1);

namespace Inhere\RouteTest\Dispatcher;

use Inhere\Route\Dispatcher\Dispatcher;
use Inhere\Route\Router;
use PHPUnit\Framework\TestCase;
use Throwable;
use function implode;
use function json_encode;
use function sprintf;

/**
 * Class DispatcherTest
 */
class DispatcherTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function testDispatcher(): void
    {
        $handler = static function (array $args = []) {
            return sprintf('hello, welcome. args: %s', json_encode($args));
        };

        $router = new Router();

        $router->handleMethodNotAllowed = true;
        $router->get('/', $handler);
        $router->get('/user/info[/{int}]', $handler);
        $router->get('/my[/{name}[/{age}]]', $handler, [
            'age' => '\d+'
        ])->setOptions([
            'defaults' => [
                'name' => 'God',
                'age'  => 25,
            ]
        ]);

        $d = new Dispatcher();

        // add events
        $d->on(Dispatcher::ON_NOT_FOUND, static function () {
            return 'TEST: page not found';
        });
        $d->on(Dispatcher::ON_METHOD_NOT_ALLOWED, static function ($path, $m, $ms) {
            return sprintf('TEST: %s %s is not allowed, allowed methods: %s', $m, $path, implode(',', $ms));
        });
        $d->setRouter($router);

        // not found
        $ret = $d->dispatchUri('/not-exist', 'get');
        $this->assertSame('TEST: page not found', $ret);

        // not allowed
        $ret = $d->dispatchUri('/', 'post');
        $this->assertSame('TEST: POST / is not allowed, allowed methods: GET', $ret);

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
        $this->assertStringEndsWith('{"name":"God","age":25}', $ret);

        $ret = $d->dispatchUri('/my/tom', 'get');
        $this->assertStringStartsWith('hello', $ret);
        $this->assertStringEndsWith('25}', $ret);
        $this->assertStringEndsWith('{"name":"tom","age":25}', $ret);

        $ret = $d->dispatchUri('/my/tom/45', 'get');
        $this->assertStringStartsWith('hello', $ret);
        $this->assertStringEndsWith('"45"}', $ret);
        $this->assertStringEndsWith('{"name":"tom","age":"45"}', $ret);
    }
}
