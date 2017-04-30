<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/28
 * Time: 上午12:00
 *
 * you can test use:
 *  php -S 127.0.0.1:5670 -t examples
 * Or use:
 * ./php_server
 *
 * then you can access url: http://127.0.0.1:5670
 */

require dirname(__DIR__) . '/SRoute.php';

require __DIR__ . '/controllers/HomeController.php';
require __DIR__ . '/controllers/DemoController.php';
require __DIR__ . '/controllers/admin/UserController.php';

require __DIR__ . '/routes.php';
