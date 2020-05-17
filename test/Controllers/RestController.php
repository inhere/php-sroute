<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-04
 * Time: 14:58
 */

namespace Inhere\RouteTest\Controllers;

/**
 * Class RestController
 * @package Inhere\RouteTest\Controllers
 */
class RestController
{
    public function indexAction(): void
    {
        echo __METHOD__ . PHP_EOL;
    }

    public function viewAction(): void
    {
        echo __METHOD__ . PHP_EOL;
    }

    public function createAction(): void
    {
        echo __METHOD__ . PHP_EOL;
    }

    public function updateAction(): void
    {
        echo __METHOD__ . PHP_EOL;
    }

    public function patchAction(): void
    {
        echo __METHOD__ . PHP_EOL;
    }

    public function deleteAction(): void
    {
        echo __METHOD__ . PHP_EOL;
    }
}
