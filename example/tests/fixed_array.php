<?php declare(strict_types=1);

for ($size = 1000; $size < 4000000; $size *= 2) {
    echo PHP_EOL . "Testing size: $size" . PHP_EOL;

    for ($s = microtime(true), $container = [], $i = 0; $i < $size; $i++) {
        $container[$i] = 1;
    }
    $container = []; // release data

    echo 'Array(): ' . (microtime(true) - $s) * 1000 . "ms \n";

    for ($s = microtime(true), $container = new SplFixedArray($size), $i = 0; $i < $size; $i++) {
        $container[$i] = 1;
    }
    $container = null; // release data

    echo 'SplArray(): ' . (microtime(true) - $s) * 1000 . "ms \n";
}
