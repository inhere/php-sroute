<?php
/*
 * This is routes cache file of the package `inhere/sroute`.
 * It is auto generate by Inhere\Route\CachedRouter.
 * @date 2018-11-19 01:12:54
 * @count 44
 * @notice Please don't edit it.
 */
return array (
// static routes
'staticRoutes' => array (
  'GET /routes' => array(
     'path' => '/routes',
     'method' => 'GET',
     'handler' => 'dump_routes',
     'bindVars' => array (
    ),
     'params' => array (
    ),
     'pathVars' => array (
    ),
     'pathRegex' => '',
     'pathStart' => '',
     'chains' => array (
    ),
     'options' => array (
    ),
  ),
  'GET /*' => array(
     'path' => '/*',
     'method' => 'GET',
     'handler' => 'main_handler',
     'bindVars' => array (
    ),
     'params' => array (
    ),
     'pathVars' => array (
    ),
     'pathRegex' => '',
     'pathStart' => '',
     'chains' => array (
    ),
     'options' => array (
    ),
  ),
  'POST /*' => array(
     'path' => '/*',
     'method' => 'POST',
     'handler' => 'main_handler',
     'bindVars' => array (
    ),
     'params' => array (
    ),
     'pathVars' => array (
    ),
     'pathRegex' => '',
     'pathStart' => '',
     'chains' => array (
    ),
     'options' => array (
    ),
  ),
  'PUT /*' => array(
     'path' => '/*',
     'method' => 'PUT',
     'handler' => 'main_handler',
     'bindVars' => array (
    ),
     'params' => array (
    ),
     'pathVars' => array (
    ),
     'pathRegex' => '',
     'pathStart' => '',
     'chains' => array (
    ),
     'options' => array (
    ),
  ),
  'PATCH /*' => array(
     'path' => '/*',
     'method' => 'PATCH',
     'handler' => 'main_handler',
     'bindVars' => array (
    ),
     'params' => array (
    ),
     'pathVars' => array (
    ),
     'pathRegex' => '',
     'pathStart' => '',
     'chains' => array (
    ),
     'options' => array (
    ),
  ),
  'DELETE /*' => array(
     'path' => '/*',
     'method' => 'DELETE',
     'handler' => 'main_handler',
     'bindVars' => array (
    ),
     'params' => array (
    ),
     'pathVars' => array (
    ),
     'pathRegex' => '',
     'pathStart' => '',
     'chains' => array (
    ),
     'options' => array (
    ),
  ),
  'OPTIONS /*' => array(
     'path' => '/*',
     'method' => 'OPTIONS',
     'handler' => 'main_handler',
     'bindVars' => array (
    ),
     'params' => array (
    ),
     'pathVars' => array (
    ),
     'pathRegex' => '',
     'pathStart' => '',
     'chains' => array (
    ),
     'options' => array (
    ),
  ),
  'HEAD /*' => array(
     'path' => '/*',
     'method' => 'HEAD',
     'handler' => 'main_handler',
     'bindVars' => array (
    ),
     'params' => array (
    ),
     'pathVars' => array (
    ),
     'pathRegex' => '',
     'pathStart' => '',
     'chains' => array (
    ),
     'options' => array (
    ),
  ),
  'CONNECT /*' => array(
     'path' => '/*',
     'method' => 'CONNECT',
     'handler' => 'main_handler',
     'bindVars' => array (
    ),
     'params' => array (
    ),
     'pathVars' => array (
    ),
     'pathRegex' => '',
     'pathStart' => '',
     'chains' => array (
    ),
     'options' => array (
    ),
  ),
  'GET /' => array(
     'path' => '/',
     'method' => 'GET',
     'handler' => 'handler0',
     'bindVars' => array (
    ),
     'params' => array (
    ),
     'pathVars' => array (
    ),
     'pathRegex' => '',
     'pathStart' => '',
     'chains' => array (
    ),
     'options' => array (
    ),
  ),
  'GET /home' => array(
     'path' => '/home',
     'method' => 'GET',
     'handler' => 'Inhere\\Route\\Example\\Controllers\\HomeController@index',
     'bindVars' => array (
    ),
     'params' => array (
    ),
     'pathVars' => array (
    ),
     'pathRegex' => '',
     'pathStart' => '',
     'chains' => array (
    ),
     'options' => array (
    ),
  ),
  'POST /post' => array(
     'path' => '/post',
     'method' => 'POST',
     'handler' => 'post_handler',
     'bindVars' => array (
    ),
     'params' => array (
    ),
     'pathVars' => array (
    ),
     'pathRegex' => '',
     'pathStart' => '',
     'chains' => array (
    ),
     'options' => array (
    ),
  ),
  'PUT /put' => array(
     'path' => '/put',
     'method' => 'PUT',
     'handler' => 'main_handler',
     'bindVars' => array (
    ),
     'params' => array (
    ),
     'pathVars' => array (
    ),
     'pathRegex' => '',
     'pathStart' => '',
     'chains' => array (
    ),
     'options' => array (
    ),
  ),
  'DELETE /del' => array(
     'path' => '/del',
     'method' => 'DELETE',
     'handler' => 'main_handler',
     'bindVars' => array (
    ),
     'params' => array (
    ),
     'pathVars' => array (
    ),
     'pathRegex' => '',
     'pathStart' => '',
     'chains' => array (
    ),
     'options' => array (
    ),
  ),
  'POST /pd' => array(
     'path' => '/pd',
     'method' => 'POST',
     'handler' => 'multi_method_handler',
     'bindVars' => array (
    ),
     'params' => array (
    ),
     'pathVars' => array (
    ),
     'pathRegex' => '',
     'pathStart' => '',
     'chains' => array (
    ),
     'options' => array (
    ),
  ),
  'DELETE /pd' => array(
     'path' => '/pd',
     'method' => 'DELETE',
     'handler' => 'multi_method_handler',
     'bindVars' => array (
    ),
     'params' => array (
    ),
     'pathVars' => array (
    ),
     'pathRegex' => '',
     'pathStart' => '',
     'chains' => array (
    ),
     'options' => array (
    ),
  ),
  'GET /user/login' => array(
     'path' => '/user/login',
     'method' => 'GET',
     'handler' => 'default_handler',
     'bindVars' => array (
    ),
     'params' => array (
    ),
     'pathVars' => array (
    ),
     'pathRegex' => '',
     'pathStart' => '',
     'chains' => array (
    ),
     'options' => array (
    ),
  ),
  'POST /user/login' => array(
     'path' => '/user/login',
     'method' => 'POST',
     'handler' => 'default_handler',
     'bindVars' => array (
    ),
     'params' => array (
    ),
     'pathVars' => array (
    ),
     'pathRegex' => '',
     'pathStart' => '',
     'chains' => array (
    ),
     'options' => array (
    ),
  ),
),
// regular routes
'regularRoutes' => array (
  'GET 50be3774f6' => array (
    0 => array(
       'path' => '/50be3774f6/{arg1}/{arg2}/{arg3}/{arg4}/{arg5}/{arg6}/{arg7}/{arg8}/{arg9}/850726135a',
       'method' => 'GET',
       'handler' => 'handler0',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'arg1',
        1 => 'arg2',
        2 => 'arg3',
        3 => 'arg4',
        4 => 'arg5',
        5 => 'arg6',
        6 => 'arg7',
        7 => 'arg8',
        8 => 'arg9',
      ),
       'pathRegex' => '#^/50be3774f6/([^/]+)/([^/]+)/([^/]+)/([^/]+)/([^/]+)/([^/]+)/([^/]+)/([^/]+)/([^/]+)/850726135a$#',
       'pathStart' => '/50be3774f6/',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
  ),
  'GET user' => array (
    0 => array(
       'path' => '/user/{id}/followers',
       'method' => 'GET',
       'handler' => 'main_handler',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'id',
      ),
       'pathRegex' => '#^/user/([^/]+)/followers$#',
       'pathStart' => '/user/',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
    1 => array(
       'path' => '/user/detail/{id}',
       'method' => 'GET',
       'handler' => 'main_handler',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'id',
      ),
       'pathRegex' => '#^/user/detail/([^/]+)$#',
       'pathStart' => '/user/detail/',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
    2 => array(
       'path' => '/user/{id}',
       'method' => 'GET',
       'handler' => 'main_handler',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'id',
      ),
       'pathRegex' => '#^/user/([^/]+)$#',
       'pathStart' => '/user/',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
    3 => array(
       'path' => '/user/{some}',
       'method' => 'GET',
       'handler' => 'default_handler',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'some',
      ),
       'pathRegex' => '#^/user/([^/]+)$#',
       'pathStart' => '/user/',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
  ),
  'PUT user' => array (
    0 => array(
       'path' => '/user/detail/{id}',
       'method' => 'PUT',
       'handler' => 'main_handler',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'id',
      ),
       'pathRegex' => '#^/user/detail/([^/]+)$#',
       'pathStart' => '/user/detail/',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
    1 => array(
       'path' => '/user/{id}',
       'method' => 'PUT',
       'handler' => 'main_handler',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'id',
      ),
       'pathRegex' => '#^/user/([^/]+)$#',
       'pathStart' => '/user/',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
  ),
  'POST user' => array (
    0 => array(
       'path' => '/user/{id}',
       'method' => 'POST',
       'handler' => 'main_handler',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'id',
      ),
       'pathRegex' => '#^/user/([^/]+)$#',
       'pathStart' => '/user/',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
  ),
  'DELETE user' => array (
    0 => array(
       'path' => '/user/{id}',
       'method' => 'DELETE',
       'handler' => 'main_handler',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'id',
      ),
       'pathRegex' => '#^/user/([^/]+)$#',
       'pathStart' => '/user/',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
  ),
  'DELETE del' => array (
    0 => array(
       'path' => '/del/{uid}',
       'method' => 'DELETE',
       'handler' => 'main_handler',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'uid',
      ),
       'pathRegex' => '#^/del/([^/]+)$#',
       'pathStart' => '/del/',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
  ),
  'GET admin' => array (
    0 => array(
       'path' => '/admin/manage/getInfo[/id/{int}]',
       'method' => 'GET',
       'handler' => 'default_handler',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'int',
      ),
       'pathRegex' => '#^/admin/manage/getInfo(?:/id/(\\d+))?$#',
       'pathStart' => '/admin/manage/getInfo',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
  ),
  'POST admin' => array (
    0 => array(
       'path' => '/admin/manage/getInfo[/id/{int}]',
       'method' => 'POST',
       'handler' => 'default_handler',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'int',
      ),
       'pathRegex' => '#^/admin/manage/getInfo(?:/id/(\\d+))?$#',
       'pathStart' => '/admin/manage/getInfo',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
  ),
  'GET home' => array (
    0 => array(
       'path' => '/home/{act}',
       'method' => 'GET',
       'handler' => 'Inhere\\Route\\Example\\Controllers\\HomeController',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'act',
      ),
       'pathRegex' => '#^/home/([a-zA-Z][\\w-]+)$#',
       'pathStart' => '/home/',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
  ),
  'POST home' => array (
    0 => array(
       'path' => '/home/{act}',
       'method' => 'POST',
       'handler' => 'Inhere\\Route\\Example\\Controllers\\HomeController',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'act',
      ),
       'pathRegex' => '#^/home/([a-zA-Z][\\w-]+)$#',
       'pathStart' => '/home/',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
  ),
  'PUT home' => array (
    0 => array(
       'path' => '/home/{act}',
       'method' => 'PUT',
       'handler' => 'Inhere\\Route\\Example\\Controllers\\HomeController',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'act',
      ),
       'pathRegex' => '#^/home/([a-zA-Z][\\w-]+)$#',
       'pathStart' => '/home/',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
  ),
  'PATCH home' => array (
    0 => array(
       'path' => '/home/{act}',
       'method' => 'PATCH',
       'handler' => 'Inhere\\Route\\Example\\Controllers\\HomeController',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'act',
      ),
       'pathRegex' => '#^/home/([a-zA-Z][\\w-]+)$#',
       'pathStart' => '/home/',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
  ),
  'DELETE home' => array (
    0 => array(
       'path' => '/home/{act}',
       'method' => 'DELETE',
       'handler' => 'Inhere\\Route\\Example\\Controllers\\HomeController',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'act',
      ),
       'pathRegex' => '#^/home/([a-zA-Z][\\w-]+)$#',
       'pathStart' => '/home/',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
  ),
  'OPTIONS home' => array (
    0 => array(
       'path' => '/home/{act}',
       'method' => 'OPTIONS',
       'handler' => 'Inhere\\Route\\Example\\Controllers\\HomeController',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'act',
      ),
       'pathRegex' => '#^/home/([a-zA-Z][\\w-]+)$#',
       'pathStart' => '/home/',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
  ),
  'HEAD home' => array (
    0 => array(
       'path' => '/home/{act}',
       'method' => 'HEAD',
       'handler' => 'Inhere\\Route\\Example\\Controllers\\HomeController',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'act',
      ),
       'pathRegex' => '#^/home/([a-zA-Z][\\w-]+)$#',
       'pathStart' => '/home/',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
  ),
  'CONNECT home' => array (
    0 => array(
       'path' => '/home/{act}',
       'method' => 'CONNECT',
       'handler' => 'Inhere\\Route\\Example\\Controllers\\HomeController',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'act',
      ),
       'pathRegex' => '#^/home/([a-zA-Z][\\w-]+)$#',
       'pathStart' => '/home/',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
  ),
),
// vague routes
'vagueRoutes' => array (
  'GET' => array (
    0 => array(
       'path' => '/{name}',
       'method' => 'GET',
       'handler' => 'default_handler',
       'bindVars' => array (
        'name' => 'blog|saying',
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'name',
      ),
       'pathRegex' => '#^/(blog|saying)$#',
       'pathStart' => '',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
    1 => array(
       'path' => '/about[.html]',
       'method' => 'GET',
       'handler' => 'Inhere\\Route\\Example\\Controllers\\HomeController@about',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
      ),
       'pathRegex' => '#^/about(?:\\.html)?$#',
       'pathStart' => '/about',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
    2 => array(
       'path' => '/test[/optional]',
       'method' => 'GET',
       'handler' => 'default_handler',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
      ),
       'pathRegex' => '#^/test(?:/optional)?$#',
       'pathStart' => '/test',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
    3 => array(
       'path' => '/blog-{post}',
       'method' => 'GET',
       'handler' => 'default_handler',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'post',
      ),
       'pathRegex' => '#^/blog-([^/]+)$#',
       'pathStart' => '/blog-',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
    4 => array(
       'path' => '/blog[/index]',
       'method' => 'GET',
       'handler' => 'default_handler',
       'bindVars' => array (
      ),
       'params' => array (
      ),
       'pathVars' => array (
      ),
       'pathRegex' => '#^/blog(?:/index)?$#',
       'pathStart' => '/blog',
       'chains' => array (
      ),
       'options' => array (
      ),
    ),
    5 => array(
       'path' => '/my[/{name}[/{age}]]',
       'method' => 'GET',
       'handler' => 'my_handler',
       'bindVars' => array (
        'age' => '\\d+',
      ),
       'params' => array (
      ),
       'pathVars' => array (
        0 => 'name',
        1 => 'age',
      ),
       'pathRegex' => '#^/my(?:/([^/]+)(?:/(\\d+))?)?$#',
       'pathStart' => '/my',
       'chains' => array (
      ),
       'options' => array (
        'defaults' => array (
          'name' => 'God',
          'age' => 25,
        ),
      ),
    ),
  ),
),
);
