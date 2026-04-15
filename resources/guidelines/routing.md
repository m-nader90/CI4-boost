# CodeIgniter 4 Routing Guidelines

## Route Definition File

All routes are defined in `app/Config/Routes.php`.

```php
use CodeIgniter\Router\RouteCollection;

$routes = RouteCollection::getRoutes();

$routes->get('/', 'Home::index');
```

## Basic Routes

```php
$routes->get('blog', 'Blog::index');
$routes->get('blog/(:num)', 'Blog::show/$1');
$routes->get('blog/new', 'Blog::new');
$routes->post('blog', 'Blog::create');
$routes->get('blog/(:num)/edit', 'Blog::edit/$1');
$routes->put('blog/(:num)', 'Blog::update/$1');
$routes->delete('blog/(:num)', 'Blog::delete/$1');

$routes->add('pages/(:any)', 'Pages::view/$1');
$routes->match(['get', 'post'], 'contact', 'Contact::process');
$routes->cli('migrate/refresh', 'Tools::migrateRefresh');
```

## Placeholders

| Placeholder | Regex | Description |
|-------------|-------|-------------|
| `(:any)` | `[^/]+` | Any character except `/` |
| `(:num)` | `[0-9]+` | Numeric characters only |
| `(:segment)` | `[^/]+` | Same as `:any` (alias) |
| `(:alpha)` | `[a-zA-Z]+` | Alphabetical characters only |
| `(:alphanum)` | `[a-zA-Z0-9]+` | Alphanumeric characters only |
| `(:hash)` | `[a-fA-F0-9]+` | Hexadecimal characters only |

Custom regex placeholders:

```php
$routes->addPlaceholder('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
$routes->get('users/(:uuid)', 'Users::show/$1');
```

## Resource Routes

```php
$routes->resource('blogs');
```

Generates these routes:

| HTTP Verb | Route | Method |
|-----------|-------|--------|
| GET | `/blogs` | `index` |
| GET | `/blogs/new` | `new` |
| POST | `/blogs` | `create` |
| GET | `/blogs/(.+)` | `show/$1` |
| GET | `/blogs/(.+)/edit` | `edit/$1` |
| PUT/PATCH | `/blogs/(.+)` | `update/$1` |
| DELETE | `/blogs/(.+)` | `delete/$1` |

Customize resource routes:

```php
$routes->resource('blogs', [
    'controller' => 'Blog',
    'only'       => ['index', 'show'],
    'except'     => ['new', 'edit'],
    'placeholder' => ':num',
]);

$routes->resource('api/posts', ['controller' => 'API\Post']);
```

## Presenter Routes

```php
$routes->presenter('photos');
```

Generates routes for a presenter pattern (no resource-style CRUD):

| HTTP Verb | Route | Method |
|-----------|-------|--------|
| GET | `/photos` | `index` |
| GET | `/photos/show/(.+)` | `show/$1` |
| GET | `/photos/new` | `new` |
| POST | `/photos/create` | `create` |
| GET | `/photos/edit/(.+)` | `edit/$1` |
| POST | `/photos/update/(.+)` | `update/$1` |
| POST | `/photos/delete/(.+)` | `delete/$1` |

## Route Groups

```php
$routes->group('admin', static function ($routes) {
    $routes->get('dashboard', 'Admin\Dashboard::index');
    $routes->get('users', 'Admin\Users::index');
    $routes->resource('posts', ['controller' => 'Admin\Posts']);
});

$routes->group('api', ['filter' => 'auth:api'], static function ($routes) {
    $routes->resource('users', ['controller' => 'API\Users']);
    $routes->get('settings', 'API\Settings::index');
});

$routes->group('api/v1', ['namespace' => 'App\Controllers\API\v1'], static function ($routes) {
    $routes->resource('posts');
});
```

Group options: `filter`, `namespace`, `hostname`, `subdomain`, `offset`.

## Named Routes

```php
$routes->get('blog/(:num)', 'Blog::show/$1', ['as' => 'blog.show']);
$routes->get('user/profile', 'User::profile', ['as' => 'profile']);

$routes->resource('blogs', [
    'controller' => 'Blog',
    'only'       => ['index', 'show'],
]);
// Named: blog.index, blog.show
```

Reverse routing with `route_to()`:

```php
// In views or controllers
$url = route_to('blog.show', $blogId);
$url = route_to('profile');
$url = route_to('blog.create');
```

## Route Filters

```php
// Single filter
$routes->get('admin', 'Admin::index', ['filter' => 'auth']);

// Multiple filters
$routes->post('api/data', 'API::data', ['filter' => 'auth|csrf']);

// Filter with parameters
$routes->get('admin/users', 'Admin\Users::index', ['filter' => 'auth:admin']);

// Filter on group
$routes->group('admin', ['filter' => 'auth'], static function ($routes) {
    $routes->get('dashboard', 'Admin\Dashboard::index');
});
```

## HTTP Verb-Based Routing

```php
$routes->get('api/posts', 'API\Posts::index');
$routes->post('api/posts', 'API\Posts::create');
$routes->put('api/posts/(:num)', 'API\Posts::update/$1');
$routes->patch('api/posts/(:num)', 'API\Posts::update/$1');
$routes->delete('api/posts/(:num)', 'API\Posts::delete/$1');
$routes->options('api/posts', 'API\Posts::options');
$routes->head('api/posts', 'API\Posts::head');
```

## 404 Override

```php
$routes->set404Override(static function () {
    return view('errors/html/error_404_custom');
});
```

## Environment-Restricted Routes

```php
if (ENVIRONMENT === 'development') {
    $routes->get('debug-bar', 'DevTools::debugBar');
}
```

## CLI Routes

```php
$routes->cli('migrate/refresh', 'Tools::migrateRefresh');
$routes->cli('cache/clear', 'Tools::clearCache');
```
