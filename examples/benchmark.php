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
$n = isset( $argv[1] ) ? (int)$argv[1] : 1000;

echo "Will generate $n routes.\n";

// generates a random request url
function random_request_url() {
    $characters = 'abcdefghijklmnopqrstuvwxyz';
    $charactersLength = strlen($characters);
    $randomString = '/';
    $rand = random_int(5, 20);

    // create random path of 5-20 characters
    for ($i = 0; $i < $rand; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];

        if( random_int(1, 10) === 1 ) {
            $randomString .= '/';
        }
    }

    // add dynamic route with 10% chance
    if ( random_int(1, 10) === 1 ) {
        $randomString = rtrim( $randomString, '/' ) . '/[:part]';
    }

    return $randomString;
}

// generate a random request method
function random_request_method() {
    static $methods = array( 'GET', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' );
    $random_key = array_rand( $methods );
    return $methods[ $random_key ];
}

// prepare benchmark data
$requests = array();
for($i=0; $i<$n; $i++) {
    $requests[] = array(
        'method' => random_request_method(),
        'url' => random_request_url(),
    );
}

$router = new \Inhere\Route\ORouter();

// map requests
$start = microtime(true);
foreach($requests as $r) {
    $router->map($r['method'], $r['url'], function(){});
}
$end = microtime(true);
$map_time = $end - $start;
echo 'Map time (add routes): ' . number_format($map_time, 6). ' seconds' . PHP_EOL;


// pick random route to match
$r = $requests[array_rand($requests)];

// match random known route
$start = microtime(true);
$router->match($r['url'], $r['method']);
$end = microtime(true);
$match_time_known_route = $end - $start;
echo 'Match time (known route): ' . number_format($match_time_known_route, 6). ' seconds' . PHP_EOL;

// match unexisting route
$start = microtime(true);
$router->match('/55-foo-bar', 'GET');
$end = microtime(true);
$match_time_unknown_route = $end - $start;
echo 'Match time (unknown route): ' . number_format($match_time_unknown_route, 6). ' seconds' . PHP_EOL;

// print totals
echo 'Total time: ' . number_format($map_time + $match_time_known_route + $match_time_unknown_route, 6). ' seconds' . PHP_EOL;
echo 'Memory usage: ' . round( memory_get_usage() / 1024 ) . 'KB' . PHP_EOL;
echo 'Peak memory usage: ' . round( memory_get_peak_usage( true ) / 1024 ) . 'KB' . PHP_EOL;



