# CodeIgniter 4 Libraries Guidelines

## Using Libraries

In CodeIgniter 4, libraries are classes stored in `app/Libraries/`. Unlike CI3, CI4 does NOT use `$this->load->library()`. Instead, instantiate libraries directly or use the Services pattern.

```php
// Direct instantiation (most common)
$pdf = new \App\Libraries\PdfGenerator();

// With constructor parameters
$mail = new \App\Libraries\Mailer([
    'host' => 'smtp.example.com',
    'port' => 587,
]);
```

## Creating Custom Libraries

```php
namespace App\Libraries;

class PdfGenerator
{
    protected string $orientation = 'portrait';
    protected string $size = 'A4';
    protected array $options = [];

    public function __construct(array $config = [])
    {
        if (isset($config['orientation'])) {
            $this->orientation = $config['orientation'];
        }
        if (isset($config['size'])) {
            $this->size = $config['size'];
        }
        $this->options = $config['options'] ?? [];
    }

    public function setOrientation(string $orientation): self
    {
        $this->orientation = $orientation;
        return $this;
    }

    public function setSize(string $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function generate(string $html, string $filename): string
    {
        $filepath = WRITEPATH . 'uploads/' . $filename . '.pdf';
        file_put_contents($filepath, $this->renderPdf($html));
        return $filepath;
    }

    public function download(string $html, string $filename)
    {
        $content = $this->renderPdf($html);
        return service('response')
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"')
            ->setBody($content);
    }

    protected function renderPdf(string $html): string
    {
        return $html;
    }
}
```

## Shared Instances via Services

For libraries that should be shared (singleton pattern), register them as services.

### Creating a Custom Service

```php
namespace App\Libraries;

class StripeService
{
    protected \Stripe\StripeClient $client;

    public function __construct()
    {
        $this->client = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));
    }

    public function createPaymentIntent(int $amount, string $currency = 'usd'): \Stripe\PaymentIntent
    {
        return $this->client->paymentIntents->create([
            'amount'   => $amount,
            'currency' => $currency,
        ]);
    }

    public function getCustomer(string $customerId): \Stripe\Customer
    {
        return $this->client->customers->retrieve($customerId);
    }
}
```

### Registering the Service

Create `app/Config/Services.php` (or edit the existing one):

```php
namespace App\Config;

use CodeIgniter\Config\Services as CoreServices;

class Services extends CoreServices
{
    public static function stripe(array $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('stripe');
        }
        return new \App\Libraries\StripeService();
    }

    public static function pdfGenerator(array $config = [], bool $getShared = false)
    {
        return new \App\Libraries\PdfGenerator($config);
    }

    public static function mailer(array $config = [], bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('mailer');
        }
        return new \App\Libraries\Mailer($config);
    }
}
```

### Using the Service

```php
// Shared instance (singleton)
$stripe = service('stripe');
$paymentIntent = $stripe->createPaymentIntent(1000, 'usd');

// Fresh instance (non-shared)
$mailer = service('mailer', ['host' => 'smtp2.example.com'], false);
$mailer->send($to, $subject, $body);
```

## Using Built-in Services

CI4 provides many built-in services:

```php
// Email
$email = service('email');
$email->setFrom('admin@example.com', 'Admin');
$email->setTo('user@example.com');
$email->setSubject('Welcome');
$email->setMessage(view('emails/welcome', $data));
$email->send();

// Curl HTTP Client
$client = service('curlrequest');
$response = $client->get('https://api.example.com/data');
$data = json_decode($response->getBody(), true);

// Session
$session = service('session');
$session->set('user_id', $userId);
$userId = $session->get('user_id');
$session->remove('user_id');
$session->destroy();

// Cache
$cache = service('cache');
$cache->save('key', $data, 3600);
$data = $cache->get('key');
$cache->delete('key');

// Encryption
$encrypter = service('encrypter');
$encrypted = $encrypter->encrypt($data);
$decrypted = $encrypter->decrypt($encrypted);

// Image manipulation
$image = service('image')->withFile('/path/to/image.jpg');
$image->resize(300, 200, true)
    ->save('/path/to/resized.jpg');

// Filesystem
$filesystem = service('filesystem');
$files = $filesystem->glob(WRITEPATH . 'uploads/*');
```

## Dependency Injection

CI4 supports constructor dependency injection for controllers and libraries:

```php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BlogModel;
use App\Libraries\BlogService;

class Blog extends BaseController
{
    protected BlogModel $model;
    protected BlogService $service;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->model = new BlogModel();
        $this->service = new BlogService($this->model);
    }

    public function index(): string
    {
        $posts = $this->service->getPublishedPosts();
        return view('blog/index', ['posts' => $posts]);
    }
}
```

DI in libraries:

```php
namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use App\Models\AuditLogModel;

class AuditLogger
{
    protected BaseConnection $db;
    protected AuditLogModel $model;

    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
        $this->model = new AuditLogModel();
    }

    public function log(string $action, array $context = []): void
    {
        $this->model->insert([
            'action'      => $action,
            'user_id'     => session()->get('user_id'),
            'context'     => json_encode($context),
            'ip_address'  => service('request')->getIPAddress(),
        ]);
    }
}

// Usage - CI4 auto-resolves dependencies
$logger = new \App\Libraries\AuditLogger(\Config\Database::connect());
```

## Organizing Library Code

For complex libraries, consider splitting into focused classes:

```
app/Libraries/
├── Billing/
│   ├── BillingService.php
│   ├── InvoiceGenerator.php
│   └── PaymentProcessor.php
├── Notifications/
│   ├── NotificationService.php
│   ├── EmailChannel.php
│   └── SmsChannel.php
└── PdfGenerator.php
```

Namespaces follow directory structure:

```php
namespace App\Libraries\Billing;

class BillingService
{
    // ...
}
```

## Key Differences from CodeIgniter 3

- NO `$this->load->library('name')` syntax
- Instantiate directly: `new \App\Libraries\Name()`
- Use `service('name')` for shared instances
- Use `model('Name')` or `new \App\Models\Name()` for models
- Libraries are fully namespaced (PSR-4)
- Constructor DI is supported but manual instantiation is standard
