<?php

/**
 * Benchmark Altorouter
 *
 * Usage: php ./tests/benchmark.php <iterations>
 *
 * Options:
 *
 * <iterations>:
 * The number of routes to map & match. Defaults to 1000.
 */

// require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/simple-loader.php';

global $argv;
$n = isset($argv[1]) ? (int)$argv[1] : 1000;

echo "There are generate $n routes. and dynamic route with 50% chance\n\n";

// generates a random request url
function random_request_url()
{
    $characters = 'abcdefghijklmnopqrstuvwxyz';
    $charactersLength = strlen($characters);
    $randomString = '/';
    $rand = random_int(5, 20);

    // create random path of 5-20 characters
    for ($i = 0; $i < $rand; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];

        if (random_int(1, 10) === 1) {
            $randomString .= '/';
        }
    }

    $v = random_int(1, 10);

    // add dynamic route with 50% chance
    if ($v <= 5) {
        $randomString = rtrim($randomString, '/') . '/{name}';
    }

    return $randomString;
}

// generate a random request method
function random_request_method()
{
    static $methods = ['GET', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
    $random_key = array_rand($methods);
    return $methods[$random_key];
}

function pretty_match_result($ret)
{
    $str = json_encode($ret, JSON_PRETTY_PRINT);

    return str_replace('\\', '', $str);
}

// prepare benchmark data
$requests = array();
for ($i = 0; $i < $n; $i++) {
    $requests[] = array(
        'method' => random_request_method(),
        'url' => random_request_url(),
    );
}

$router = new \Inhere\Route\CachedRouter([
    'cacheFile' => __DIR__ . '/cached/bench-routes-cache.php',
    'cacheEnable' => 0,
    // 'tmpCacheNumber' => 100,
    // 'notAllowedAsNotFound' => 1,
]);

// map requests
$start = microtime(true);
foreach ($requests as $r) {
    $router->map($r['method'], $r['url'], 'handler_func');
}
$end = microtime(true);
$map_time = $end - $start;
echo "Build time ($n routes): " . number_format($map_time, 6) . " seconds\n";

$r = $requests[0];
$uri = str_replace(['{', '}'], '', $r['url']);

// match first known route
$start = microtime(true);
$ret = $router->match($uri, $r['method']);
$end = microtime(true);
$matchTime = $end - $start;
echo 'Match time (first route): ' . number_format($matchTime, 6) . " seconds(URI: {$uri})\n";
// echo "Match result: \n" . pretty_match_result($ret) . "\n\n";

// pick random route to match
$r = $requests[random_int(0, $n)];
$uri = str_replace(['{', '}'], '', $r['url']);

// match random known route
$start = microtime(true);
$ret = $router->match($uri, $r['method']);
$end = microtime(true);
$matchTime = $end - $start;
echo 'Match time (random route): ' . number_format($matchTime, 6) . " seconds(URI: {$uri})\n" ;
// echo "Match result: \n" . pretty_match_result($ret) . "\n\n";

$r = $requests[$n-1];
$uri = str_replace(['{', '}'], '', $r['url']);

// match last known route
$start = microtime(true);
$ret = $router->match($uri, $r['method']);
$end = microtime(true);
$matchTime = $end - $start;
echo 'Match time (last route): ' . number_format($matchTime, 6) . " seconds(URI: {$uri})\n";
// echo "Match result: \n" . pretty_match_result($ret) . "\n\n";

// match un-existing route
$start = microtime(true);
$ret = $router->match('/55-foo-bar', 'GET');
$end = microtime(true);
$match_time_unknown_route = $end - $start;
echo 'Match time (unknown route): ' . number_format($match_time_unknown_route, 6) . " seconds\n";
// echo "Match result: \n" . pretty_match_result($ret) . "\n\n";

// print totals
echo 'Total time: ' . number_format($map_time + $matchTime + $match_time_unknown_route,
        6) . ' seconds' . PHP_EOL;
echo 'Memory usage: ' . round(memory_get_usage() / 1024) . ' KB' . PHP_EOL;
echo 'Peak memory usage: ' . round(memory_get_peak_usage(true) / 1024) . ' KB' . PHP_EOL;



/*
// 2017.12.3
$ php examples/benchmark.php
There are generate 1000 routes. and dynamic route with 10% chance

Build time (1000 routes): 0.011926 seconds
Match time (first route): 0.000072 seconds(URI: /rlpkswupqzo/g)
Match time (random route): 0.000015 seconds(URI: /muq/vs)
Match time (last route): 0.000013 seconds(URI: /fneek/aedpctey/v/aaxzpf)
Match time (unknown route): 0.000014 seconds
Total time: 0.011953 seconds
Memory usage: 1814 KB
Peak memory usage: 2048 KB

 */
