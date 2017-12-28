<?php
/*
 * This is routes cache file of the package `inhere/sroute`.
 * It is auto generate by Inhere\Route\CachedRouter.
 * @date 2017-12-28 11:01:33
 * @count 42
 * @notice Please don't edit it.
 */

$count = 42;

// static routes
$staticRoutes = array (
  '/routes' => array (
    'GET' => 0,
  ),
  '/rest' => array (
    'GET' => 1,
    'POST' => 2,
  ),
  '/*' => array (
    'ANY' => 7,
    'GET' => 7,
    'POST' => 7,
    'PUT' => 7,
    'PATCH' => 7,
    'DELETE' => 7,
    'OPTIONS' => 7,
    'HEAD' => 7,
  ),
  '/' => array (
    'GET' => 16,
  ),
  '/home' => array (
    'GET' => 17,
  ),
  '/post' => array (
    'POST' => 18,
  ),
  '/put' => array (
    'PUT' => 19,
  ),
  '/del' => array (
    'DELETE' => 20,
  ),
  '/pd' => array (
    'POST' => 29,
    'DELETE' => 29,
  ),
  '/user/login' => array (
    'GET' => 31,
    'POST' => 31,
  ),
);

// regular routes
$regularRoutes = array (
  'rest' => array (
    0 => array (
      'dataId' => 3,
      'start' => '/rest/',
      'startLen' => 6,
      'regex' => '#^(?P<id>[1-9]\\d*)$#',
      'methods' => 'GET',
    ),
    1 => array (
      'dataId' => 4,
      'start' => '/rest/',
      'startLen' => 6,
      'regex' => '#^(?P<id>[1-9]\\d*)$#',
      'methods' => 'PUT',
    ),
    2 => array (
      'dataId' => 5,
      'start' => '/rest/',
      'startLen' => 6,
      'regex' => '#^(?P<id>[1-9]\\d*)$#',
      'methods' => 'PATCH',
    ),
    3 => array (
      'dataId' => 6,
      'start' => '/rest/',
      'startLen' => 6,
      'regex' => '#^(?P<id>[1-9]\\d*)$#',
      'methods' => 'DELETE',
    ),
  ),
  '50be3774f6' => array (
    0 => array (
      'dataId' => 15,
      'start' => '/50be3774f6/',
      'startLen' => 12,
      'regex' => '#^(?P<arg1>[^/]+)/(?P<arg2>[^/]+)/(?P<arg3>[^/]+)/(?P<arg4>[^/]+)/(?P<arg5>[^/]+)/(?P<arg6>[^/]+)/(?P<arg7>[^/]+)/(?P<arg8>[^/]+)/(?P<arg9>[^/]+)/850726135a$#',
      'methods' => 'GET',
    ),
  ),
  'user' => array (
    0 => array (
      'dataId' => 21,
      'start' => '/user/',
      'startLen' => 6,
      'regex' => '#^(?P<id>[1-9][0-9]*)/followers$#',
      'methods' => 'GET',
    ),
    1 => array (
      'dataId' => 22,
      'start' => '/user/detail/',
      'startLen' => 13,
      'regex' => '#^(?P<id>[1-9][0-9]*)$#',
      'methods' => 'GET',
    ),
    2 => array (
      'dataId' => 23,
      'start' => '/user/detail/',
      'startLen' => 13,
      'regex' => '#^(?P<id>[1-9][0-9]*)$#',
      'methods' => 'PUT',
    ),
    3 => array (
      'dataId' => 24,
      'start' => '/user/',
      'startLen' => 6,
      'regex' => '#^(?P<id>[1-9][0-9]*)$#',
      'methods' => 'GET',
    ),
    4 => array (
      'dataId' => 25,
      'start' => '/user/',
      'startLen' => 6,
      'regex' => '#^(?P<id>[1-9][0-9]*)$#',
      'methods' => 'POST',
    ),
    5 => array (
      'dataId' => 26,
      'start' => '/user/',
      'startLen' => 6,
      'regex' => '#^(?P<id>[1-9][0-9]*)$#',
      'methods' => 'PUT',
    ),
    6 => array (
      'dataId' => 27,
      'start' => '/user/',
      'startLen' => 6,
      'regex' => '#^(?P<id>[1-9][0-9]*)$#',
      'methods' => 'DELETE',
    ),
    7 => array (
      'dataId' => 41,
      'start' => '/user/',
      'startLen' => 6,
      'regex' => '#^(?P<some>[^/]+)$#',
      'methods' => 'GET',
    ),
  ),
  'del' => array (
    0 => array (
      'dataId' => 28,
      'start' => '/del/',
      'startLen' => 5,
      'regex' => '#^(?P<uid>[^/]+)$#',
      'methods' => 'DELETE',
    ),
  ),
  'home' => array (
    0 => array (
      'dataId' => 40,
      'start' => '/home/',
      'startLen' => 6,
      'regex' => '#^(?P<act>[a-zA-Z][\\w-]+)$#',
      'methods' => 'ANY,GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD',
    ),
  ),
);

