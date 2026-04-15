# CodeIgniter 4 Entity Guidelines

## Creating Entities

Entities extend `CodeIgniter\Entity` and represent a single database row. They encapsulate business logic for individual records.

```php
namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Blog extends Entity
{
    protected $attributes = [
        'id'         => null,
        'title'      => null,
        'slug'       => null,
        'body'       => null,
        'author_id'  => null,
        'status'     => 'draft',
        'created_at' => null,
        'updated_at' => null,
    ];

    protected $casts = [
        'id'        => 'int',
        'author_id' => 'int',
        'status'    => 'string',
        'is_active' => 'boolean',
        'rating'    => 'float',
        'metadata'  => 'json-array',
        'created_at'=> 'datetime',
        'updated_at'=> 'datetime',
    ];
}
```

## Available Cast Types

| Cast Type | Description |
|-----------|-------------|
| `int` | Cast to integer |
| `float` | Cast to float |
| `double` | Cast to double |
| `string` | Cast to string |
| `bool` / `boolean` | Cast to boolean |
| `object` | JSON decode to object |
| `array` | JSON decode to array |
| `json-array` | JSON decode to associative array |
| `datetime` | Convert to DateTime object |
| `timestamp` | Convert to integer timestamp |
| `uri` | Convert to URI object |

## Custom Getters and Setters

Use `get{PropertyName}()` and `set{PropertyName}($value)` methods. The entity intercepts these automatically when accessing or mutating properties.

```php
namespace App\Entities;

use CodeIgniter\Entity\Entity;

class User extends Entity
{
    protected $attributes = [
        'first_name' => null,
        'last_name'  => null,
        'email'      => null,
        'password'   => null,
        'created_at' => null,
    ];

    protected $casts = [
        'id'         => 'int',
        'created_at' => 'datetime',
    ];

    public function getFullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function setPassword(string $password): self
    {
        $this->attributes['password'] = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->attributes['password'];
    }

    public function getGravatarUrl(int $size = 80): string
    {
        return 'https://www.gravatar.com/avatar/' . md5(strtolower($this->email)) . "?s={$size}";
    }

    public function setCreatedAt($value): self
    {
        $this->attributes['created_at'] = $value;
        return $this;
    }

    public function getCreatedAt(string $format = 'Y-m-d H:i:s'): ?string
    {
        if ($this->attributes['created_at'] instanceof \DateTime) {
            return $this->attributes['created_at']->format($format);
        }
        return $this->attributes['created_at'];
    }
}
```

## Accessing Entity Properties

```php
$user = new User([
    'first_name' => 'John',
    'last_name'  => 'Doe',
    'email'      => 'john@example.com',
]);

// Direct property access (triggers getter if exists)
echo $user->first_name;
echo $user->full_name;

// Setting properties (triggers setter if exists)
$user->password = 'secret123';

// Array access
echo $user['email'];
$user['last_name'] = 'Smith';

// Check if attribute exists
isset($user->email);

// Original / raw attributes
$raw = $user->toRawArray();

// Casted attributes
$array = $user->toArray();
```

## Date Mutators

With `datetime` cast, date fields automatically become `DateTime` objects:

```php
namespace App\Entities;

use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

class Post extends Entity
{
    protected $casts = [
        'published_at' => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function getPublishedAt(): ?string
    {
        $date = $this->attributes['published_at'];
        if ($date instanceof Time) {
            return $date->toLocalizedString('MMM d, yyyy');
        }
        return null;
    }

    public function isPublished(): bool
    {
        $date = $this->attributes['published_at'];
        return $date !== null && $date instanceof Time && $date->isPast();
    }
}
```

## Model Entity Integration

Models automatically convert results to entities when `$returnType` is set:

```php
namespace App\Models;

use CodeIgniter\Model;
use App\Entities\Blog;

class BlogModel extends Model
{
    protected $returnType    = Blog::class;
    protected $table         = 'blogs';
    protected $allowedFields = ['title', 'slug', 'body', 'author_id', 'status'];
    protected $useTimestamps = true;

    public function getPublished(): array
    {
        return $this->where('status', 'published')
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
```

Usage in controllers:

```php
$blog = $model->find($id);
// $blog is a Blog entity
echo $blog->title;
$blog->title = 'New Title';
$model->save($blog);
```

## Entity Validation

Entities can have their own validation:

```php
namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Blog extends Entity
{
    public function validate(string $scenario = 'default'): bool
    {
        $rules = [
            'default' => [
                'title' => 'required|min_length[3]',
                'body'  => 'required',
            ],
            'publish' => [
                'title' => 'required|min_length[3]',
                'body'  => 'required|min_length[50]',
                'slug'  => 'required|is_unique[blogs.slug,id,{id}]',
            ],
        ];

        return $this->validateData($this->attributes, $rules[$scenario]);
    }
}
```

## Mutating on Create/Update

```php
class Blog extends Entity
{
    protected $dates = ['created_at', 'updated_at'];

    public function fill(array $data): self
    {
        if (isset($data['title']) && !isset($data['slug'])) {
            $data['slug'] = url_title($data['title'], '-', true);
        }
        return parent::fill($data);
    }
}
```
