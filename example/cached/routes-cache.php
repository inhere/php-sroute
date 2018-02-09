<?php
/*
 * This is routes cache file of the package `inhere/sroute`.
 * It is auto generate by Inhere\Route\CachedRouter.
 * @date 2018-01-27 17:57:49
 * @count 40
 * @notice Please don't edit it.
 */
return array (
// static routes
'staticRoutes' => array (
  '/routes' => array (
    'GET' => array (
      'handler' => 'dump_routes',
    ),
  ),
  '/rest' => array (
    'GET' => array (
      'handler' => 'Inhere\\Route\\Example\\Controllers\\RestController@index',
    ),
    'POST' => array (
      'handler' => 'Inhere\\Route\\Example\\Controllers\\RestController@create',
    ),
  ),
  '/*' => array (
    'GET' => array (
      'handler' => 'main_handler',
    ),
    'POST' => array (
      'handler' => 'main_handler',
    ),
    'PUT' => array (
      'handler' => 'main_handler',
    ),
    'PATCH' => array (
      'handler' => 'main_handler',
    ),
    'DELETE' => array (
      'handler' => 'main_handler',
    ),
    'OPTIONS' => array (
      'handler' => 'main_handler',
    ),
    'HEAD' => array (
      'handler' => 'main_handler',
    ),
  ),
  '/' => array (
    'GET' => array (
      'handler' => 'handler0',
    ),
  ),
  '/home' => array (
    'GET' => array (
      'handler' => 'Inhere\\Route\\Example\\Controllers\\HomeController@index',
    ),
  ),
  '/post' => array (
    'POST' => array (
      'handler' => 'post_handler',
    ),
  ),
  '/put' => array (
    'PUT' => array (
      'handler' => 'main_handler',
    ),
  ),
  '/del' => array (
    'DELETE' => array (
      'handler' => 'main_handler',
    ),
  ),
  '/pd' => array (
    'POST' => array (
      'handler' => 'multi_method_handler',
    ),
    'DELETE' => array (
      'handler' => 'multi_method_handler',
    ),
  ),
  '/user/login' => array (
    'GET' => array (
      'handler' => 'default_handler',
    ),
    'POST' => array (
      'handler' => 'default_handler',
    ),
  ),
),
// regular routes
'regularRoutes' => array (
  'rest' => array (
    0 => array (
      'handler' => 'Inhere\\Route\\Example\\Controllers\\RestController@view',
      'option' => array (
        'params' => array (
          'id' => '[1-9]\\d*',
        ),
      ),
      'original' => '/rest/{id}',
      'regex' => '#^/rest/(?P<id>[1-9]\\d*)$#',
      'start' => '/rest/',
      'methods' => 'GET,',
    ),
    1 => array (
      'handler' => 'Inhere\\Route\\Example\\Controllers\\RestController@update',
      'option' => array (
        'params' => array (
          'id' => '[1-9]\\d*',
        ),
      ),
      'original' => '/rest/{id}',
      'regex' => '#^/rest/(?P<id>[1-9]\\d*)$#',
      'start' => '/rest/',
      'methods' => 'PUT,',
    ),
    2 => array (
      'handler' => 'Inhere\\Route\\Example\\Controllers\\RestController@patch',
      'option' => array (
        'params' => array (
          'id' => '[1-9]\\d*',
        ),
      ),
      'original' => '/rest/{id}',
      'regex' => '#^/rest/(?P<id>[1-9]\\d*)$#',
      'start' => '/rest/',
      'methods' => 'PATCH,',
    ),
    3 => array (
      'handler' => 'Inhere\\Route\\Example\\Controllers\\RestController@delete',
      'option' => array (
        'params' => array (
          'id' => '[1-9]\\d*',
        ),
      ),
      'original' => '/rest/{id}',
      'regex' => '#^/rest/(?P<id>[1-9]\\d*)$#',
      'start' => '/rest/',
      'methods' => 'DELETE,',
    ),
  ),
  '50be3774f6' => array (
    0 => array (
      'handler' => 'handler0',
      'original' => '/50be3774f6/{arg1}/{arg2}/{arg3}/{arg4}/{arg5}/{arg6}/{arg7}/{arg8}/{arg9}/850726135a',
      'regex' => '#^/50be3774f6/(?P<arg1>[^/]+)/(?P<arg2>[^/]+)/(?P<arg3>[^/]+)/(?P<arg4>[^/]+)/(?P<arg5>[^/]+)/(?P<arg6>[^/]+)/(?P<arg7>[^/]+)/(?P<arg8>[^/]+)/(?P<arg9>[^/]+)/850726135a$#',
      'start' => '/50be3774f6/',
      'methods' => 'GET,',
    ),
  ),
  'user' => array (
    0 => array (
      'handler' => 'main_handler',
      'original' => '/user/{id}/followers',
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)/followers$#',
      'start' => '/user/',
      'methods' => 'GET,',
    ),
    1 => array (
      'handler' => 'main_handler',
      'original' => '/user/detail/{id}',
      'regex' => '#^/user/detail/(?P<id>[1-9][0-9]*)$#',
      'start' => '/user/detail/',
      'methods' => 'GET,',
    ),
    2 => array (
      'handler' => 'main_handler',
      'original' => '/user/detail/{id}',
      'regex' => '#^/user/detail/(?P<id>[1-9][0-9]*)$#',
      'start' => '/user/detail/',
      'methods' => 'PUT,',
    ),
    3 => array (
      'handler' => 'main_handler',
      'original' => '/user/{id}',
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)$#',
      'start' => '/user/',
      'methods' => 'GET,',
    ),
    4 => array (
      'handler' => 'main_handler',
      'original' => '/user/{id}',
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)$#',
      'start' => '/user/',
      'methods' => 'POST,',
    ),
    5 => array (
      'handler' => 'main_handler',
      'original' => '/user/{id}',
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)$#',
      'start' => '/user/',
      'methods' => 'PUT,',
    ),
    6 => array (
      'handler' => 'main_handler',
      'original' => '/user/{id}',
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)$#',
      'start' => '/user/',
      'methods' => 'DELETE,',
    ),
    7 => array (
      'handler' => 'default_handler',
      'original' => '/user/{some}',
      'regex' => '#^/user/(?P<some>[^/]+)$#',
      'start' => '/user/',
      'methods' => 'GET,',
    ),
  ),
  'del' => array (
    0 => array (
      'handler' => 'main_handler',
      'original' => '/del/{uid}',
      'regex' => '#^/del/(?P<uid>[^/]+)$#',
      'start' => '/del/',
      'methods' => 'DELETE,',
    ),
  ),
  'home' => array (
    0 => array (
      'handler' => 'Inhere\\Route\\Example\\Controllers\\HomeController',
      'original' => '/home/{act}',
      'regex' => '#^/home/(?P<act>[a-zA-Z][\\w-]+)$#',
      'start' => '/home/',
      'methods' => 'ANY,GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD,',
    ),
  ),
),
// vague routes
'vagueRoutes' => array (
  'GET' => array (
    0 => array (
      'handler' => 'default_handler',
      'option' => array (
        'params' => array (
          'name' => 'blog|saying',
        ),
      ),
      'original' => '/{name}',
      'regex' => '#^/(?P<name>blog|saying)$#',
      'include' => NULL,
    ),
    1 => array (
      'handler' => 'Inhere\\Route\\Example\\Controllers\\HomeController@about',
      'original' => '/about[.html]',
      'regex' => '#^/about(?:\\.html)?$#',
      'include' => '/about',
    ),
    2 => array (
      'handler' => 'default_handler',
      'original' => '/test[/optional]',
      'regex' => '#^/test(?:/optional)?$#',
      'include' => '/test',
    ),
    3 => array (
      'handler' => 'default_handler',
      'original' => '/blog-{post}',
      'regex' => '#^/blog-(?P<post>[^/]+)$#',
      'include' => '/blog-',
    ),
    4 => array (
      'handler' => 'default_handler',
      'original' => '/blog[index]',
      'regex' => '#^/blog(?:index)?$#',
      'include' => '/blog',
    ),
    5 => array (
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
      'regex' => '#^/my(?:/(?P<name>[^/]+)(?:/(?P<age>\\d+))?)?$#',
      'include' => '/my',
    ),
  ),
),
);