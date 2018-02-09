<?php

$ary = array (
    array(
        'first' => '/hallo',
        'regex' => '#^/hello(?:/(\\w+))?$#',
        'method' => 'GET',
        'handler' => 'handler1',
        'option' => array(
            'params' => array(
                'name' => '\\w+',
            ),
            'domains' => null,
            'schema' => null,
        ),
    ),
    array(
        'first' => '/hallo',
        'regex' => '#^/hello(?:/(\\w+))?$#',
        'method' => 'GET',
        'handler' => 'handler1',
        'option' => array(
            'params' => array(
                'name' => '\\w+',
            ),
            'domains' => null,
            'schema' => null,
        ),
    ),
    array(
        'first' => '/hallo',
        'regex' => '#^/hello(?:/(\\w+))?$#',
        'method' => 'GET',
        'handler' => 'handler1',
        'option' => array(
            'params' => array(
                'name' => '\\w+',
            ),
            'domains' => null,
            'schema' => null,
        ),
    ),
    array(
        'first' => '/hallo',
        'regex' => '#^/hello(?:/(\\w+))?$#',
        'method' => 'GET',
        'handler' => 'handler1',
        'option' => array(
            'params' => array(
                'name' => '\\w+',
            ),
            'domains' => null,
            'schema' => null,
        ),
    ),
    array(
        'first' => '/hallo',
        'regex' => '#^/hello(?:/(\\w+))?$#',
        'method' => 'GET',
        'handler' => 'handler1',
        'option' => array(
            'params' => array(
                'name' => '\\w+',
            ),
            'domains' => null,
            'schema' => null,
        ),
    ),
    array(
        'first' => '/hallo',
        'regex' => '#^/hello(?:/(\\w+))?$#',
        'method' => 'GET',
        'handler' => 'handler1',
        'option' => array(
            'params' => array(
                'name' => '\\w+',
            ),
            'domains' => null,
            'schema' => null,
        ),
    ),
    array(
        'first' => '/hallo',
        'regex' => '#^/hello(?:/(\\w+))?$#',
        'method' => 'GET',
        'handler' => 'handler1',
        'option' => array(
            'params' => array(
                'name' => '\\w+',
            ),
            'domains' => null,
            'schema' => null,
        ),
    ),
    array(
        'first' => '/hallo',
        'regex' => '#^/hello(?:/(\\w+))?$#',
        'method' => 'GET',
        'handler' => 'handler1',
        'option' => array(
            'params' => array(
                'name' => '\\w+',
            ),
            'domains' => null,
            'schema' => null,
        ),
    ),
    array(
        'first' => '/hallo',
        'regex' => '#^/hello(?:/(\\w+))?$#',
        'method' => 'GET',
        'handler' => 'handler1',
        'option' => array(
            'params' => array(
                'name' => '\\w+',
            ),
            'domains' => null,
            'schema' => null,
        ),
    ),
);

foreach (gen($ary, 'hallo') as $key => $value) {
    # code...
}

function gen($ary, $path)
{
    if (($num = count($ary)) <= 30) {
        return find($ary);
    }

    array_chunk($ary, ceil($num/2));
    yield find($path);
    yield find($path);
}

function find($arr, $path)
{
    foreach ((array)$arr as $conf) {
        if (0 === strpos($path, $conf['first']) && preg_match($conf['regex'], $path, $matches)) {
            // method not allowed
            if ($method !== $conf['method'] && self::ANY_METHOD !== $conf['method']) {
                return false;
            }

            $conf['matches'] = $matches;

            // cache latest $number routes.
            if ($number > 0) {
                if (count($this->routeCaches) === $number) {
                    array_shift($this->routeCaches);
                }

                $this->routeCaches[$path][$conf['method']] = $conf;
            }

            return [$path, $conf];
        }
    }

    return false;
}
