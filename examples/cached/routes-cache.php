<?php
/*
 * This inhere/sroute routes cache file. is auto generate by Inhere\Route\ORouter.
 * @date 2017-08-08 15:07:34
 */
return [
    'staticRoutes' => array (
  '/' =>
  array (
    'GET' =>
    array (
      'method' => 'GET',
      'handler' => 'home_handler',
      'option' =>
      array (
        'params' => NULL,
        'domains' => NULL,
        'schema' => NULL,
      ),
    ),
  ),
  '/user/signUp' =>
  array (
    'POST' =>
    array (
      'method' => 'POST',
      'handler' => 'handler2',
      'option' =>
      array (
        'params' => NULL,
        'domains' => NULL,
        'schema' => NULL,
      ),
    ),
  ),
  '/user' =>
  array (
    'GET' =>
    array (
      'method' => 'GET',
      'handler' => 'handler3',
      'option' =>
      array (
        'params' => NULL,
        'domains' => NULL,
        'schema' => NULL,
      ),
    ),
  ),
  '/user/index' =>
  array (
    'GET' =>
    array (
      'method' => 'GET',
      'handler' => 'handler4',
      'option' =>
      array (
        'params' => NULL,
        'domains' => NULL,
        'schema' => NULL,
      ),
    ),
  ),
  '/user/login' =>
  array (
    'GET' =>
    array (
      'method' => 'GET',
      'handler' => 'handler5',
      'option' =>
      array (
        'params' => NULL,
        'domains' => NULL,
        'schema' => NULL,
      ),
    ),
    'POST' =>
    array (
      'method' => 'POST',
      'handler' => 'handler5',
      'option' =>
      array (
        'params' => NULL,
        'domains' => NULL,
        'schema' => NULL,
      ),
    ),
  ),
  '/home' =>
  array (
    'GET' =>
    array (
      'method' => 'GET',
      'handler' => 'inhere\\sroute\\examples\\controllers\\HomeController@index',
      'option' =>
      array (
        'params' => NULL,
        'domains' => NULL,
        'schema' => NULL,
      ),
    ),
  ),
),
    'regularRoutes' => array (
  'm' =>
  array (
    'y' =>
    array (
      0 =>
      array (
        'first' => '/my',
        'regex' => '#^/my(?:/([^/]+)(?:/(\\d+))?)?$#',
        'method' => 'GET',
        'handler' => 'my_handler',
        'option' =>
        array (
          'params' =>
          array (
            'age' => '\\d+',
          ),
          'domains' => NULL,
          'schema' => NULL,
        ),
      ),
    ),
  ),
  'h' =>
  array (
    'e' =>
    array (
      0 =>
      array (
        'first' => '/hello',
        'regex' => '#^/hello(?:/(\\w+))?$#',
        'method' => 'GET',
        'handler' => 'handler1',
        'option' =>
        array (
          'params' =>
          array (
            'name' => '\\w+',
          ),
          'domains' => NULL,
          'schema' => NULL,
        ),
      ),
    ),
    'o' =>
    array (
      0 =>
      array (
        'first' => '/home',
        'regex' => '#^/home/([a-zA-Z][\\w-]+)$#',
        'method' => 'ANY',
        'handler' => 'inhere\\sroute\\examples\\controllers\\HomeController',
        'option' =>
        array (
          'params' => NULL,
          'domains' => NULL,
          'schema' => NULL,
        ),
      ),
    ),
  ),
),
    'vagueRoutes' => array (
  0 =>
  array (
    'method' => 'GET',
    'handler' => 'default_handler',
    'option' =>
    array (
      'params' =>
      array (
        'name' => 'blog|saying',
      ),
      'domains' => NULL,
      'schema' => NULL,
    ),
    'regex' => '#^/(blog|saying)$#',
  ),
),
];
