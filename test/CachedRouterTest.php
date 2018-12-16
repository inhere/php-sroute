<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-12-17
 * Time: 00:10
 */

namespace Inhere\Route\Test;

use Inhere\Route\CachedRouter;
use Inhere\Route\Route;
use PHPUnit\Framework\TestCase;
use function Inhere\Route\createCachedRouter;

/**
 * Class CachedRouterTest
 * @package Inhere\Route\Test
 */
class CachedRouterTest extends TestCase
{
    public function testCacheRouter()
    {
        $cacheFile = __DIR__ . '/routes-cache.php';

        if (\file_exists($cacheFile)) {
            \unlink($cacheFile);
        }

        $config = [
            'cacheFile' => $cacheFile,
            'cacheEnable' => 1,
        ];
        $callback = function (CachedRouter $router) {
            $router->get('/path0', 'handler0');
        };

        $router = createCachedRouter($callback, $config);

        $this->assertFalse($router->isCacheLoaded());
        $this->assertTrue($router->isCacheExists());

        /** @var Route $route */
        list($sts, , $route) = $router->match('/path0');

        $this->assertSame(CachedRouter::FOUND, $sts);
        $this->assertSame('/path0', $route->getPath());

        // create again, will load caches.
        $router = createCachedRouter($callback, $config);

        $this->assertTrue($router->isCacheExists());
        $this->assertTrue($router->isCacheLoaded());

        /** @var Route $route */
        list($sts, , $route) = $router->match('/path0');

        $this->assertSame(CachedRouter::FOUND, $sts);
        $this->assertSame('/path0', $route->getPath());

        if (\file_exists($cacheFile)) {
            \unlink($cacheFile);
        }
    }
}
