<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/16
 * Time: 下午10:29
 */

//$path = '/hello/ad{name:\w+}/{any}';
//$path = '/hello/{name}/{foo:\w+}';
$path = '/hello/{name}/{foo:\w+}/{bar:[a-z]+}';

preg_match_all('/(?:
    # parse variable param with separator
    #.           # separator
    \{
     ([\w\d_]+)  # variable
     (?::(.*))?  # optional params
    \}
)/x', $path, $matches1);
var_dump($matches1);

$variable_regex = <<<REGEX
\{
    \s* ([a-zA-Z_][a-zA-Z0-9_-]*) \s*
    (?:
        : \s* ([^{}]*(?:\{(?-1)\}[^{}]*)*)
    )?
\}
REGEX;

preg_match_all(
    '~' . $variable_regex . '~x',
    $path,
    $matches,
    PREG_OFFSET_CAPTURE | PREG_SET_ORDER
);

var_dump($matches);

// from fastRoute
function replaceTokenToPattern($path, $variable_regex)
{
    if (!preg_match_all(
        '#' . $variable_regex . '#x',
        $path,
        $matches,
        PREG_OFFSET_CAPTURE | PREG_SET_ORDER
    )
    ) {
        return [$path];
    }

    $offset = 0;
    $routeData = [];
    /** @var array $matches */
    foreach ($matches as $set) {
        if ($set[0][1] > $offset) {
            $routeData[] = substr($path, $offset, $set[0][1] - $offset);
        }
        $routeData[] = [
            $set[1][0],
            isset($set[2]) ? trim($set[2][0]) : self::DEFAULT_DISPATCH_REGEX
        ];
        $offset = $set[0][1] + strlen($set[0][0]);
    }

    if ($offset !== strlen($path)) {
        $routeData[] = substr($path, $offset);
    }

    // var_dump($routeData);

    return $routeData;
}
