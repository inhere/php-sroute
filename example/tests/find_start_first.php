<?php declare(strict_types=1);

// $path = '/hello[/{name}]';
// $path = '/user/{id}/profile';
// $path = '/user-{id}/profile';
$path = '/user/id-{id}/profile';
// $path = '/api/{about}';
// $path = '/api[/{about}]';

preg_match('#^/([\w-]+)/(?:[\w-\/]*)#', $path, $m1);
$first = 1 === preg_match('#^/([\w-]+)/#', $path, $m) ? $m[1] : '';
var_dump($first, $m1);
