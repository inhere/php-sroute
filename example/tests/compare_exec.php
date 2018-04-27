<?php

$times = isset($argv[1]) ? (int)$argv[1] : 1000;

$str = '/50be3774f6/{arg1}/arg2/arg3/{int}/arg5/arg6/{arg7}/arg8/arg9[/850726135a]';

$sample1 = function () {
    $path = '/50be3774f6/{arg1}/{arg2}/{arg3}/{arg4}/{arg5}/{arg6}/{arg7}/{arg8}/{arg9}/850726135a';

    preg_match_all('#\{([a-zA-Z_][\w-]*)\}#', $path, $m);

    return $m;
};

$sample2 = function () {
    $path = '/50be3774f6/{arg1}/{arg2}/{arg3}/{arg4}/{arg5}/{arg6}/{arg7}/{arg8}/{arg9}/850726135a';

    preg_match_all('#\{([a-zA-Z_][\w-]*)\}#', $path, $m, PREG_SET_ORDER);

    return $m;
};

compare_speed($sample1, $sample2, $times);

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
