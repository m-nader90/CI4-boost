# CodeIgniter 4 Middleware / Filters Guidelines

## Understanding Filters in CI4

Filters in CodeIgniter 4 are equivalent to middleware in other frameworks. They intercept HTTP requests before they reach the controller (before filters) or after the controller has executed (after filters).

## Creating Filters

Filters are stored in `app/Filters/` and must implement `CodeIgniter\Filters\FilterInterface`.

```php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (!$session->get('logged_in')) {
            if ($request->isAJAX()) {
                return $this->response
                    ->setJSON(['error' => 'Unauthorized'])
                    ->setStatusCode(401);
            }
            return redirect()->to('/login')
                ->with('error', 'Please log in to continue');
        }

        if ($arguments && in_array('admin', $arguments)) {
            if ($session->get('role') !== 'admin') {
                return redirect()->to('/dashboard')
                    ->with('error', 'Access denied');
            }
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
```

### After-Only Filter Example

```php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CorsFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        return $response;
    }
}
```

## Registering Filters in Config

Register filter aliases in `app/Config/Filters.php`:

```php
namespace App\Config;

use CodeIgniter\Config\BaseConfig;

class Filters extends BaseConfig
{
    public array $aliases = [
        'csrf'     => \CodeIgniter\Filters\CSRF::class,
        'toolbar'  => \CodeIgniter\Debug\Toolbar::class,
        'honeypot' => \CodeIgniter\Filters\Honeypot::class,
        'auth'     => \App\Filters\AuthFilter::class,
        'cors'     => \App\Filters\CorsFilter::class,
        'throttle' => \App\Filters\ThrottleFilter::class,
        'locale'   => \App\Filters\LocaleFilter::class,
        'api'      => \App\Filters\ApiFilter::class,
    ];

    public array $globals = [
        'before' => [
            'csrf' => ['except' => ['api/*']],
            'honeypot',
        ],
        'after' => [
            'toolbar',
        ],
    ];

    public array $methods = [
        'post'   => ['csrf'],
        'put'    => ['csrf'],
        'delete' => ['csrf'],
    ];

    public array $filters = [
        'auth' => ['before' => ['admin/*', 'dashboard']],
        'cors' => ['after' => ['api/*']],
    ];
}
```

## Filter Configuration Options

### Global Filters (`$globals`)

Run on every request:

```php
public array $globals = [
    'before' => [
        'csrf',
        'honeypot' => ['except' => ['api/*']],
    ],
    'after' => [
        'toolbar' => ['except' => ['api/*']],
    ],
];
```

Use `'except'` to exclude URI patterns from the filter.

### HTTP Method Filters (`$methods`)

Apply filters to specific HTTP methods:

```php
public array $methods = [
    'post'   => ['csrf', 'throttle'],
    'put'    => ['csrf', 'throttle'],
    'delete' => ['csrf', 'auth'],
];
```

### URI Pattern Filters (`$filters`)

Apply filters to specific URI patterns:

```php
public array $filters = [
    'auth'     => ['before' => ['admin/*', 'dashboard', 'profile']],
    'cors'     => ['after'  => ['api/*']],
    'throttle' => ['before' => ['api/*', 'login', 'register']],
];
```

## Route-Level Filter Assignment

Apply filters directly in `app/Config/Routes.php`:

```php
// Single filter on a route
$routes->get('admin', 'AdminController::index', ['filter' => 'auth']);

// Multiple filters
$routes->post('api/posts', 'API\Post::create', ['filter' => 'auth|throttle:60,1']);

// Filter with arguments
$routes->get('admin/users', 'Admin\Users::index', ['filter' => 'auth:admin']);

// Filter on route group
$routes->group('admin', ['filter' => 'auth:admin'], static function ($routes) {
    $routes->get('dashboard', 'Admin\Dashboard::index');
    $routes->resource('users', ['controller' => 'Admin\Users']);
});

$routes->group('api', ['filter' => 'auth:api|cors'], static function ($routes) {
    $routes->resource('posts');
});
```

## Practical Filter Examples

### Throttle Filter

```php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ThrottleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $throttler = service('throttler');

        $rate = $arguments[0] ?? 60;
        $period = $arguments[1] ?? MINUTE;

        if ($throttler->check(md5($request->getIPAddress()), $rate, $period) === false) {
            return service('response')
                ->setStatusCode(429)
                ->setJSON(['error' => 'Too many requests', 'retry_after' => $throttler->tokenTimeLeft()]);
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
```

### Locale Filter

```php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class LocaleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $locale = $request->getGet('lang') ?? $session->get('locale') ?? config('App')->defaultLocale;

        if (in_array($locale, ['en', 'es', 'fr', 'de'])) {
            $session->set('locale', $locale);
            service('request')->setLocale($locale);
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
```

### API Auth Filter (JWT Example)

```php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return service('response')
                ->setJSON(['error' => 'Token required'])
                ->setStatusCode(401);
        }

        $token = substr($authHeader, 7);
        $payload = verify_jwt($token);

        if (!$payload) {
            return service('response')
                ->setJSON(['error' => 'Invalid or expired token'])
                ->setStatusCode(401);
        }

        $request->user = $payload;

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
```

### Maintenance Mode Filter

```php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MaintenanceFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (env('APP_MAINTENANCE') === 'true') {
            if ($request->getIPAddress() !== env('ALLOWED_IP')) {
                return service('response')
                    ->setStatusCode(503)
                    ->setBody(view('errors/maintenance'));
            }
        }
        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
```

## CSRF Protection

CI4 includes built-in CSRF protection:

```php
// Already registered globally in default Config/Filters.php
public array $aliases = [
    'csrf' => \CodeIgniter\Filters\CSRF::class,
];

// In forms, include the CSRF token
echo csrf_field();

// AJAX requests - include X-CSRF-Token header
// The token is available via csrf_token() or csrf_hash()
```

## Key Points

- `before()` must return either the modified `$request` or a `$response` to halt execution
- `after()` must always return the `$response`
- Filter arguments are passed as an array after the filter name in routes (`auth:admin,editor`)
- Use `except` in global filters to skip specific URIs
- Never call `exit()` or `die()` in a filter; return a response instead
