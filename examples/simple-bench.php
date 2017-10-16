<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/14
 * Time: 下午7:52
 */

use Inhere\Route\SRouter;

// include __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/simple-loader.php';

function getRandomParts()
{
    $rand = md5(uniqid(mt_rand(), true));
    
    return [
        substr($rand, 0, 10),
        substr($rand, -10),
    ];
}

function setupBenchSRouter($benchmark) {
    $argNum = 9;
    $routeNumbers = 100;

    $argStr = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $argNum)));
    $firstStr = $lastStr = '';

    SRouter::map('GET', '/', 'handler');

    for ($i = 0; $i < $routeNumbers; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argStr . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('{', '}'), '', $str);
        }

        $lastStr = str_replace(array('{', '}'), '', $str);
        SRouter::map('GET', $str, 'handler' . $i);
    }

    $benchmark->register(sprintf('SRouter - last route (%s routes)', $routeNumbers), function () use ($lastStr) {
        $route = SRouter::match($lastStr, 'GET');
    });
    $benchmark->register(sprintf('SRouter - unknown route (%s routes)', $routeNumbers), function () {
        $route = SRouter::match('/not-match-any', 'GET');
    });
}

$runTime = 10;
$count = $seconds = 0;
$time = microtime(true);

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
