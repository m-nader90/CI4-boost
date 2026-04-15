---
name: routing-development
description: Define and manage CodeIgniter 4 routes, resource routes, groups, and filters.
---

# Routing Development

## When to Use This Skill

- Defining new URL routes or modifying existing ones
- Creating resource routes for CRUD operations
- Organizing routes into groups (auth, API, admin)
- Setting up route filters for middleware
- Implementing named routes and reverse routing
- Configuring environment-based routes

## Route Definition

All routes are defined in `app/Config/Routes.php`.

```php
use CodeIgniter\Router\RouteCollection;

$routes->get('/', 'Home::index');

$routes->get('about', 'Pages::about');

$routes->get('blog', 'BlogController::index');

$routes->get('blog/(:num)', 'BlogController::show/$1');

$routes->get('blog/(:segment)', 'BlogController::showBySlug/$1');

$routes->get('blog/category/(:any)', 'BlogController::category/$1');

$routes->match(['get', 'post'], 'contact', 'ContactController::handle');

$routes->post('api/login', 'AuthController::login');

$routes->put('api/users/(:num)', 'Api\UserController::update/$1');

$routes->delete('api/users/(:num)', 'Api\UserController::delete/$1');

$routes->cli('migrate', 'MigrateController::index');
```

## Route Placeholders

```php
$routes->get('users/(:num)', 'Users::show/$1');
$routes->get('posts/(:segment)', 'Posts::show/$1');
$routes->get('files/(:any)', 'Files::serve/$1');
$routes->get('search/(:alphanum)', 'Search::query/$1');
$routes->get('archive/(:year)/(:month)', 'Blog::archive/$1/$2');

$routes->add('product/(:num)/(:num)', 'Catalog::product/$1/$2');

$routes->get('pages/(:any)', static function ($slug) {
    return view('pages/' . $slug);
});
```

Regex placeholders:

```php
$routes->get('users/(:id)', 'Users::show/$1');
$routes->addPlaceholder('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
```

## Resource Routes

```php
$routes->resource('posts');
$routes->resource('photos', ['controller' => 'Gallery']);
$routes->resource('api/users', ['controller' => 'Api\UserController']);
```

This maps to:

| HTTP Method | Route | Controller Method |
|-------------|-------|-------------------|
| GET | /posts | index |
| GET | /posts/new | new |
| POST | /posts | create |
| GET | /posts/(.*) | show/$1 |
| GET | /posts/(.*)/edit | edit/$1 |
| PUT/PATCH | /posts/(.*) | update/$1 |
| DELETE | /posts/(.*) | delete/$1 |

Limit resource routes:

```php
$routes->resource('api/posts', [
    'only'  => ['index', 'show', 'create', 'update'],
]);

$routes->resource('api/posts', [
    'except' => ['new', 'edit'],
]);
```

Customize resource route placeholders:

```php
$routes->resource('posts', [
    'placeholder' => '(:num)',
    'controller'  => 'BlogController',
]);
```

## Presenter Routes

Presenter routes handle both GET (form) and POST (submit) with a single method:

```php
$routes->presenter('posts');
```

Maps to:

| HTTP Method | Route | Method |
|-------------|-------|--------|
| GET | /posts | index |
| GET | /posts/new | new |
| POST | /posts | create |
| GET | /posts/(.*) | show/$1 |
| GET | /posts/(.*)/edit | edit/$1 |
| POST | /posts/(.*)/edit | update/$1 |
| POST | /posts/(.*)/delete | delete/$1 |

## Route Groups

Organize routes by prefix, filters, or namespace:

```php
$routes->group('admin', static function ($routes) {
    $routes->get('dashboard', 'Admin\DashboardController::index');
    $routes->get('users', 'Admin\UserController::index');
    $routes->get('users/(:num)', 'Admin\UserController::show/$1');
    $routes->get('settings', 'Admin\SettingsController::index');
});
```

Group with filter:

