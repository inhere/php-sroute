<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-02-09
 * Time: 12:00
 */

namespace Inhere\Route\Test;

use Inhere\Route\Router;
use Inhere\Route\PreMatchRouter;
use Inhere\Route\RouterManager;
use PHPUnit\Framework\TestCase;

/**
 * Class RouterManagerTest
 * @package Inhere\Route\Test
 * @covers \Inhere\Route\RouterManager
 */
class RouterManagerTest extends TestCase
{
    /** @var RouterManager */
    private $manager;

    protected function setUp()
    {
        $configs = [
            'default' => 'main-site',
            'main-site' => [
                'driver' => 'default',
                'conditions' => [
                    'domains' => ['abc.com', 'www.abc.com']
                ],
            ],
            'doc-site' => [
                'driver' => 'cached',
                'options' => [

                ],
                'conditions' => [
                    'domains' => 'doc.abc.com'
                ],
            ],
            'blog-site' => [
                'driver' => 'preMatch',
                'options' => [
                    'path' => '/test',
                    'method' => 'GET',
                ],
                'conditions' => [
                    'schemes' => 'http',
                    'domains' => 'blog.abc.com'
                ],
            ],
        ];

        $this->manager = new RouterManager($configs);
    }

    public function testGet()
    {
        $router = $this->manager->get([
            'scheme' => 'http',
            'domain' => 'blog.abc.com',
        ]);

        $this->assertSame('blog-site', $router->getName());
        $this->assertInstanceOf(PreMatchRouter::class, $router);
    }

    public function testGetByName()
    {
        $router = $this->manager->getByName('blog-site');

        $this->assertSame('blog-site', $router->getName());
    }

    public function testGetDefault()
    {
        $router = $this->manager->getDefault();

        $this->assertSame('default', $router->getName());
        $this->assertInstanceOf(Router::class, $router);
    }
}
