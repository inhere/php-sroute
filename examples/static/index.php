<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/28
 * Time: 上午12:00
 *
 * you can test use:
 *  php -S 127.0.0.1:5670 -t examples/static
 *
 * then you can access url: http://127.0.0.1:5670
 */

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Asia/Shanghai');

use inhere\sroute\SRoute;

require dirname(__DIR__) . '/simple-loader.php';
require __DIR__ . '/routes.php';

// set config
SRoute::config([
    'stopOnMatch' => true,
    'ignoreLastSep' => true,
    'dynamicAction' => true,

//    'matchAll' => '/', // a route path
//    'matchAll' => function () {
//        echo 'System Maintaining ... ...';
//    },

    // enable autoRoute
    // you can access '/demo' '/admin/user/info', Don't need to configure any route
    'autoRoute' => [
        'enable' => 1,
        'controllerNamespace' => 'inhere\sroute\examples\controllers',
        'controllerSuffix' => 'Controller',
    ],
]);

// dispatch
SRoute::dispatch();
