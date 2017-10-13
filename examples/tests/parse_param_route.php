<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/13
 * Time: 下午11:53
 */

$r = '/hello[/{name}]';
$r1 = '/user/{id}/profile';
$r2 = '/user-{id}/profile';
$r3 = '/user/id-{id}/profile';
$r4 = '/{name}/profile';

$p = '#^/([\w-]+)/?[\w-]*#';
$p1 = '#/([\w-]+)/?[\w-]*#';

preg_match($p, $r, $m);
preg_match($p, $r1, $m1);
preg_match($p, $r2, $m2);
preg_match($p, $r3, $m3);

preg_match($p1, $r4, $m4);

var_dump($m, $m1, $m2, $m3, $m4);
