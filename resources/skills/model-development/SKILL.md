---
name: model-development
description: Build and work with CodeIgniter 4 models, entities, database queries, and data relationships.
---

# Model Development

## When to Use This Skill

- Creating new models for database table interaction
- Implementing CRUD operations and custom queries
- Working with entities and data casting
- Setting up model events (beforeInsert, afterInsert, etc.)
- Adding validation at the model level
- Implementing pagination for listing data

## Step-by-Step Model Creation

Use the CLI to generate a model:

```bash
php spark make:model UserModel
php spark make:model UserModel --entity
php spark make:model UserModel --migration
php spark make:model UserModel --entity --migration
```

## Basic Model with CRUD

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['name', 'email', 'password_hash', 'role'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = 'deleted_at';

    protected $validationRules    = [
        'name'  => 'required|min_length[2]|max_length[100]',
        'email' => 'required|valid_email|is_unique[users.email,id,{id}]',
    ];
    protected $validationMessages = [
        'email' => [
            'is_unique' => 'This email is already registered.',
        ],
    ];
    protected $skipValidation = false;
}
```

CRUD operations:

```php
$userModel = new \App\Models\UserModel();

$user = $userModel->find(1);
$users = $userModel->findAll();
$activeUsers = $userModel->where('status', 'active')->findAll();

$newId = $userModel->insert([
    'name'     => 'John Doe',
    'email'    => 'john@example.com',
    'password_hash' => password_hash('secret', PASSWORD_DEFAULT),
    'role'     => 'user',
]);

$userModel->update(1, ['name' => 'Jane Doe']);
$userModel->where('role', 'guest')->delete();
$userModel->delete(1);
$userModel->purgeDeleted();
```

## Query Builder Patterns

```php
$userModel = new \App\Models\UserModel();

$results = $userModel
    ->select('users.*, profiles.bio')
    ->join('profiles', 'profiles.user_id = users.id')
    ->where('users.role', 'admin')
    ->where('users.active', 1)
    ->where('users.created_at >=', '2025-01-01')
    ->like('users.name', 'john')
    ->groupBy('users.id')
    ->orderBy('users.created_at', 'DESC')
    ->limit(10)
    ->offset(20)
    ->findAll();

$userCount = $userModel->where('role', 'admin')->countAllResults();

$emails = $userModel->select('email')->where('active', 1)->findColumn('email');

$exists = $userModel->where('email', 'test@example.com')->first();
```

Subqueries with `whereIn` and model methods:

```php
$activeUserIds = model('ActivityModel')
    ->select('user_id')
    ->where('last_login >=', date('Y-m-d', strtotime('-30 days')))
    ->findColumn('user_id');

$recentUsers = $userModel->whereIn('id', $activeUserIds)->findAll();
```

## Entity Creation

```php
<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class User extends Entity
{
    protected $attributes = [
        'id'            => null,
        'name'          => null,
        'email'         => null,
        'password_hash' => null,
        'created_at'    => null,
    ];

    protected $casts = [
        'id'         => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function setPassword(string $password)
    {
        $this->attributes['password_hash'] = password_hash($password, PASSWORD_DEFAULT);

        return $this;
    }

    public function getPassword(): string
    {
        return '********';
    }

    public function getFullName(): string
    {
        return trim($this->attributes['name']);
    }

    public function getGravatarUrl(int $size = 80): string
    {
        return 'https://www.gravatar.com/avatar/' . md5(strtolower($this->email)) . "?s={$size}";
    }

    public function isAdmin(): bool
    {
        return $this->attributes['role'] === 'admin';
    }
}
```

Using entities in models:

```php
class UserModel extends Model
{
    protected $returnType = \App\Entities\User::class;
    protected $returnType = 'object';
}
```

## Model Events

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table         = 'users';
    protected $allowedFields = ['name', 'email', 'password_hash'];

    protected $beforeInsert = ['hashPassword', 'setRole'];
    protected $afterInsert  = ['clearCache'];
    protected $beforeUpdate = ['hashPassword'];
    protected $afterUpdate  = ['clearCache'];
    protected $afterDelete  = ['clearCache'];

    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $data['data']['password_hash'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
            unset($data['data']['password']);
        }

        return $data;
    }

    protected function setRole(array $data): array
    {
        if (!isset($data['data']['role'])) {
            $data['data']['role'] = 'user';
        }

        return $data;
    }

    protected function clearCache(array $data): array
    {
        cache()->delete('users_list');

        return $data;
    }
}
```

## Pagination

```php
<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class UserController extends BaseController
{
    public function index()
    {
        $userModel = model('UserModel');

        $data = [
            'users' => $userModel->paginate(15),
            'pager' => $userModel->pager,
        ];

        return view('users/index', $data);
    }
}
```

In the view:

```php
<?= $pager->links() ?>
<?= $pager->simpleLinks() ?>
```

Custom pager template:

```php
<?= $pager->links('default', 'bootstrap_full') ?>
<?= $pager->makeLinks($page, $perPage, $total, 'custom_pager') ?>
```
