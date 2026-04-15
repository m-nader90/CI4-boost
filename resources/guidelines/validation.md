# CodeIgniter 4 Validation Guidelines

## Controller Validation

The primary way to validate data in CI4 is through `$this->validate()` in controllers.

```php
namespace App\Controllers;

use App\Controllers\BaseController;

class Blog extends BaseController
{
    public function create()
    {
        $data = $this->request->getPost();

        $valid = $this->validate([
            'title' => 'required|min_length[3]|max_length[255]',
            'body'  => 'required|min_length[10]',
            'slug'  => 'required|is_unique[blogs.slug]',
        ]);

        if (!$valid) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $model = model(BlogModel::class);
        $model->insert($data);
        return redirect()->to('/blog')->with('success', 'Post created');
    }
}
```

## Validation with Custom Messages

```php
$valid = $this->validate([
    'title' => 'required|min_length[3]|max_length[255]',
    'body'  => 'required',
], [
    'title' => [
        'required'   => 'Title is required.',
        'min_length' => 'Title must be at least {param} characters long.',
        'max_length' => 'Title cannot exceed {param} characters.',
    ],
    'body' => [
        'required' => 'Post body cannot be empty.',
    ],
]);
```

## Rule Sets in Config/Validation.php

Define reusable rule sets in `app/Config/Validation.php`:

```php
namespace App\Config;

use CodeIgniter\Config\BaseConfig;

class Validation extends BaseConfig
{
    public array $ruleSets = [
        \CodeIgniter\Validation\Rules::class,
        \CodeIgniter\Validation\FileRules::class,
        \CodeIgniter\Validation\CreditCardRules::class,
        \CodeIgniter\Validation\UrlRules::class,
    ];

    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    public array $blog = [
        'title' => 'required|min_length[3]|max_length[255]',
        'slug'  => 'required|max_length[255]|is_unique[blogs.slug,id,{id}]',
        'body'  => 'required|min_length[10]',
    ];

    public array $blog_messages = [
        'blog.title' => [
            'required'   => 'Title is required.',
            'min_length' => 'Title must be at least {param} characters.',
        ],
        'blog.slug' => [
            'is_unique' => 'URL slug is already in use.',
        ],
    ];

    public array $user_registration = [
        'username'              => 'required|min_length[3]|max_length[30]|alpha_numeric|is_unique[users.username]',
        'email'                 => 'required|valid_email|is_unique[users.email]',
        'password'              => 'required|min_length[8]',
        'password_confirm'      => 'required|matches[password]',
    ];
}
```

Using config rule sets in controllers:

```php
$valid = $this->validate('blog');
$valid = $this->validate('user_registration');
$valid = $this->validate('blog', $config->blog_messages);
```

## Available Validation Rules

### General Rules

| Rule | Description |
|------|-------------|
| `required` | Field must not be empty |
| `required_with[other]` | Required when other field is present |
| `required_without[other]` | Required when other field is absent |
| `isset` | Field must have a value (including 0 or false) |
| `permit_empty` | Skip validation if empty, validate if not |

### String Rules

| Rule | Description |
|------|-------------|
| `min_length[n]` | Minimum string length |
| `max_length[n]` | Maximum string length |
| `exact_length[n]` | Exact string length |
| `alpha` | Only alphabetical characters |
| `alpha_numeric` | Alphanumeric characters only |
| `alpha_numeric_space` | Alphanumeric and spaces |
| `alpha_dash` | Alphanumeric, dashes, and underscores |
| `numeric` | Numeric value |
| `integer` | Integer value |
| `decimal` | Decimal number |
| `is_natural` | Natural number (0, 1, 2, ...) |
| `is_natural_no_zero` | Natural number (1, 2, 3, ...) |
| `valid_email` | Valid email address |
| `valid_emails` | Comma-separated valid emails |
| `valid_url` | Valid URL |
| `valid_ip` | Valid IP address |
| `valid_base64` | Valid Base64 string |
| `regex_match[pattern]` | Matches regex pattern |

### Database Rules

| Rule | Description |
|------|-------------|
| `is_unique[table.field]` | Unique in database table |
| `is_unique[table.field,id,id_value]` | Unique except specific row |
| `is_not_unique[table.field]` | Must exist in database table |

### Comparison Rules

| Rule | Description |
|------|-------------|
| `matches[field]` | Must match another field's value |
| `differs[field]` | Must differ from another field |
| `greater_than[n]` | Greater than numeric value |
| `greater_than_equal_to[n]` | Greater than or equal to |
| `less_than[n]` | Less than |
| `less_than_equal_to[n]` | Less than or equal to |

### File Upload Rules

| Rule | Description |
|------|-------------|
| `uploaded[field]` | File must be uploaded |
| `max_size[field,size]` | Max file size in KB |
| `is_image[field]` | Must be an image |
| `mime_in[field,type1,type2]` | Allowed MIME types |
| `ext_in[field,ext1,ext2]` | Allowed file extensions |
| `max_dims[field,width,height]` | Maximum image dimensions |

## File Upload Validation

```php
$valid = $this->validate([
    'avatar' => [
        'uploaded[avatar]',
        'max_size[avatar,2048]',
        'is_image[avatar]',
        'mime_in[avatar,image/png,image/jpeg,image/gif]',
        'ext_in[avatar,png,jpg,gif]',
        'max_dims[avatar,1024,768]',
    ],
]);
```

## Using Validation Directly

```php
$validation = \Config\Services::validation();
$validation->setRules([
    'email'    => 'required|valid_email',
    'password' => 'required|min_length[8]',
]);

if ($validation->withRequest($this->request)->run()) {
    // Valid
} else {
    $errors = $validation->getErrors();
}

// Validate raw data
$validation->setRules([
    'amount' => 'required|numeric|greater_than[0]',
]);

if ($validation->run(['amount' => $amount])) {
    // Valid
}
```

## Custom Validation Rules

Create custom rules in `app/Validation/`:

```php
namespace App\Validation;

use CodeIgniter\Validation\Rules;

class CustomRules extends Rules
{
    public function valid_phone(string $str, string $fields, array $data): bool
    {
        return preg_match('/^[0-9\-\+\(\)\s]+$/', $str) === 1;
    }

    public function strong_password(string $str, string $fields, array $data): bool
    {
        return preg_match('/[A-Z]/', $str)
            && preg_match('/[a-z]/', $str)
            && preg_match('/[0-9]/', $str)
            && preg_match('/[^A-Za-z0-9]/', $str);
    }

    public function valid_recaptcha(string $str, string $fields, array $data): bool
    {
        $client = \Config\Services::curlrequest();
        $response = $client->post('https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret'   => env('RECAPTCHA_SECRET'),
                'response' => $str,
            ],
        ]);
        return json_decode($response->getBody())->success;
    }
}
```

Register in `app/Config/Validation.php`:

```php
public array $ruleSets = [
    \CodeIgniter\Validation\Rules::class,
    \App\Validation\CustomRules::class,
];
```

Usage:

```php
$this->validate([
    'phone'    => 'required|valid_phone',
    'password' => 'required|min_length[8]|strong_password',
]);
```

## Showing Errors in Views

```php
<?php if (session()->has('errors')): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach (session('errors') as $field => $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (session('errors.title')): ?>
    <span class="text-danger"><?= esc(session('errors.title')) ?></span>
<?php endif; ?>
```

Repopulating form values:

```php
<input type="text" name="title" value="<?= esc(old('title', $post['title'] ?? '')) ?>">
```
