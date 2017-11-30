<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午7:52
 */

use Inhere\Route\SRouter;

require __DIR__ . '/simple-loader.php';

$runTime = 10;
$count = $seconds = 0;
$time = microtime(true);
SRouter::get('/', 'handler0');

echo 'start time: ' . $time . PHP_EOL;

while($seconds < $runTime) {
    $count++;

    $route = SRouter::match('/', 'GET');

    if($time + 1 < microtime(true)) {
        $time = microtime(true);
        $seconds++;
        echo $count . ' routes dispatched per second' . "\r";
        $count = 0;
    }
}

echo PHP_EOL . 'end time: ' . $time . PHP_EOL;
