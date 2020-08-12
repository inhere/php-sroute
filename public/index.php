<?php


use Inhere\Route\Router;

// 使用composer, 需要先加载 autoload 文件
// require dirname(__DIR__) . '/vendor/autoload.php';
// 不使用composer，可以加载 test/boot.php
require dirname(__DIR__) . '/test/boot.php';

$router = new Router();

$router->get('/', function() {
    echo 'hello';
});

// 开始调度运行
$router->dispatch();
