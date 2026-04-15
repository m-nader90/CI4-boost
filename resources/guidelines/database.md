# CodeIgniter 4 Database Guidelines

## Database Configuration

Primary config file: `app/Config/Database.php`. Environment-specific values go in `.env`.

```env
database.default.hostname = localhost
database.default.database = ci4app
database.default.username = root
database.default.password = secret
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306
```

Multiple connection groups:

```env
database.readonly.hostname = read-replica.db.internal
database.readonly.database = ci4app
database.readonly.username = reader
database.readonly.password = secret
database.readonly.DBDriver = MySQLi

database.reporting.hostname = reports.db.internal
database.reporting.database = ci4app_reports
database.reporting.username = reporter
database.reporting.password = secret
database.reporting.DBDriver = MySQLi
```

Access connections:

```php
// Default connection
$db = \Config\Database::connect();

// Specific group
$dbReadonly = \Config\Database::connect('readonly');

// Via model
class ReportModel extends Model
{
    protected $DBGroup = 'reporting';
}
```

## Query Builder

```php
$db = \Config\Database::connect();

// Select
$results = $db->table('users')
    ->select('id, username, email')
    ->where('is_active', 1)
    ->orderBy('username', 'ASC')
    ->limit(10)
    ->get()
    ->getResultArray();

// Insert
$db->table('users')->insert([
    'username' => 'johndoe',
    'email'    => 'john@example.com',
    'password' => password_hash('secret', PASSWORD_DEFAULT),
]);
$insertId = $db->insertID();

// Update
$db->table('users')
    ->where('id', $userId)
    ->update(['last_login' => date('Y-m-d H:i:s')]);

// Delete
$db->table('users')->where('id', $userId)->delete();

// Join
$results = $db->table('posts')
    ->select('posts.*, users.username as author')
    ->join('users', 'users.id = posts.user_id', 'left')
    ->where('posts.status', 'published')
    ->get()
    ->getResultArray();

// Where variations
$db->table('users')->where('status', 'active');
$db->table('users')->where('age >=', 18);
$db->table('users')->whereIn('role', ['admin', 'editor']);
$db->table('users')->whereNotIn('status', ['banned', 'deleted']);
$db->table('users')->like('username', 'john');
$db->table('users')->orWhere('role', 'admin')->where('status', 'active');

// Grouping
$db->table('orders')
    ->select('user_id, COUNT(*) as total')
    ->groupBy('user_id')
    ->having('total >', 5)
    ->get()
    ->getResultArray();

// Aggregation
$total = $db->table('users')->countAll();
$filtered = $db->table('users')->where('is_active', 1)->countAllResults();
$max = $db->table('products')->selectMax('price')->get()->getRow()->price;
```

## Raw Queries

```php
$db = \Config\Database::connect();

// Simple query
$query = $db->query("SELECT * FROM users WHERE id = ?", [$userId]);
$results = $query->getResultArray();
$row = $query->getRowArray();

// Multiple bindings
$query = $db->query(
    "SELECT * FROM posts WHERE status = ? AND author_id = ? ORDER BY created_at DESC LIMIT ?",
    ['published', $authorId, 10]
);

// Named bindings
$query = $db->query(
    "SELECT * FROM users WHERE email = :email: AND status = :status:",
    ['email' => $email, 'status' => 'active']
);

// Insert with raw query
$db->query("INSERT INTO logs (message, level) VALUES (?, ?)", [$message, $level]);
```

## Transactions

```php
$db = \Config\Database::connect();

$db->transStart();
try {
    $db->table('orders')->insert(['user_id' => $userId, 'total' => $total]);
    $orderId = $db->insertID();
    $db->table('order_items')->insertBatch($items);
    $db->table('users')->where('id', $userId)->decrement('balance', $total);
    $db->transComplete();
} catch (\Exception $e) {
    $db->transRollback();
    log_message('error', 'Transaction failed: ' . $e->getMessage());
}

if ($db->transStatus() === false) {
    // Handle failure
}
```

Manual transaction control:

```php
$db->transException(true);
try {
    $db->table('accounts')->update(...);
    $db->table('transactions')->insert(...);
    $db->transCommit();
} catch (\Throwable $e) {
    $db->transRollback();
    throw $e;
}
```

## Migrations

Create and run migrations:

```bash
php spark make:migration CreateUsersTable
php spark make:migration AddAvatarToUsersTable
php spark make:migration CreatePostsTable --suffix
```

Migration structure:

```php
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
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'unique'     => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'unique'     => true,
            ],
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'is_active' => [
                'type'       => 'BOOLEAN',
                'default'    => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('users');
    }

    public function down(): void
    {
        $this->forge->dropTable('users');
    }
}
```

Adding columns:

```php
class AddAvatarToUsersTable extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'avatar' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'after'      => 'email',
                'null'       => true,
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', 'avatar');
    }
}
```

Run migrations:

```bash
php spark migrate
php spark migrate:rollback
php spark migrate:refresh
php spark migrate:status
php spark migrate -n 'App\\Database\\Migrations\\CreateUsersTable'
```

## Seeding

```bash
php spark make:seeder UserSeeder
```

```php
namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'username'  => 'admin',
                'email'     => 'admin@example.com',
                'password'  => password_hash('admin123', PASSWORD_DEFAULT),
                'is_active' => true,
            ],
            [
                'username'  => 'editor',
                'email'     => 'editor@example.com',
                'password'  => password_hash('editor123', PASSWORD_DEFAULT),
                'is_active' => true,
            ],
        ];

        $this->db->table('users')->insertBatch($data);
    }
}
```

```bash
php spark db:seed UserSeeder
php spark db:seed UserSeeder --force
```

Call seeders from other seeders:

```php
public function run(): void
{
    $this->call('UserSeeder');
    $this->call('PostSeeder');
}
```

## Query Logging

```php
// Enable query logging (development only)
$db = \Config\Database::connect();
$db->setDebugMode(true);

$queries = $db->getLastQuery()->getQuery();
```
