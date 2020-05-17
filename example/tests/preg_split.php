<?php declare(strict_types=1);
$variable_regex = <<<'REGEX'
\{
    \s* ([a-zA-Z_][a-zA-Z0-9_-]*) \s*
    (?:
        : \s* ([^{}]*(?:\{(?-1)\}[^{}]*)*)
    )?
\}
REGEX;

// $route = '/hello[/{name}]/{foo}[/{bar}]';
$route = '/hello/{name}[/{foo}[/{bar}]]';
$withoutClosingOptionals = rtrim($route, ']');
$numOptionals = strlen($route) - strlen($withoutClosingOptionals);

$segments = preg_split('~' . $variable_regex . '(*SKIP)(*F) | \[~x', $withoutClosingOptionals);

// if ($numOptionals !== 1) {
//     throw new \LogicException("Optional segments can only occur at the end of a route");
// }
if ($numOptionals !== count($segments) - 1) {
    // If there are any ] in the middle of the route, throw a more specific error message
    if (preg_match('~' . $variable_regex . '(*SKIP)(*F) | \]~x', $routeWithoutClosingOptionals)) {
        throw new \LogicException('Optional segments can only occur at the end of a route');
    }

    throw new \LogicException("Number of opening '[' and closing ']' does not match");
}

// preg_match_all('#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#', $route, $m);
// $m1 = preg_split('#\[#', $route, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

preg_match_all(
    '~' . $variable_regex . '~x',
    $route,
    $matches,
    PREG_OFFSET_CAPTURE | PREG_SET_ORDER
);

$e1 = '/user/{name}[/{id:[0-9]+}]';
// $e2 = '/hello/{name}[/{foo}[/{bar}]]';
$e2 = '/hello/{name}[/{foo}/{bar}]';

/*
/hello/name/foo/bar
/hello/name/foo

 */
preg_match('#/hello/(\w+)/(?:(\w+)(?:/(\w+))?)?#', '/hello/name/foo', $md);
var_dump($segments, $matches, $md);
