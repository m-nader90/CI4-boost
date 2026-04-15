# CodeIgniter 4 Core Framework Guidelines

## Directory Structure

```
app/
├── Config/          # Configuration files (Routes, Database, Filters, etc.)
├── Controllers/     # Controller classes
├── Controllers/
│   └── Admin/       # Sub-directory for grouped controllers
├── Models/          # Model classes
├── Entities/        # Entity classes
├── Views/           # View templates
├── Filters/         # HTTP filters / middleware
├── Helpers/         # Custom helper functions
├── Libraries/       # Custom library classes
├── ThirdParty/      # Third-party libraries
├── Language/        # Language files (en/, es/, etc.)
└── Database/
    └── Migrations/  # Database migrations
public/
├── index.php        # Front controller
└── assets/          # Public assets (css, js, images)
writable/
├── cache/           # Cache files
├── logs/            # Log files
├── session/         # Session data
└── uploads/         # Uploaded files
system/              # Framework core (never modify)
vendor/              # Composer dependencies
spark                # CLI tool
```

## PSR-4 Namespacing

All app classes follow PSR-4 autoloading. The `app` directory maps to the `App` namespace.

```php
namespace App\Controllers;
namespace App\Models;
namespace App\Entities;
namespace App\Libraries;
namespace App\Filters;
namespace App\Helpers;

namespace App\Controllers\Admin;
```

Sub-directories map to sub-namespaces. A controller in `app/Controllers/Admin/Dashboard.php` uses `namespace App\Controllers\Admin`.

## Environment Management

Environment is determined by `app/Config/Environment.php` or the `CI_ENVIRONMENT` constant in `.env`.

```env
# .env file (root directory, NEVER commit to VCS)
CI_ENVIRONMENT = development

database.default.hostname = localhost
database.default.database = myapp
database.default.username = root
database.default.password = secret
database.default.DBDriver = MySQLi

app.baseURL = 'http://localhost:8080'
```

Access environment values anywhere:

```php
// Using the env() helper
$env = env('CI_ENVIRONMENT');
$dbHost = env('database.default.hostname', 'localhost');

// Environment detection
if (ENVIRONMENT === 'development') {
    // development-only code
}
```

Valid environments: `development`, `testing`, `production`. In production, error display is disabled and debug toolbar is off.

## Configuration Files (app/Config/*.php)

Every config class extends `CodeIgniter\Config\BaseConfig` and uses namespace `App\Config`.

```php
namespace App\Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    public string $baseURL = 'http://localhost:8080';
    public string $indexPage = 'index.php';
    public string $uriProtocol = 'REQUEST_URI';
    public string $defaultLocale = 'en';
    public bool $CSPEnabled = false;
}
```

Accessing config values:

```php
// Via service
$appConfig = config('App');
echo $appConfig->baseURL;

// In controllers, models, etc.
$cacheConfig = config('Cache');
```

Overriding config per environment is done through `.env` file values or by creating environment-specific config files.

## Key Config Files

- `app/Config/App.php` - Application settings (baseURL, locale, timezone)
- `app/Config/Database.php` - Database connections
- `app/Config/Routes.php` - Route definitions
- `app/Config/Filters.php` - Filter aliases and assignments
- `app/Config/Validation.php` - Validation rule sets
- `app/Config/Autoload.php` - PSR-4 mappings and helper auto-loading
- `app/Config/Cache.php` - Cache handler and settings
- `app/Config/Email.php` - Email configuration
- `app/Config/Logger.php` - Logging thresholds and handlers
- `app/Config/Security.php` - CSRF, CSP settings
- `app/Config/Pagination.php` - Pagination defaults

## The Spark CLI

Use `spark` for code generation and tasks:

```bash
php spark make:controller Blog
php spark make:model BlogModel
php spark make:migration CreateBlogTable
php spark make:seeder BlogSeeder
php spark make:command SendEmails
php spark make:filter Auth
php spark make:library BlogService
php spark make:entity Blog
php spark migrate
php spark db:seed BlogSeeder
php spark serve
php spark routes:list
```

## Services and Factories

CI4 uses a service-based architecture. Access core services through helper functions or the `Services` class:

```php
// Helper function syntax (preferred)
$request = service('request');
$response = service('response');
$session = session();
$cache = cache();
$validation = service('validation');
$email = service('email');

// Direct service class
$request = \CodeIgniter\Config\Services::request();
```

## Autoloading

Defined in `app/Config/Autoload.php`:

```php
class Autoload extends BaseConfig
{
    public $psr4 = [
        APP_NAMESPACE => APPPATH,
    ];

    public $classmap = [];

    public $files = [];

    public $helpers = ['url', 'form'];
}
```

The `$helpers` array auto-loads helpers for every request.