```php
$routes->group('admin', ['filter' => 'auth'], static function ($routes) {
    $routes->get('dashboard', 'Admin\DashboardController::index');
    $routes->resource('users', ['controller' => 'Admin\UserController']);
});
```

API group with namespace:

```php
$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    $routes->resource('users');
    $routes->resource('posts');
    $routes->post('auth/login', 'AuthController::login');
    $routes->post('auth/logout', 'AuthController::logout', ['filter' => 'auth:api']);
});
```

Multiple filters on a group:

```php
$routes->group('api', ['filter' => ['cors', 'auth:api']], static function ($routes) {
    $routes->get('profile', 'UserController::profile');
    $routes->put('profile', 'UserController::update');
});
```

Nested groups:

```php
$routes->group('api', ['namespace' => 'App\Controllers\Api', 'filter' => 'cors'], static function ($routes) {
    $routes->group('v1', static function ($routes) {
        $routes->resource('users');
        $routes->resource('posts');
    });
});
```

Group with subdomain:

```php
$routes->group('api', ['subdomain' => 'api'], static function ($routes) {
    $routes->get('users', 'Api\UserController::index');
});
```

## Named Routes

```php
$routes->add('login', 'AuthController::login', ['as' => 'login']);
$routes->add('register', 'AuthController::register', ['as' => 'register']);
$routes->add('dashboard', 'DashboardController::index', ['as' => 'dashboard']);
$routes->add('profile/(:num)', 'UserController::show/$1', ['as' => 'profile']);
```

## Reverse Routing

Generate URLs from named routes:

```php
<a href="<?= route_to('login') ?>">Login</a>
<a href="<?= route_to('profile', $userId) ?>">View Profile</a>

$url = route_to('dashboard');
$url = route_to('profile', 42);

return redirect()->route('login');
return redirect()->route('profile', ['id' => $userId]);
```

In controllers:

```php
return redirect()->to(route_to('post.show', $slug));
```

## Route Filters

Register filters in `app/Config/Filters.php`:

```php
<?php

namespace App\Config;

use CodeIgniter\Config\Filters as BaseFilters;

class Filters extends BaseFilters
{
    public array $aliases = [
        'auth'     => \App\Filters\AuthFilter::class,
        'cors'     => \App\Filters\CorsFilter::class,
        'throttle' => \App\Filters\ThrottleFilter::class,
        'admin'    => \App\Filters\AdminFilter::class,
    ];

    public array $globals = [
        'before' => [
            'cors',
        ],
        'after' => [],
    ];

    public array $methods = [
        'post' => ['csrf'],
    ];

    public array $filters = [
        'auth' => ['before' => ['admin/*', 'profile/*']],
        'throttle' => ['before' => ['api/*']],
    ];
}
```

Custom filter:

```php
<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('logged_in')) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Unauthorized'])
                    ->setStatusCode(401);
            }

            return redirect()->route('login')->with('error', 'Please log in');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
```

Filter with arguments:

```php
$routes->group('api', ['filter' => 'auth:api'], static function ($routes) {
    $routes->resource('users');
});

$routes->add('admin/dashboard', 'AdminController::index', ['filter' => 'role:admin,super']);
```

Filter class reading arguments:

```php
public function before(RequestInterface $request, $arguments = null)
{
    $requiredRole = $arguments[0] ?? 'user';
    $userRole = session()->get('role');

    if ($userRole !== $requiredRole) {
        return redirect()->to('/')->with('error', 'Access denied');
    }
}
```

## Environment-Based Routes

`app/Config/Routes.php`:

```php
if (ENVIRONMENT === 'development') {
    $routes->get('debug/bar', 'DebugController::bar');
    $routes->get('debug/routes', 'DebugController::routes');
}

$routes->setAutoRoute(false);

$routes->set404Override(function () {
    return view('errors/custom_404');
});

$routes->setTranslateURIDashes(false);
```

## CLI Routes

```php
$routes->cli('migrate/refresh', 'Migrate::refresh');
$routes->cli('cache:clear', 'CacheController::clear');
$routes->cli('import/(:segment)', 'ImportController::run/$1');
```
