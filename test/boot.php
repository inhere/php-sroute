<?php declare(strict_types=1);
/**
 * run test:
 *  phpunit
 * OR
 *  phpunit --bootstrap test/boot.php test
 *  phpunit --colors --coverage-html ./coverage/
 * // output coverage without xdebug
 *  phpdbg -dauto_globals_jit=Off -qrr /usr/local/bin/phpunit --coverage-text
 * test filter:
 *  phpunit --filter '\\RouterTest::testAdd' --debug
 */

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Asia/Shanghai');

$libDir = dirname(__DIR__);

// has autoloader
if (file_exists($libDir . '/vendor/autoload.php')) {
    require $libDir . '/vendor/autoload.php';
} else {
    require $libDir . '/src/Helper/functions.php';

    $npMap  = [
        'Inhere\RouteTest\\' => $libDir . '/test/',
        'Inhere\Route\\'     => $libDir . '/src/',
    ];

    spl_autoload_register(function ($class) use ($npMap) {
        foreach ($npMap as $np => $dir) {
            $file = $dir . str_replace('\\', '/', substr($class, strlen($np))) . '.php';

            if (file_exists($file)) {
                include $file;
            }
        }
    });
}

// generates a random request url
function random_request_url($chance = 5)
{
    $characters       = 'abcdefghijklmnopqrstuvwxyz';
    $charactersLength = strlen($characters);
    $randomString     = '/';
    $prefixes         = ['api', 'v2', 'user', 'goods'];
    $rand             = random_int(5, 20);

    $hasPrefix = false;
    if (in_array($rand, [6, 12, 15, 18], true)) {
        $hasPrefix    = true;
        $randomString .= $prefixes[array_rand($prefixes, 1)] . '/';
    }

    // create random path of 5-20 characters
    for ($i = 0; $i < $rand; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];

        if (!$hasPrefix && random_int(1, 8) === 1) {
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
        'yellow'  => '1;33',
        'magenta' => '1;35',
        'white'   => '1;37',
        'black'   => '0;30',
        'red'     => '0;31',
        'green'   => '0;32',
        'brown'   => '0;33',
        'blue'    => '0;34',
        'cyan'    => '0;36',

        'light_red'   => '1;31',
        'light_blue'  => '1;34',
        'light_gray'  => '37',
        'light_green' => '1;32',
        'light_cyan'  => '1;36',
    ];

    if (isset($styles[$style]) && false === strpos(PHP_OS, 'WIN')) {
        return sprintf("\033[%sm%s\033[0m" . ($nl ? PHP_EOL : ''), $styles[$style], $msg);
    }

    return $msg . ($nl ? PHP_EOL : '');
}

function pretty_match_result($ret)
{
    $str = json_encode($ret, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    return str_replace('\\', '', $str);
}
