<?php
/*
 * This inhere/sroute routes cache file. is auto generate by inhere\sroute\ORouter.
 * @date 2017-07-16 16:36:10
 */
return [
    'staticRoutes' => array (
  '/' => 
  array (
    'GET' => 
    array (
      'method' => 'GET',
      'handler' => 
      Closure::__set_state(array(
      )),
      'option' => 
      array (
        'tokens' => NULL,
        'hosts' => NULL,
        'schema' => NULL,
        'enter' => NULL,
        'leave' => NULL,
      ),
    ),
  ),
  '/user/signUp' => 
  array (
    'POST' => 
    array (
      'method' => 'POST',
      'handler' => 
      Closure::__set_state(array(
      )),
      'option' => 
      array (
        'tokens' => NULL,
        'hosts' => NULL,
        'schema' => NULL,
        'enter' => NULL,
        'leave' => NULL,
      ),
    ),
  ),
  '/user/' => 
  array (
    'GET' => 
    array (
      'method' => 'GET',
      'handler' => 
      Closure::__set_state(array(
      )),
      'option' => 
      array (
        'tokens' => NULL,
        'hosts' => NULL,
        'schema' => NULL,
        'enter' => NULL,
        'leave' => NULL,
      ),
    ),
  ),
  '/user/index' => 
  array (
    'GET' => 
    array (
      'method' => 'GET',
      'handler' => 
      Closure::__set_state(array(
      )),
      'option' => 
      array (
        'tokens' => NULL,
        'hosts' => NULL,
        'schema' => NULL,
        'enter' => NULL,
        'leave' => NULL,
      ),
    ),
  ),
  '/user/login' => 
  array (
    'GET' => 
    array (
      'method' => 'GET',
      'handler' => 
      Closure::__set_state(array(
      )),
      'option' => 
      array (
        'tokens' => NULL,
        'hosts' => NULL,
        'schema' => NULL,
        'enter' => NULL,
        'leave' => NULL,
      ),
    ),
    'POST' => 
    array (
      'method' => 'POST',
      'handler' => 
      Closure::__set_state(array(
      )),
      'option' => 
      array (
        'tokens' => NULL,
        'hosts' => NULL,
        'schema' => NULL,
        'enter' => NULL,
        'leave' => NULL,
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
        'tokens' => NULL,
        'hosts' => NULL,
        'schema' => NULL,
        'enter' => NULL,
        'leave' => NULL,
      ),
    ),
  ),
  '/home/:act' => 
  array (
    'ANY' => 
    array (
      'method' => 'ANY',
      'handler' => 'inhere\\sroute\\examples\\controllers\\HomeController',
      'option' => 
      array (
        'tokens' => NULL,
        'hosts' => NULL,
        'schema' => NULL,
        'enter' => NULL,
        'leave' => NULL,
      ),
    ),
  ),
),
    'regularRoutes' => array (
  'h' => 
  array (
    'e' => 
    array (
      0 => 
      array (
        'first' => '/hello',
        'regex' => '#^/hello/\\w+$#',
        'method' => 'GET',
        'handler' => 
        Closure::__set_state(array(
        )),
        'option' => 
        array (
          'tokens' => 
          array (
            'name' => '\\w+',
          ),
          'hosts' => NULL,
          'schema' => NULL,
          'enter' => NULL,
          'leave' => NULL,
        ),
      ),
    ),
  ),
),
    'vagueRoutes' => array (
),
];