// vague routes
$vagueRoutes = array (
  'GET' => array (
    0 => array (
      'dataId' => 33,
      'regex' => '#^/(?P<name>blog|saying)$#',
      'include' => NULL,
    ),
    1 => array (
      'dataId' => 34,
      'regex' => '#^/about(?:\\.html)?$#',
      'include' => '/about',
    ),
    2 => array (
      'dataId' => 35,
      'regex' => '#^/test(?:/optional)?$#',
      'include' => '/test',
    ),
    3 => array (
      'dataId' => 36,
      'regex' => '#^/blog-(?P<post>[^/]+)$#',
      'include' => '/blog-',
    ),
    4 => array (
      'dataId' => 37,
      'regex' => '#^/blog(?:index)?$#',
      'include' => '/blog',
    ),
    5 => array (
      'dataId' => 38,
      'regex' => '#^/(?P<user>[^/]+)/profile$#',
      'include' => '/profile',
    ),
    6 => array (
      'dataId' => 39,
      'regex' => '#^/my(?:/{name}(?:/{age})?)?$#',
      'include' => '/my',
    ),
  ),
);

// routes Data
$routesData = array (
  0 => array (
    'handler' => 'dump_routes',
  ),
  1 => array (
    'handler' => 'Inhere\\Route\\Examples\\Controllers\\RestController@index',
  ),
  2 => array (
    'handler' => 'Inhere\\Route\\Examples\\Controllers\\RestController@create',
  ),
  3 => array (
    'handler' => 'Inhere\\Route\\Examples\\Controllers\\RestController@view',
    'option' => array (
      'params' => array (
        'id' => '[1-9]\\d*',
      ),
    ),
    'original' => '/rest/{id}',
  ),
  4 => array (
    'handler' => 'Inhere\\Route\\Examples\\Controllers\\RestController@update',
    'option' => array (
      'params' => array (
        'id' => '[1-9]\\d*',
      ),
    ),
    'original' => '/rest/{id}',
  ),
  5 => array (
    'handler' => 'Inhere\\Route\\Examples\\Controllers\\RestController@patch',
    'option' => array (
      'params' => array (
        'id' => '[1-9]\\d*',
      ),
    ),
    'original' => '/rest/{id}',
  ),
  6 => array (
    'handler' => 'Inhere\\Route\\Examples\\Controllers\\RestController@delete',
    'option' => array (
      'params' => array (
        'id' => '[1-9]\\d*',
      ),
    ),
    'original' => '/rest/{id}',
  ),
  7 => array (
    'handler' => 'main_handler',
  ),
  15 => array (
    'handler' => 'handler0',
    'original' => '/50be3774f6/{arg1}/{arg2}/{arg3}/{arg4}/{arg5}/{arg6}/{arg7}/{arg8}/{arg9}/850726135a',
  ),
  16 => array (
    'handler' => 'handler0',
  ),
  17 => array (
    'handler' => 'Inhere\\Route\\Examples\\Controllers\\HomeController@index',
  ),
  18 => array (
    'handler' => 'post_handler',
  ),
  19 => array (
    'handler' => 'main_handler',
  ),
  20 => array (
    'handler' => 'main_handler',
  ),
  21 => array (
    'handler' => 'main_handler',
    'original' => '/user/{id}/followers',
  ),
  22 => array (
    'handler' => 'main_handler',
    'original' => '/user/detail/{id}',
  ),
  23 => array (
    'handler' => 'main_handler',
    'original' => '/user/detail/{id}',
  ),
  24 => array (
    'handler' => 'main_handler',
    'original' => '/user/{id}',
  ),
  25 => array (
    'handler' => 'main_handler',
    'original' => '/user/{id}',
  ),
  26 => array (
    'handler' => 'main_handler',
    'original' => '/user/{id}',
  ),
  27 => array (
    'handler' => 'main_handler',
    'original' => '/user/{id}',
  ),
  28 => array (
    'handler' => 'main_handler',
    'original' => '/del/{uid}',
  ),
  29 => array (
    'handler' => 'multi_method_handler',
  ),
  31 => array (
    'handler' => 'default_handler',
  ),
  33 => array (
    'handler' => 'default_handler',
    'option' => array (
      'params' => array (
        'name' => 'blog|saying',
      ),
    ),
    'original' => '/{name}',
  ),
  34 => array (
    'handler' => 'Inhere\\Route\\Examples\\Controllers\\HomeController@about',
    'original' => '/about[.html]',
  ),
  35 => array (
    'handler' => 'default_handler',
    'original' => '/test[/optional]',
  ),
  36 => array (
    'handler' => 'default_handler',
    'original' => '/blog-{post}',
  ),
  37 => array (
    'handler' => 'default_handler',
    'original' => '/blog[index]',
  ),
  38 => array (
    'handler' => 'default_handler',
    'original' => '/{user}/profile',
  ),
  39 => array (
    'handler' => 'my_handler',
    'option' => array (
      'params' => array (
        'age' => '\\d+',
      ),
      'defaults' => array (
        'name' => 'God',
        'age' => 25,
      ),
    ),
    'original' => '/my[/{name}[/{age}]]',
  ),
  40 => array (
    'handler' => 'Inhere\\Route\\Examples\\Controllers\\HomeController',
    'original' => '/home/{act}',
  ),
  41 => array (
    'handler' => 'default_handler',
    'original' => '/user/{some}',
  ),
);