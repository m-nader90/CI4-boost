# CodeIgniter 4 Model Guidelines

## Creating Models

Models extend `CodeIgniter\Model` and live under `App\Models`.

```php
namespace App\Models;

use CodeIgniter\Model;

class BlogModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'blogs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = ['title', 'slug', 'body', 'author_id', 'status'];

    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = 'deleted_at';

    protected $validationRules      = [
        'title' => 'required|min_length[3]|max_length[255]',
        'slug'  => 'required|max_length[255]|is_unique[blogs.slug,id,{id}]',
        'body'  => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
```

## Returning Entities

Set `$returnType` to an entity class for automatic entity conversion:

```php
namespace App\Models;

use CodeIgniter\Model;
use App\Entities\Blog;

class BlogModel extends Model
{
    protected $returnType = Blog::class;
    protected $table      = 'blogs';
    protected $allowedFields = ['title', 'slug', 'body', 'author_id', 'status'];
    protected $useTimestamps = true;
}
```

## CRUD Operations

```php
$model = new BlogModel();

// Find single record by primary key
$blog = $model->find($id);

// Find by specific column
$blog = $model->where('slug', $slug)->first();

// Find all with conditions
$blogs = $model->where('status', 'published')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->findAll();

// Find with pagination
$blogs = $model->where('author_id', $userId)->paginate(10);

// Insert
$data = [
    'title' => 'My First Post',
    'slug'  => 'my-first-post',
    'body'  => 'Content here...',
];
$insertId = $model->insert($data);

// Insert with entity
$blog = new Blog(['title' => 'My Post', 'body' => 'Content']);
$model->insert($blog);

// Update
$model->update($id, ['title' => 'Updated Title']);

// Update multiple
$model->whereIn('status', ['draft'])->set(['status' => 'archived'])->update();

// Delete
$model->delete($id);

// Soft delete (requires useSoftDeletes = true)
$model->delete($id);

// Purge (permanent delete with soft deletes)
$model->delete($id, true);

// Count
$total = $model->where('status', 'published')->countAllResults();

// Check existence
$exists = $model->where('slug', $slug)->first() !== null;
```

## Validation in Models

Models validate on insert/update automatically when `$skipValidation` is false.

```php
// Insert with validation (automatic)
$id = $model->insert(['title' => '', 'body' => '']);
if ($id === false) {
    $errors = $model->errors();
}

// Bypass validation
$model->skipValidation(true)->insert($data);

// Custom validation messages
protected $validationMessages = [
    'title' => [
        'required'   => 'The title field is required.',
        'min_length' => 'Title must be at least 3 characters.',
    ],
    'slug' => [
        'is_unique' => 'This slug is already taken.',
    ],
];
```

## Query Builder in Models

Use `$this->builder()` to access the Query Builder for the model's table:

```php
class BlogModel extends Model
{
    protected $table = 'blogs';

    public function getRecentPosts(int $limit = 5): array
    {
        return $this->builder()
            ->select('blogs.*, users.username as author_name')
            ->join('users', 'users.id = blogs.author_id')
            ->where('blogs.status', 'published')
            ->orderBy('blogs.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getByTag(string $tag): array
    {
        return $this->builder('blogs')
            ->join('blog_tags', 'blog_tags.blog_id = blogs.id')
            ->join('tags', 'tags.id = blog_tags.tag_id')
            ->where('tags.slug', $tag)
            ->get()
            ->getResultArray();
    }
}
```

## Model Callbacks

```php
protected $beforeInsert = ['generateSlug'];
protected $afterInsert  = ['clearCache'];

protected function generateSlug(array $data): array
{
    if (isset($data['data']['title'])) {
        $data['data']['slug'] = url_title($data['data']['title'], '-', true);
    }
    return $data;
}

protected function clearCache(array $data): void
{
    cache()->delete('blog_list');
}
```

## Working with Different Database Groups

```php
protected $DBGroup = 'reporting';

// Or switch at runtime
$model->setDBGroup('readonly');
$logs = $model->findAll();
$model->setDBGroup('default');
```
