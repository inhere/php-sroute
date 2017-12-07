<?php
/*
 * This `inhere/sroute` routes cache file.
 * It is auto generate by Inhere\Route\CachedRouter.
 * @date 2017-12-07 19:43:41
 * @count 41
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
  '/rest' => array (
    'GET' => array (
      'handler' => 'Inhere\\Route\\Examples\\Controllers\\RestController@index',
      'option' => array (
      ),
    ),
    'POST' => array (
      'handler' => 'Inhere\\Route\\Examples\\Controllers\\RestController@create',
      'option' => array (
      ),
    ),
  ),
  '/*' => array (
    'ANY' => array (
      'handler' => 'main_handler',
      'option' => array (
      ),
    ),
    'GET' => array (
      'handler' => 'main_handler',
      'option' => array (
      ),
    ),
    'POST' => array (
      'handler' => 'main_handler',
      'option' => array (
      ),
    ),
    'PUT' => array (
      'handler' => 'main_handler',
      'option' => array (
      ),
    ),
    'PATCH' => array (
      'handler' => 'main_handler',
      'option' => array (
      ),
    ),
    'DELETE' => array (
      'handler' => 'main_handler',
      'option' => array (
      ),
    ),
    'OPTIONS' => array (
      'handler' => 'main_handler',
      'option' => array (
      ),
    ),
    'HEAD' => array (
      'handler' => 'main_handler',
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
  'rest' => array (
    0 => array (
      'regex' => '#^/rest/(?P<id>[1-9]\\d*)$#',
      'original' => '/rest/{id}',
      'start' => '/rest/',
      'handler' => 'Inhere\\Route\\Examples\\Controllers\\RestController@view',
      'option' => array (
        'params' => array (
          'id' => '[1-9]\\d*',
        ),
      ),
      'methods' => 'GET',
    ),
    1 => array (
      'regex' => '#^/rest/(?P<id>[1-9]\\d*)$#',
      'original' => '/rest/{id}',
      'start' => '/rest/',
      'handler' => 'Inhere\\Route\\Examples\\Controllers\\RestController@update',
      'option' => array (
        'params' => array (
          'id' => '[1-9]\\d*',
        ),
      ),
      'methods' => 'PUT',
    ),
    2 => array (
      'regex' => '#^/rest/(?P<id>[1-9]\\d*)$#',
      'original' => '/rest/{id}',
      'start' => '/rest/',
      'handler' => 'Inhere\\Route\\Examples\\Controllers\\RestController@patch',
      'option' => array (
        'params' => array (
          'id' => '[1-9]\\d*',
        ),
      ),
      'methods' => 'PATCH',
    ),
    3 => array (
      'regex' => '#^/rest/(?P<id>[1-9]\\d*)$#',
      'original' => '/rest/{id}',
      'start' => '/rest/',
      'handler' => 'Inhere\\Route\\Examples\\Controllers\\RestController@delete',
      'option' => array (
        'params' => array (
          'id' => '[1-9]\\d*',
        ),
      ),
      'methods' => 'DELETE',
    ),
  ),
  '50be3774f6' => array (
    0 => array (
      'regex' => '#^/50be3774f6/(?P<arg1>[^/]+)/(?P<arg2>[^/]+)/(?P<arg3>[^/]+)/(?P<arg4>[^/]+)/(?P<arg5>[^/]+)/(?P<arg6>[^/]+)/(?P<arg7>[^/]+)/(?P<arg8>[^/]+)/(?P<arg9>[^/]+)/850726135a$#',
      'original' => '/50be3774f6/{arg1}/{arg2}/{arg3}/{arg4}/{arg5}/{arg6}/{arg7}/{arg8}/{arg9}/850726135a',
      'start' => '/50be3774f6/',
      'handler' => 'handler0',
      'option' => array (
      ),
      'methods' => 'GET',
    ),
  ),
  'user' => array (
    0 => array (
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)/followers$#',
      'original' => '/user/{id}/followers',
      'start' => '/user/',
      'handler' => 'main_handler',
      'option' => array (
      ),
      'methods' => 'GET',
    ),
    1 => array (
      'regex' => '#^/user/detail/(?P<id>[1-9][0-9]*)$#',
      'original' => '/user/detail/{id}',
      'start' => '/user/detail/',
      'handler' => 'main_handler',
      'option' => array (
      ),
      'methods' => 'GET',
    ),
    2 => array (
      'regex' => '#^/user/detail/(?P<id>[1-9][0-9]*)$#',
      'original' => '/user/detail/{id}',
      'start' => '/user/detail/',
      'handler' => 'main_handler',
      'option' => array (
      ),
      'methods' => 'PUT',
    ),
    3 => array (
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)$#',
      'original' => '/user/{id}',
      'start' => '/user/',
      'handler' => 'main_handler',
      'option' => array (
      ),
      'methods' => 'GET',
    ),
    4 => array (
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)$#',
      'original' => '/user/{id}',
      'start' => '/user/',
      'handler' => 'main_handler',
      'option' => array (
      ),
      'methods' => 'POST',
    ),
    5 => array (
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)$#',
      'original' => '/user/{id}',
      'start' => '/user/',
      'handler' => 'main_handler',
      'option' => array (
      ),
      'methods' => 'PUT',
    ),
    6 => array (
      'regex' => '#^/user/(?P<id>[1-9][0-9]*)$#',
      'original' => '/user/{id}',
      'start' => '/user/',
      'handler' => 'main_handler',
      'option' => array (
      ),
      'methods' => 'DELETE',
    ),
    7 => array (
      'regex' => '#^/user/(?P<some>[^/]+)$#',
      'original' => '/user/{some}',
      'start' => '/user/',
      'handler' => 'default_handler',
      'option' => array (
      ),
      'methods' => 'GET',
    ),
  ),
  'del' => array (
    0 => array (
      'regex' => '#^/del/(?P<uid>[^/]+)$#',
      'original' => '/del/{uid}',
      'start' => '/del/',
      'handler' => 'main_handler',
      'option' => array (
      ),
      'methods' => 'DELETE',
    ),
  ),
  'home' => array (
    0 => array (
      'regex' => '#^/home/(?P<act>[a-zA-Z][\\w-]+)$#',
      'original' => '/home/{act}',
      'start' => '/home/',
      'handler' => 'Inhere\\Route\\Examples\\Controllers\\HomeController',
      'option' => array (
      ),
      'methods' => 'ANY,GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD',
    ),
  ),
),
// vague routes
'vagueRoutes' => array (
  'GET' => array (
    0 => array (
      'regex' => '#^/(?P<name>blog|saying)$#',
      'original' => '/{name}',
      'include' => NULL,
      'handler' => 'default_handler',
      'option' => array (
        'params' => array (
          'name' => 'blog|saying',
        ),
      ),
    ),
    1 => array (
      'regex' => '#^/about(?:\\.html)?$#',
      'original' => '/about[.html]',
      'include' => '/about',
      'handler' => 'Inhere\\Route\\Examples\\Controllers\\HomeController@about',
      'option' => array (
      ),
    ),
    2 => array (
      'regex' => '#^/test(?:/optional)?$#',
      'original' => '/test[/optional]',
      'include' => '/test',
      'handler' => 'default_handler',
      'option' => array (
      ),
    ),
    3 => array (
      'regex' => '#^/blog-(?P<post>[^/]+)$#',
      'original' => '/blog-{post}',
      'include' => '/blog-',
      'handler' => 'default_handler',
      'option' => array (
      ),
    ),
    4 => array (
      'regex' => '#^/blog(?:index)?$#',
      'original' => '/blog[index]',
      'include' => '/blog',
      'handler' => 'default_handler',
      'option' => array (
      ),
    ),
    5 => array (
      'regex' => '#^/my(?:/(?P<name>[^/]+)(?:/(?P<age>\\d+))?)?$#',
      'original' => '/my[/{name}[/{age}]]',
      'include' => '/my',
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