<?php
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
        throw new \LogicException("Optional segments can only occur at the end of a route");
    }

    throw new \LogicException("Number of opening '[' and closing ']' does not match");
}

// preg_match_all('#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#', $route, $m);
// $m1 = preg_split('#\[#', $route, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

preg_match_all(
    '~' . $variable_regex . '~x', $route, $matches,
    PREG_OFFSET_CAPTURE | PREG_SET_ORDER
);

$p = new Std;
$e1 = "/user/{name}[/{id:[0-9]+}]";
// $e2 = '/hello/{name}[/{foo}[/{bar}]]';
$e2 = '/hello/{name}[/{foo}/{bar}]';

var_dump($segments, $matches, $p->parse($e2));

/**
 * Parses route strings of the following form:
 *
 * "/user/{name}[/{id:[0-9]+}]"
 */
class Std {
    const VARIABLE_REGEX = <<<'REGEX'
\{
    \s* ([a-zA-Z_][a-zA-Z0-9_-]*) \s*
    (?:
        : \s* ([^{}]*(?:\{(?-1)\}[^{}]*)*)
    )?
\}
REGEX;
    const DEFAULT_DISPATCH_REGEX = '[^/]+';

    public function parse($route) {
        $routeWithoutClosingOptionals = rtrim($route, ']');
        $numOptionals = strlen($route) - strlen($routeWithoutClosingOptionals);

        // Split on [ while skipping placeholders
        $segments = preg_split('~' . self::VARIABLE_REGEX . '(*SKIP)(*F) | \[~x', $routeWithoutClosingOptionals);
        if ($numOptionals !== count($segments) - 1) {
            // If there are any ] in the middle of the route, throw a more specific error message
            if (preg_match('~' . self::VARIABLE_REGEX . '(*SKIP)(*F) | \]~x', $routeWithoutClosingOptionals)) {
                throw new BadRouteException("Optional segments can only occur at the end of a route");
            }
            throw new BadRouteException("Number of opening '[' and closing ']' does not match");
        }

        $currentRoute = '';
        $routeDatas = [];
        foreach ($segments as $n => $segment) {
            if ($segment === '' && $n !== 0) {
                throw new BadRouteException("Empty optional part");
            }

            $currentRoute .= $segment;
            $routeDatas[] = $this->parsePlaceholders($currentRoute);
        }
        return $routeDatas;
    }

    /**
     * Parses a route string that does not contain optional segments.
     *
     * @param string
     * @return mixed[]
     */
    private function parsePlaceholders($route) {
        if (!preg_match_all(
            '~' . self::VARIABLE_REGEX . '~x', $route, $matches,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        )) {
            return [$route];
        }

        $offset = 0;
        $routeData = [];
        foreach ($matches as $set) {
            if ($set[0][1] > $offset) {
                $routeData[] = substr($route, $offset, $set[0][1] - $offset);
            }
            $routeData[] = [
                $set[1][0],
                isset($set[2]) ? trim($set[2][0]) : self::DEFAULT_DISPATCH_REGEX
            ];
            $offset = $set[0][1] + strlen($set[0][0]);
        }

        if ($offset != strlen($route)) {
            $routeData[] = substr($route, $offset);
        }

        return $routeData;
    }
}
