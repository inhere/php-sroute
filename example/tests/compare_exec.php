<?php

const METHODS_STRING = 'ANY,GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD';
const METHODS_ARRAY = [
    'ANY',
    'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD',
    // 'COPY', 'PURGE', 'LINK', 'UNLINK', 'LOCK', 'UNLOCK', 'VIEW', 'SEARCH', 'CONNECT', 'TRACE',
];

$times = isset($argv[1]) ? (int)$argv[1] : 1000;

// $str = 'get';
$str = ['get', 'post'];

$sample1 = function ($methods) {
    $hasAny = false;
    $methods = \array_map(function ($m) use (&$hasAny) {
        $m = \strtoupper(\trim($m));

        if (!$m || false === \strpos(METHODS_STRING . ',', $m . ',')) {
            throw new \InvalidArgumentException(
                "The method [$m] is not supported, Allow: " . METHODS_STRING
            );
        }

        if (!$hasAny && $m === 'ANY') {
            $hasAny = true;
        }

        return $m;
    }, (array)$methods);

    return $hasAny ? METHODS_ARRAY : $methods;
};

$sample2 = function ($methods) {
    if (is_string($methods)) {
        $method = strtoupper($methods);

        if ($method === 'ANY') {
            return METHODS_ARRAY;
        }

        if (false === \strpos(METHODS_STRING . ',', $method . ',')) {
            throw new \InvalidArgumentException(
                "The method [$method] is not supported, Allow: " . METHODS_STRING
            );
        }

        return [$method];
    }

    $upperMethods = [];

    foreach ((array)$methods as $method) {
        $method = strtoupper($method);

        if ($method === 'ANY') {
            return METHODS_ARRAY;
        }

        if (false === \strpos(METHODS_STRING . ',', $method . ',')) {
            throw new \InvalidArgumentException(
                "The method [$method] is not supported, Allow: " . METHODS_STRING
            );
        }

        $upperMethods[] = $method;
    }

    return $upperMethods;
};

// $sample2 = function ($route, $params) {
//     $route = (string)preg_replace_callback('#\{[a-zA-Z_][\w-]*\}#', function ($m) use ($params) {
//         //var_dump($m, $params);die;
//         $name = substr($m[0], 1, -1);
//         return '(' . ($params[$name] ?? '[^/]+') . ')';
//     }, $route);
//
//     return $route;
// };

compare_speed($sample1, $sample2, $times, [
    $str,
    [
        'all' => '.*',
        'any' => '[^/]+',        // match any except '/'
        'num' => '[1-9][0-9]*',  // match a number and gt 0
        'int' => '\d+',          // match a number
        'act' => '[a-zA-Z][\w-]+', // match a action name
    ]
]);

function compare_speed(callable $sample1, callable $sample2, int $times = 1000, array $args = [])
{
    if ($times < 1000) {
        $times = 1000;
    }

    $start1 = microtime(1);

    // test 0
    for ($i = 0; $i < $times; $i++) {
        $sample1(...$args);
    }

    $end1 = microtime(1);

    // test 1
    $start2 = microtime(1);

    for ($i = 0; $i < $times; $i++) {
        $sample2(...$args);
    }

    $end2 = microtime(1);

    // calc total
    $total1 = round($end1 - $start1, 3);
    $total2 = round($end2 - $start2, 3);

    // average
    $decimal = 3;
    $average1 = round($total1/$times, $decimal);
    $average2 = round($total2/$times, $decimal);

    $result1 = $sample1(...$args);
    $result2 = $sample2(...$args);

    printf("Sample 1 exec results: %s\n", var_export($result1, true));
    printf("Sample 2 exec results: %s\n", var_export($result2, true));

    $faster = $total1 - $total2 > 0 ? 'Sample 2' : 'Sample 1';

    printf(
        "\n\t              Speed Test Results(Faster is: %s)\n%s\n",
        $faster, str_repeat('---', 29)

    );

    $template = "%-12s %-22s %-25s %-20s\n";
    $results = [
        ['Test Name', 'Number of executions', 'Total time-consuming(us)', 'Average time-consuming(us)'],
        ['Sample 1', $times, $total1, $average1],
        ['Sample 2', $times, $total2, $average2],
    ];

    foreach ($results as $items) {
        printf($template, ...$items);
    }

    echo "\n";
}
