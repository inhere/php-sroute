<?php
/*
 * This `inhere/sroute` routes cache file.
 * It is auto generate by Inhere\Route\CachedRouter.
 * @date 2017-12-03 22:25:37
 * @count 22
 * @notice Please don't edit it.
 */
return array (
// static routes
'staticRoutes' => array (
  '/routes' => array (
    'GET' => array (
      'handler' => 'dump_routes',
      'option' => array (
      ),
    ),
  ),
  '/' => array (
    'GET' => array (
      'handler' => 'handler0',
      'option' => array (
      ),
    ),
  ),
  '/home' => array (
    'GET' => array (
      'handler' => 'Inhere\\Route\\Examples\\Controllers\\HomeController@index',
      'option' => array (
      ),
    ),
  ),
  '/post' => array (
    'POST' => array (
      'handler' => 'post_handler',
      'option' => array (
      ),
    ),
  ),
  '/put' => array (
    'PUT' => array (
      'handler' => 'main_handler',
      'option' => array (
      ),
    ),
  ),
  '/del' => array (
    'DELETE' => array (
      'handler' => 'main_handler',
      'option' => array (
      ),
    ),
  ),
  '/pd' => array (
    'POST' => array (
      'handler' => 'multi_method_handler',
      'option' => array (
      ),
    ),
    'DELETE' => array (
      'handler' => 'multi_method_handler',
      'option' => array (
      ),
    ),
  ),
  '/user/login' => array (
    'GET' => array (
      'handler' => 'default_handler',
      'option' => array (
      ),
    ),
    'POST' => array (
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
      'handler' => 'handler0',
      'option' => array (
      ),
      'methods' => 'GET',
    ),
  ),
  'user' => array (
    0 => array (
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)$#',
      'start' => '/user/',
      'original' => '/user/{id}',
      'handler' => 'main_handler',
      'option' => array (
      ),
      'methods' => 'GET',
    ),
    1 => array (
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)$#',
      'start' => '/user/',
      'original' => '/user/{id}',
      'handler' => 'main_handler',
      'option' => array (
      ),
      'methods' => 'POST',
    ),
    2 => array (
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)$#',
      'start' => '/user/',
      'original' => '/user/{id}',
      'handler' => 'main_handler',
      'option' => array (
      ),
      'methods' => 'PUT',
    ),
    3 => array (
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)$#',
      'start' => '/user/',
      'original' => '/user/{id}',
      'handler' => 'main_handler',
      'option' => array (
      ),
      'methods' => 'DELETE',
    ),
    4 => array (
      'regex' => '#^/user/(?P<some>[^/]+)$#',
      'start' => '/user/',
      'original' => '/user/{some}',
      'handler' => 'default_handler',
      'option' => array (
      ),
      'methods' => 'GET',
    ),
  ),
  'del' => array (
    0 => array (
      'regex' => '#^/del/(?P<uid>[^/]+)$#',
      'start' => '/del/',
      'original' => '/del/{uid}',
      'handler' => 'main_handler',
      'option' => array (
      ),
      'methods' => 'DELETE',
    ),
  ),
  'home' => array (
    0 => array (
      'regex' => '#^/home/(?P<act>[a-zA-Z][\\w-]+)$#',
      'start' => '/home/',
      'original' => '/home/{act}',
      'handler' => 'Inhere\\Route\\Examples\\Controllers\\HomeController',
      'option' => array (
      ),
      'methods' => 'ANY,GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD,SEARCH,CONNECT,TRACE',
    ),
  ),
),
// vague routes
'vagueRoutes' => array (
  'GET' => array (
    0 => array (
      'regex' => '#^/about(?:\\.html)?$#',
      'include' => '/about',
      'original' => '/about[.html]',
      'handler' => 'Inhere\\Route\\Examples\\Controllers\\HomeController@about',
      'option' => array (
      ),
    ),
    1 => array (
      'regex' => '#^/(?P<name>blog|saying)$#',
      'include' => NULL,
      'original' => '/{name}',
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
      'handler' => 'default_handler',
      'option' => array (
      ),
    ),
    3 => array (
      'regex' => '#^/blog-(?P<post>[^/]+)$#',
      'include' => '/blog-',
      'original' => '/blog-{post}',
      'handler' => 'default_handler',
      'option' => array (
      ),
    ),
    4 => array (
      'regex' => '#^/blog(?:index)?$#',
      'include' => '/blog',
      'original' => '/blog[index]',
      'handler' => 'default_handler',
      'option' => array (
      ),
    ),
    5 => array (
      'regex' => '#^/my(?:/(?P<name>[^/]+)(?:/(?P<age>\\d+))?)?$#',
      'include' => '/my',
      'original' => '/my[/{name}[/{age}]]',
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
),
);