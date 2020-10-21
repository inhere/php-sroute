<?php declare(strict_types=1);

namespace Inhere\RouteTest\Dispatcher;

use Inhere\Route\Dispatcher\SimpleDispatcher;
use Inhere\Route\Router;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Class SimpleDispatcherTest
 *
 * @package Inhere\RouteTest\Dispatcher
 */
class SimpleDispatcherTest extends TestCase
{
    private static $buffer = '';

    public static function resetBuffer(): void
    {
        self::$buffer = '';
    }

    /**
     * @throws Throwable
     */
    public function testDispatchUri(): void
    {
        $router = new Router();
        $router->get('/', static function () {
            self::$buffer = 'hello';
        });

        $d = new SimpleDispatcher([], $router);

        $bakServer = $_SERVER;

        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $d->dispatchUri();

        $this->assertSame('hello', self::$buffer);

        $_SERVER = $bakServer;
    }

    /**
     * @throws Throwable
     */
    public function testDispatchUri2(): void
    {
        $router = new Router();
        $router->get('/', static function () {
            self::$buffer = 'hello';
        });

        $d = SimpleDispatcher::create([], $router);

        $bakServer = $_SERVER;

        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $d->dispatchUri();

        $this->assertSame('hello', self::$buffer);

        $_SERVER = $bakServer;
    }
}
