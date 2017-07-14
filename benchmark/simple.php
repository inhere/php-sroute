<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午7:52
 */

use inhere\sroute\SRoute;

// include __DIR__ . '/../../vendor/autoload.php';
require dirname(__DIR__) . '/examples/simple-loader.php';

SRoute::get('/test', function(){
});
SRoute::get('/test2', function(){
});
SRoute::get('/test3', function(){
});
SRoute::get('/test1/(\w+)', function(){
});
SRoute::get('/test2/(\w+)', function(){
});
SRoute::get('/test3/(\w+)', function(){
});

$runTime = 10;
$time = microtime(true);
$count = 0;
$seconds = 0;

while($seconds < $runTime) {
    $count++;

    SRoute::dispatchTo('/test2/joe');

    if($time + 1 < microtime(true)) {
        $time = microtime(true);
        $seconds++;
        echo $count . ' routes dispatched per second' . "\r";
        $count = 0;
    }
}

echo PHP_EOL;
