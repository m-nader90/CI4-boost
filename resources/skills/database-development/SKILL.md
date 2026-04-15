---
name: database-development
description: Work with CodeIgniter 4 database operations including migrations, seeding, query builder, and raw queries.
---

# Database Development

## When to Use This Skill

- Creating or modifying database tables via migrations
- Seeding the database with sample or initial data
- Writing complex queries with the query builder
- Running raw SQL queries
- Working with transactions
- Setting up database connections and groups

## Migration Creation

Generate migrations:

```bash
php spark make:migration CreateUsersTable
php spark make:migration AddAvatarToUsers
php spark make:migration CreatePostsTable
```

Run and rollback:

```bash
php spark migrate
php spark migrate:rollback
php spark migrate:rollback -n 3
php spark migrate:refresh
php spark migrate:status
```

## Migration Structure

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => false,
                'unique'     => true,
            ],
            'password_hash' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'role' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'editor', 'user'],
                'default'    => 'user',
            ],
            'active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('email');
        $this->forge->addKey(['role', 'active']);
        $this->forge->createTable('users', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('users', true);
    }
}
```

Migration to modify an existing table:

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAvatarToUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'avatar' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'email',
            ],
            'bio' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'avatar',
            ],
        ]);

        $this->forge->dropColumn('users', 'role');
    }

    public function down(): void
    {
        $this->forge->addColumn('users', [
            'role' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'editor', 'user'],
                'default'    => 'user',
                'after'      => 'email',
            ],
        ]);

        $this->forge->dropColumn('users', ['avatar', 'bio']);
    }
}
```

Foreign key migration:

```php
$this->forge->addField([
    'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
    'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
    'title' => ['type' => 'VARCHAR', 'constraint' => '255'],
    'body' => ['type' => 'TEXT'],
    'created_at' => ['type' => 'DATETIME', 'null' => true],
]);

$this->forge->addKey('id', true);
$this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
$this->forge->createTable('posts');
```

## Seeder Creation

```bash
php spark make:seeder UserSeeder
php spark make:seeder DatabaseSeeder
```

```php
<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $userModel = model('UserModel');

        $userModel->insertBatch([
            [
                'name'     => 'Admin User',
                'email'    => 'admin@example.com',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'role'     => 'admin',
                'active'   => 1,
            ],
            [
                'name'     => 'Editor User',
                'email'    => 'editor@example.com',
                'password_hash' => password_hash('editor123', PASSWORD_DEFAULT),
                'role'     => 'editor',
                'active'   => 1,
            ],
            [
                'name'     => 'Regular User',
                'email'    => 'user@example.com',
                'password_hash' => password_hash('user123', PASSWORD_DEFAULT),
                'role'     => 'user',
                'active'   => 1,
            ],
        ]);

        $faker = \Faker\Factory::create();
        $bulkData = [];

        for ($i = 0; $i < 100; $i++) {
            $bulkData[] = [
                'name'     => $faker->name(),
                'email'    => $faker->unique()->email(),
                'password_hash' => password_hash('password', PASSWORD_DEFAULT),
                'role'     => 'user',
                'active'   => $faker->boolean(80) ? 1 : 0,
                'created_at' => $faker->dateTimeThisYear()->format('Y-m-d H:i:s'),
            ];
        }

        $userModel->insertBatch($bulkData);
    }
}
```

Main seeder:

```php
<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call('UserSeeder');
        $this->call('CategorySeeder');
        $this->call('PostSeeder');
    }
}
```

Run seeders:

```bash
php spark db:seed UserSeeder
php spark db:seed DatabaseSeeder
```

## Query Builder

Access the database directly:

```php
$db = \Config\Database::connect();
$builder = $db->table('users');

$results = $builder->select('id, name, email')
    ->where('active', 1)
    ->where('role !=', 'banned')
    ->like('name', 'john')
    ->orderBy('name', 'ASC')
    ->limit(10)
    ->get()
    ->getResultArray();
```

Joins:

```php
$builder = $db->table('posts');
$results = $builder->select('posts.*, users.name as author_name, categories.name as category_name')
    ->join('users', 'users.id = posts.user_id', 'left')
    ->join('categories', 'categories.id = posts.category_id', 'left')
    ->where('posts.status', 'published')
    ->orderBy('posts.created_at', 'DESC')
    ->get()
    ->getResultArray();
```

Grouping and aggregation:

```php
$results = $db->table('orders')
    ->select('user_id, COUNT(*) as order_count, SUM(total) as total_spent')
    ->where('status', 'completed')
    ->groupBy('user_id')
    ->having('total_spent >', 1000)
    ->orderBy('total_spent', 'DESC')
    ->get()
    ->getResultArray();
```

Subqueries:

```php
$subquery = $db->table('order_items')
    ->select('order_id, SUM(quantity * price) as total')
    ->groupBy('order_id');

$results = $db->table('orders')
    ->select('orders.*, sub.total')
    ->joinSubquery($subquery, 'sub', 'sub.order_id = orders.id')
    ->where('orders.status', 'completed')
    ->get()
    ->getResultArray();
```

## Transactions

```php
$db = \Config\Database::connect();

$db->transStart();

try {
    $db->table('users')->insert([
        'name'  => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $userId = $db->insertID();

    $db->table('profiles')->insert([
        'user_id' => $userId,
        'bio'     => 'Hello world',
    ]);

    $db->table('wallets')->insert([
        'user_id' => $userId,
        'balance' => 0,
    ]);

    $db->transComplete();
} catch (\Exception $e) {
    $db->transRollback();
    log_message('error', 'Transaction failed: ' . $e->getMessage());
}

if ($db->transStatus() === false) {
    log_message('error', 'Transaction failed');
}
```

## Raw Queries

```php
$db = \Config\Database::connect();

$results = $db->query("SELECT * FROM users WHERE active = ? AND role = ?", [1, 'admin'])->getResultArray();

$db->query("UPDATE users SET last_login = ? WHERE id = ?", [date('Y-m-d H:i:s'), $userId]);
```

## Database Testing

```php
<?php

namespace Tests\Models;

use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Models\UserModelTest;

class UserModelTest extends \CodeIgniter\Test\CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testFindUser(): void
    {
        $model = new \App\Models\UserModel();
        $user = $model->find(1);

        $this->assertIsArray($user);
        $this->assertSame('Admin User', $user['name']);
    }

    public function testInsertUser(): void
    {
        $model = new \App\Models\UserModel();
        $id = $model->insert([
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }
}
```
