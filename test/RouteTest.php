<?php
namespace Inhere\Route\Test;

use PHPUnit\Framework\TestCase;
use Inhere\Route\Route;

/**
 * @covers \Inhere\Route\Route
 */
class RouteTest extends TestCase
{
    public function testParseParamRoute()
    {
        // 抽象方法才需要配置
        // $stub->expects($this->any())
        //     ->method('parseParamRoute')
        //     ->will($this->returnValue('foo'));

        $conf = [
            'handler' => 'some_handler'
        ];

        $path = '/im/{name}/{age}';
        $stub = Route::create('GET', $path, 'my_handler');
        $first = $stub->parseParam([]);
        $this->assertCount(2, $stub->getPathVars());
        $this->assertEquals('im', $first);// first node
        $this->assertEquals('/im/', $stub->getPathStart());

        $path = '/path/to/{name}';
        $stub = Route::create('GET', $path, 'my_handler');
        $first = $stub->parseParam([]);
        $this->assertEquals('path', $first);
        $this->assertEquals('/path/to/', $stub->getPathStart());

        $path = '/path/to/some/{name}';
        $stub = Route::create('GET', $path, 'my_handler');
        $first =  $stub->parseParam([]);
        $this->assertEquals('path', $first);
        $this->assertEquals('/path/to/some/', $stub->getPathStart());

        $path = '/hi/{name}';
        $stub = Route::create('GET', $path, 'my_handler');
        $first =  $stub->parseParam([]);
        $this->assertEquals('hi', $first);
        $this->assertEquals('/hi/', $stub->getPathStart());

        $path = '/hi[/{name}]';
        $stub = Route::create('GET', $path, 'my_handler');
        $first =  $stub->parseParam([]);
        $this->assertEquals('', $first);
        $this->assertEquals('/hi', $stub->getPathStart());

        $path = '/hi[/tom]';
        $stub = Route::create('GET', $path, 'my_handler');
        $first =  $stub->parseParam([]);
        $this->assertEquals('', $first);
        $this->assertEquals('/hi', $stub->getPathStart());

        $path = '/hi/[tom]';
        $stub = Route::create('GET', $path, 'my_handler');
        $first =  $stub->parseParam([]);
        $this->assertEquals('hi', $first);
        $this->assertEquals('/hi/', $stub->getPathStart());

        $path = '/{category}';
        $stub = Route::create('GET', $path, 'my_handler');
        $first =  $stub->parseParam([]);
        $this->assertEquals('', $first);
        $this->assertNull($stub->getPathStart());
        $this->assertEquals('', $stub->getPathStart());

        $path = '/blog-{category}';
        $stub = Route::create('GET', $path, 'my_handler');
        $first =  $stub->parseParam([]);
        $this->assertEquals('', $first);
        $this->assertEquals('/blog-', $stub->getPathStart());

        // var_dump($ret);die;
    }
}
