<?php


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

function pretty_echo($msg, $style = 'green', $nl = false)
{
    static $styles = [
        'yellow' => '1;33',
        'magenta' => '1;35',
        'white' => '1;37',
        'black' => '0;30',
        'red' => '0;31',
        'green' => '0;32',
        'brown' => '0;33',
        'blue' => '0;34',
        'cyan' => '0;36',

        'light_red' => '1;31',
        'light_blue' => '1;34',
        'light_gray' => '37',
        'light_green' => '1;32',
        'light_cyan' => '1;36',
    ];

    if (false === strpos(PHP_OS, 'WIN') && isset($styles[$style])) {
        return sprintf("\033[%sm%s\033[0m" . ($nl ? PHP_EOL : ''), $styles[$style], $msg);
    }

    return $msg . ($nl ? PHP_EOL : '');
}

function pretty_match_result($ret)
{
    $str = json_encode($ret, JSON_PRETTY_PRINT);

    return str_replace('\\', '', $str);
}