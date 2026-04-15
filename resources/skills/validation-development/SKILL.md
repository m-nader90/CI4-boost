---
name: validation-development
description: Implement form and data validation in CodeIgniter 4 applications.
---

# Validation Development

## When to Use This Skill

- Validating form submissions in controllers
- Setting up model-level validation rules
- Creating custom validation rules
- Writing reusable validation rule sets
- Validating file uploads
- Handling AJAX validation requests

## Basic Validation in Controllers

```php
<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class PostController extends BaseController
{
    public function store()
    {
        $data = $this->request->getPost();

        $rules = [
            'title'   => 'required|min_length[3]|max_length[255]',
            'slug'    => 'required|alpha_dash|is_unique[posts.slug]',
            'content' => 'required|min_length[10]',
            'status'  => 'in_list[draft,published,archived]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        model('PostModel')->insert($data);

        return redirect()->to('/posts')->with('success', 'Post created');
    }
}
```

## Validation Rules Reference

Common rules:

| Rule | Description |
|------|-------------|
| `required` | Field must be present and not empty |
| `min_length[n]` | Minimum n characters |
| `max_length[n]` | Maximum n characters |
| `exact_length[n]` | Exactly n characters |
| `valid_email` | Valid email address |
| `alpha` | Alphabetic characters only |
| `alpha_numeric` | Alphanumeric only |
| `alpha_dash` | Alphanumeric, dashes, underscores |
| `numeric` | Numeric value |
| `integer` | Integer value |
| `decimal` | Decimal number |
| `is_natural` | Natural number (0, 1, 2, ...) |
| `is_natural_no_zero` | Natural number starting from 1 |
| `greater_than[n]` | Value must be greater than n |
| `less_than[n]` | Value must be less than n |
| `matches[field]` | Must match another field's value |
| `differs[field]` | Must differ from another field |
| `in_list[a,b,c]` | Value must be in the list |
| `is_unique[table.field]` | Unique value in database |
| `is_not_unique[table.field]` | Must exist in database |
| `valid_date` | Valid date string |
| `valid_date[Y-m-d]` | Valid date with specific format |
| `permit_empty` | Allows empty value |
| `regex_match[/pattern/]` | Must match regex pattern |

## Model-Level Validation

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class PostModel extends Model
{
    protected $table         = 'posts';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['title', 'slug', 'content', 'status', 'category_id', 'user_id'];

    protected $validationRules = [
        'title'   => 'required|min_length[3]|max_length[255]',
        'slug'    => 'required|alpha_dash|is_unique[posts.slug,id,{id}]',
        'content' => 'required|min_length[10]',
        'status'  => 'required|in_list[draft,published,archived]',
    ];

    protected $validationMessages = [
        'title' => [
            'required'   => 'The post title is required.',
            'min_length' => 'Title must be at least 3 characters.',
            'max_length' => 'Title cannot exceed 255 characters.',
        ],
        'slug' => [
            'required'   => 'The URL slug is required.',
            'is_unique'  => 'This slug is already in use. Please choose another.',
        ],
        'content' => [
            'required'   => 'Post content cannot be empty.',
            'min_length' => 'Content must be at least 10 characters long.',
        ],
    ];
}
```

## Validation Rule Sets

Define reusable rule sets in `app/Config/Validation.php`:

```php
<?php

namespace App\Config;

use CodeIgniter\Config\BaseConfig;

class Validation extends BaseConfig
{
    public array $ruleSets = [
        \CodeIgniter\Validation\Rules::class,
        \App\Validation\CustomRules::class,
    ];

    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    public array $registration = [
        'username' => 'required|alpha_numeric|min_length[3]|max_length[30]|is_unique[users.username]',
        'email'    => 'required|valid_email|is_unique[users.email]',
        'password' => 'required|min_length[8]|strong_password',
        'confirm'  => 'required|matches[password]',
    ];

    public array $registration_errors = [
        'username' => [
            'is_unique' => 'That username is taken.',
            'min_length' => 'Username must be at least 3 characters.',
        ],
        'email' => [
            'is_unique' => 'That email is already registered.',
        ],
        'password' => [
            'min_length' => 'Password must be at least 8 characters.',
        ],
        'confirm' => [
            'matches' => 'Passwords do not match.',
        ],
    ];

