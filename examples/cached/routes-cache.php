<?php
/*
 * This inhere/sroute routes cache file. 
 * It is auto generate by Inhere\Route\CachedRouter.
 * @date 2017-10-18 10:47:53
 * @notice Please don't change it.
 */
return [
    'staticRoutes' => array (
  '/routes' => 
  array (
    0 => 
    array (
      'methods' => 'GET,',
      'handler' => 'dump_routes',
      'option' => 
      array (
        'params' => NULL,
        'domains' => NULL,
      ),
    ),
  ),
  '/' => 
  array (
    0 => 
    array (
      'methods' => 'GET,',
      'handler' => 'handler0',
      'option' => 
      array (
        'params' => NULL,
        'domains' => NULL,
      ),
    ),
  ),
  '/home' => 
  array (
    0 => 
    array (
      'methods' => 'GET,',
      'handler' => 'Inhere\\Route\\Examples\\Controllers\\HomeController@index',
      'option' => 
      array (
        'params' => NULL,
        'domains' => NULL,
      ),
    ),
  ),
  '/post' => 
  array (
    0 => 
    array (
      'methods' => 'POST,',
      'handler' => 'post_handler',
      'option' => 
      array (
        'params' => NULL,
        'domains' => NULL,
      ),
    ),
  ),
  '/pd' => 
  array (
    0 => 
    array (
      'methods' => 'POST,DELETE,',
      'handler' => 'multi_method_handler',
      'option' => 
      array (
        'params' => NULL,
        'domains' => NULL,
      ),
    ),
  ),
  '/user/login' => 
  array (
    0 => 
    array (
      'methods' => 'GET,POST,',
      'handler' => 'default_handler',
      'option' => 
      array (
        'params' => NULL,
        'domains' => NULL,
      ),
    ),
  ),
),
    'regularRoutes' => array (
  '50be3774f6' => 
  array (
    0 => 
    array (
      'regex' => '#^/50be3774f6/(?P<arg1>[^/]+)/(?P<arg2>[^/]+)/(?P<arg3>[^/]+)/(?P<arg4>[^/]+)/(?P<arg5>[^/]+)/(?P<arg6>[^/]+)/(?P<arg7>[^/]+)/(?P<arg8>[^/]+)/(?P<arg9>[^/]+)/850726135a$#',
      'start' => '/50be3774f6/',
      'methods' => 'GET,',
      'handler' => 'handler0',
      'option' => 
      array (
        'params' => NULL,
        'domains' => NULL,
      ),
    ),
  ),
  'about' => 
  array (
    0 => 
    array (
      'regex' => '#^/about(?:\\.html)?$#',
      'start' => '/about',
      'methods' => 'GET,',
      'handler' => 'Inhere\\Route\\Examples\\Controllers\\HomeController@about',
      'option' => 
      array (
        'params' => NULL,
        'domains' => NULL,
      ),
    ),
  ),
  'test' => 
  array (
    0 => 
    array (
      'regex' => '#^/test(?:/optional)?$#',
      'start' => '/test',
      'methods' => 'GET,',
      'handler' => 'default_handler',
      'option' => 
      array (
        'params' => NULL,
        'domains' => NULL,
      ),
    ),
  ),
  'my' => 
  array (
    0 => 
    array (
      'regex' => '#^/my(?:/(?P<name>[^/]+)(?:/(?P<age>\\d+))?)?$#',
      'start' => '/my',
      'methods' => 'GET,',
      'handler' => 'my_handler',
      'option' => 
      array (
        'params' => 
        array (
          'age' => '\\d+',
        ),
        'domains' => NULL,
        'defaults' => 
        array (
          'name' => 'God',
          'age' => 25,
        ),
      ),
    ),
  ),
  'home' => 
  array (
    0 => 
    array (
      'regex' => '#^/home/(?P<act>[a-zA-Z][\\w-]+)$#',
      'start' => '/home/',
      'methods' => 'ANY,GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD,SEARCH,CONNECT,TRACE,',
      'handler' => 'Inhere\\Route\\Examples\\Controllers\\HomeController',
      'option' => 
      array (
        'params' => NULL,
        'domains' => NULL,
      ),
    ),
  ),
),
    'vagueRoutes' => array (
  0 => 
  array (
    'regex' => '#^/(?P<name>blog|saying)$#',
    'include' => NULL,
    'methods' => 'GET,',
    'handler' => 'default_handler',
    'option' => 
    array (
      'params' => 
      array (
        'name' => 'blog|saying',
      ),
      'domains' => NULL,
    ),
  ),
),
];