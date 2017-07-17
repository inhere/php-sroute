<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午7:52
 */

use inhere\sroute\SRouter;

// include __DIR__ . '/../../vendor/autoload.php';
require dirname(__DIR__) . '/examples/simple-loader.php';

SRouter::get('/test', function(){
});
SRouter::get('/test2', function(){
});
SRouter::get('/test3', function(){
});
SRouter::get('/test1/{name}', function(){
});
SRouter::get('/test2/{name}', function(){
});
SRouter::get('/test3/{name}', function(){
});

$runTime = 10;
$time = microtime(true);
$count = 0;
$seconds = 0;

while($seconds < $runTime) {
    $count++;

    SRouter::dispatchTo('/test2/joe');

    if($time + 1 < microtime(true)) {
        $time = microtime(true);
        $seconds++;
        echo $count . ' routes dispatched per second' . "\r";
        $count = 0;
    }
}

echo PHP_EOL;
