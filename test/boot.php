<?php
/**
 * phpunit
 * OR
 * phpunit6.phar --bootstrap test/boot.php test
 * phpunit6.phar --colors --coverage-html ./coverage/
 */

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Asia/Shanghai');

spl_autoload_register(function($class)
{
    $file = null;

    if (0 === strpos($class,'Inhere\Route\Example\\')) {
        $path = str_replace('\\', '/', substr($class, strlen('Inhere\Route\Example\\')));
        $file = __DIR__ . "/{$path}.php";
    } elseif(0 === strpos($class,'Inhere\Route\Test\\')) {
        $path = str_replace('\\', '/', substr($class, strlen('Inhere\Route\Test\\')));
        $file = dirname(__DIR__) . "/test/{$path}.php";
    } elseif (0 === strpos($class,'Inhere\Route\\')) {
        $path = str_replace('\\', '/', substr($class, strlen('Inhere\Route\\')));
        $file = dirname(__DIR__) . "/src/{$path}.php";
    }

    if ($file && is_file($file)) {
        include $file;
    }
});

// generates a random request url
function random_request_url($chance = 5)
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
    if ($v <= $chance) {
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

    if (isset($styles[$style]) && false === strpos(PHP_OS, 'WIN')) {
        return sprintf("\033[%sm%s\033[0m" . ($nl ? PHP_EOL : ''), $styles[$style], $msg);
    }

    return $msg . ($nl ? PHP_EOL : '');
}

function pretty_match_result($ret)
{
    $str = json_encode($ret, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);

    return str_replace('\\', '', $str);
}