    public array $profile = [
        'name'  => 'required|min_length[2]|max_length[100]',
        'email' => 'required|valid_email|is_unique[users.email,id,{id}]',
        'bio'   => 'max_length[500]|permit_empty',
    ];
}
```

Use in controllers:

```php
$rules = service('validation')->rules['registration'];
$errors = service('validation')->registration_errors;

if (!$this->validate($rules, $errors)) {
    return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
}
```

Or load by set name:

```php
if (!$this->validate('registration')) {
    return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
}
```

## Custom Validation Rules

Create `app/Validation/CustomRules.php`:

```php
<?php

namespace App\Validation;

use CodeIgniter\Validation\Rules;

class CustomRules extends Rules
{
    public function strong_password(string $str, string $fields, array $data): bool
    {
        return (bool) preg_match(
            '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/',
            $str
        );
    }

    public function valid_phone(string $str): bool
    {
        return (bool) preg_match('/^[\d\s\-\+\(\)]{7,20}$/', $str);
    }

    public function valid_recaptcha(string $str, string $fields, array $data): bool
    {
        $response = service('curlrequest')->post('https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret'   => env('RECAPTCHA_SECRET'),
                'response' => $str,
                'remoteip' => service('request')->getIPAddress(),
            ],
        ]);

        return json_decode($response->getBody())->success ?? false;
    }

    public function valid_timezone(string $str): bool
    {
        return in_array($str, timezone_identifiers_list());
    }

    public function not_future_date(string $str): bool
    {
        return strtotime($str) <= time();
    }
}
```

Register in `Config/Validation.php`:

```php
public array $ruleSets = [
    \CodeIgniter\Validation\Rules::class,
    \App\Validation\CustomRules::class,
];
```

Use custom rules:

```php
$rules = [
    'password'       => 'required|strong_password',
    'phone'          => 'required|valid_phone',
    'g-recaptcha'    => 'required|valid_recaptcha',
    'timezone'       => 'required|valid_timezone',
    'birth_date'     => 'required|valid_date[Y-m-d]|not_future_date',
];
```

## File Validation

```php
$rules = [
    'avatar' => [
        'label' => 'Profile Picture',
        'rules' => 'uploaded[avatar]'
            . '|is_image[avatar]'
            . '|mime_in[avatar,image/png,image/jpeg,image/gif]'
            . '|max_size[avatar,2048]'
            . '|max_dims[avatar,800,800]',
    ],
    'document' => [
        'label' => 'Document',
        'rules' => 'uploaded[document]'
            . '|ext_in[document,pdf,doc,docx,xls,xlsx]'
            . '|max_size[document,5120]',
    ],
    'gallery.*' => [
        'label' => 'Gallery Images',
        'rules' => 'uploaded[gallery.*]'
            . '|is_image[gallery.*]'
            . '|max_size[gallery.*,1024]',
    ],
];
```

## AJAX Validation

Controller method for AJAX validation:

```php
public function validateField()
{
    $rules = $this->request->getVar('rules') ?? [];
    $field = $this->request->getVar('field');
    $value = $this->request->getVar('value');

    if (empty($rules) || empty($field)) {
        return $this->response->setJSON(['valid' => false, 'message' => 'Invalid request']);
    }

    $validation = service('validation');
    $validation->setRules([$field => $rules]);

    if ($validation->run([$field => $value])) {
        return $this->response->setJSON(['valid' => true]);
    }

    return $this->response->setJSON([
        'valid'   => false,
        'message' => $validation->getError($field),
    ]);
}
```

Returning validation errors as JSON:

```php
public function apiStore()
{
    $rules = [
        'title'   => 'required|min_length[3]',
        'content' => 'required',
    ];

    if (!$this->validate($rules)) {
        return $this->response->setJSON([
            'success' => false,
            'errors'  => $this->validator->getErrors(),
        ])->setStatusCode(422);
    }

    $id = model('PostModel')->insert($this->request->getJSON(true));

    return $this->response->setJSON([
        'success' => true,
        'data'    => model('PostModel')->find($id),
    ])->setStatusCode(201);
}
```

## Grouping Rules with Arrays

```php
$rules = [
    'user' => [
        'name'  => 'required|min_length[2]',
        'email' => 'required|valid_email',
    ],
    'address' => [
        'street'  => 'required',
        'city'    => 'required',
        'zip'     => 'required|numeric|exact_length[5]',
        'country' => 'required|in_list[US,CA,UK,DE]',
    ],
    'preferences' => [
        'newsletter' => 'in_list[0,1]',
        'theme'      => 'in_list[light,dark,auto]',
    ],
];
```
