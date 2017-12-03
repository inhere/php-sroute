<?php
/*
 * This `inhere/sroute` routes cache file.
 * It is auto generate by Inhere\Route\CachedRouter.
 * @date 2017-12-03 13:48:20
 * @count 22
 * @notice Please don't edit it.
 */
return array (
// static routes
'staticRoutes' => array (
  '/routes' => array (
    'GET' => array (
      'methods' => 'GET',
      'handler' => 'dump_routes',
      'option' => array (
      ),
    ),
  ),
  '/' => array (
    'GET' => array (
      'methods' => 'GET',
      'handler' => 'handler0',
      'option' => array (
      ),
    ),
  ),
  '/home' => array (
    'GET' => array (
      'methods' => 'GET',
      'handler' => 'Inhere\\Route\\Examples\\Controllers\\HomeController@index',
      'option' => array (
      ),
    ),
  ),
  '/post' => array (
    'POST' => array (
      'methods' => 'POST',
      'handler' => 'post_handler',
      'option' => array (
      ),
    ),
  ),
  '/put' => array (
    'PUT' => array (
      'methods' => 'PUT',
      'handler' => 'main_handler',
      'option' => array (
      ),
    ),
  ),
  '/del' => array (
    'DELETE' => array (
      'methods' => 'DELETE',
      'handler' => 'main_handler',
      'option' => array (
      ),
    ),
  ),
  '/pd' => array (
    'POST,DELETE' => array (
      'methods' => 'POST,DELETE',
      'handler' => 'multi_method_handler',
      'option' => array (
      ),
    ),
  ),
  '/user/login' => array (
    'GET,POST' => array (
      'methods' => 'GET,POST',
      'handler' => 'default_handler',
      'option' => array (
      ),
    ),
  ),
),
// regular routes
'regularRoutes' => array (
  '50be3774f6' => array (
    0 => array (
      'regex' => '#^/50be3774f6/(?P<arg1>[^/]+)/(?P<arg2>[^/]+)/(?P<arg3>[^/]+)/(?P<arg4>[^/]+)/(?P<arg5>[^/]+)/(?P<arg6>[^/]+)/(?P<arg7>[^/]+)/(?P<arg8>[^/]+)/(?P<arg9>[^/]+)/850726135a$#',
      'start' => '/50be3774f6/',
      'original' => '/50be3774f6/{arg1}/{arg2}/{arg3}/{arg4}/{arg5}/{arg6}/{arg7}/{arg8}/{arg9}/850726135a',
      'methods' => 'GET',
      'handler' => 'handler0',
      'option' => array (
      ),
    ),
  ),
  'user' => array (
    0 => array (
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)$#',
      'start' => '/user/',
      'original' => '/user/{id}',
      'methods' => 'GET',
      'handler' => 'main_handler',
      'option' => array (
      ),
    ),
    1 => array (
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)$#',
      'start' => '/user/',
      'original' => '/user/{id}',
      'methods' => 'POST',
      'handler' => 'main_handler',
      'option' => array (
      ),
    ),
    2 => array (
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)$#',
      'start' => '/user/',
      'original' => '/user/{id}',
      'methods' => 'PUT',
      'handler' => 'main_handler',
      'option' => array (
      ),
    ),
    3 => array (
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)$#',
      'start' => '/user/',
      'original' => '/user/{id}',
      'methods' => 'DELETE',
      'handler' => 'main_handler',
      'option' => array (
      ),
    ),
    4 => array (
      'regex' => '#^/user/(?P<some>[^/]+)$#',
      'start' => '/user/',
      'original' => '/user/{some}',
      'methods' => 'GET',
      'handler' => 'default_handler',
      'option' => array (
      ),
    ),
  ),
  'del' => array (
    0 => array (
      'regex' => '#^/del/(?P<uid>[^/]+)$#',
      'start' => '/del/',
      'original' => '/del/{uid}',
      'methods' => 'DELETE',
      'handler' => 'main_handler',
      'option' => array (
      ),
    ),
  ),
  'home' => array (
    0 => array (
      'regex' => '#^/home/(?P<act>[a-zA-Z][\\w-]+)$#',
      'start' => '/home/',
      'original' => '/home/{act}',
      'methods' => 'ANY,GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD,SEARCH,CONNECT,TRACE',
      'handler' => 'Inhere\\Route\\Examples\\Controllers\\HomeController',
      'option' => array (
      ),
    ),
  ),
),
// vague routes
'vagueRoutes' => array (
  0 => array (
    'regex' => '#^/about(?:\\.html)?$#',
    'include' => '/about',
    'original' => '/about[.html]',
    'methods' => 'GET',
    'handler' => 'Inhere\\Route\\Examples\\Controllers\\HomeController@about',
    'option' => array (
    ),
  ),
  1 => array (
    'regex' => '#^/(?P<name>blog|saying)$#',
    'include' => NULL,
    'original' => '/{name}',
    'methods' => 'GET',
    'handler' => 'default_handler',
    'option' => array (
      'params' => array (
        'name' => 'blog|saying',
      ),
    ),
  ),
  2 => array (
    'regex' => '#^/test(?:/optional)?$#',
    'include' => '/test',
    'original' => '/test[/optional]',
    'methods' => 'GET',
    'handler' => 'default_handler',
    'option' => array (
    ),
  ),
  3 => array (
    'regex' => '#^/blog-(?P<post>[^/]+)$#',
    'include' => '/blog-',
    'original' => '/blog-{post}',
    'methods' => 'GET',
    'handler' => 'default_handler',
    'option' => array (
    ),
  ),
  4 => array (
    'regex' => '#^/blog(?:index)?$#',
    'include' => '/blog',
    'original' => '/blog[index]',
    'methods' => 'GET',
    'handler' => 'default_handler',
    'option' => array (
    ),
  ),
  5 => array (
    'regex' => '#^/my(?:/(?P<name>[^/]+)(?:/(?P<age>\\d+))?)?$#',
    'include' => '/my',
    'original' => '/my[/{name}[/{age}]]',
    'methods' => 'GET',
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
  ),
),
);