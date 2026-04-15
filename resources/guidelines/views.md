# CodeIgniter 4 View Guidelines

## View Location

Views are stored in `app/Views/` with a `.php` extension.

```
app/Views/
├── layouts/
│   ├── default.php
│   └── admin.php
├── blog/
│   ├── index.php
│   ├── show.php
│   └── form.php
├── errors/
│   └── html/
├── partials/
│   ├── header.php
│   ├── footer.php
│   └── sidebar.php
└── welcome_message.php
```

## Rendering Views

```php
// In controllers
return view('welcome_message');
return view('blog/index', $data);
return view('layouts/default', $data);
```

CI4 uses native PHP templating, NOT Blade.

## Output Escaping

Always escape output to prevent XSS:

```php
// Escape HTML entities
<?= esc($variable) ?>
<?= esc($user->name, 'html') ?>

// Escape for JavaScript
<?= esc($jsValue, 'js') ?>

// Escape for CSS
<?= esc($cssValue, 'css') ?>

// Escape for URL attributes
<?= esc($url, 'url') ?>

// Escape for HTML attributes
<?= esc($value, 'attr') ?>
```

## Layouts with extend()

```php
// app/Views/layouts/default.php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $this->renderSection('title') ?> - My App</title>
    <?= $this->renderSection('styles') ?>
</head>
<body>
    <header>
        <?= $this->include('partials/header') ?>
    </header>

    <main>
        <?= $this->renderSection('content') ?>
    </main>

    <footer>
        <?= $this->include('partials/footer') ?>
    </footer>

    <?= $this->renderSection('scripts') ?>
</body>
</html>
```

```php
// app/Views/blog/index.php
<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>
    Blog Posts
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
    <link rel="stylesheet" href="/css/blog.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <h1>Blog Posts</h1>

    <?php foreach ($posts as $post): ?>
        <article>
            <h2><?= esc($post['title']) ?></h2>
            <p><?= esc($post['excerpt']) ?></p>
            <a href="<?= route_to('blog.show', $post['id']) ?>">Read More</a>
        </article>
    <?php endforeach; ?>

    <?= $pager->links() ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
    <script src="/js/blog.js"></script>
<?= $this->endSection() ?>
```

## Sections

Sections are defined in layouts with `$this->renderSection('name')`. Default content can be provided:

```php
// app/Views/layouts/default.php
<title><?= $this->renderSection('title', 'My App Default Title') ?></title>

// Optional section (won't error if not defined)
<?= $this->renderSection('sidebar', '') ?>
```

Sections can be appended to instead of replaced:

```php
<?= $this->section('scripts') ?>
    <script src="/js/blog.js"></script>
<?= $this->appendSection() ?>
```

## Passing Data to Views

```php
// Associative array
$data = [
    'title' => 'Blog',
    'posts' => $posts,
    'pager' => $pager,
];
return view('blog/index', $data);

// Named parameters (CI4.3+)
return view('blog/index', ['title' => 'Blog', 'posts' => $posts]);

// Cached views
return view('blog/index', $data, ['cache' => 3600]);
```

## View Partials with include()

Use `$this->include()` for sub-views that share the parent's data:

```php
// In a layout or view
<?= $this->include('partials/sidebar') ?>
<?= $this->include('partials/pagination') ?>
```

`include()` shares all variables from the parent view scope. Pass additional data:

```php
<?= $this->include('partials/card', ['post' => $post]) ?>
```

## View Parser (Alternative Template Engine)

CI4 includes a lightweight template parser as an alternative to pure PHP:

```php
$parser = \CodeIgniter\Config\Services::parser();
return $parser->setData(['title' => 'Hello', 'body' => 'World'])
    ->render('blog/post');
```

Parser syntax uses `{variable}` notation:

```php
// app/Views/blog/post.php (parser view)
<h1>{title}</h1>
<p>{body}</p>

// Loops with parser
{posts}
    <h2>{title}</h2>
    <p>{body}</p>
{/posts}

// Conditional
{if status == 'published'}
    <span class="badge">Published</span>
{/if}
```

## View Cells

View Cells are classes that return HTML fragments, useful for reusable widget-style content.

```php
namespace App\Views\Cells;

use CodeIgniter\View\Cells\Cell;

class RecentPostsCell extends Cell
{
    public string $category = '';

    public function render(): string
    {
        $model = new \App\Models\BlogModel();
        $this->data['posts'] = $model->where('category', $this->category)
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->findAll();
        return view('cells/recent_posts', $this->data);
    }

    public function mount(string $category = ''): void
    {
        $this->category = $category;
    }
}
```

In views, use the `cell()` function:

```php
// Render a cell
<?= cell('RecentPosts') ?>

// Pass parameters
<?= cell('RecentPosts', ['category' => 'technology']) ?>
```

Generate cells via CLI:

```bash
php spark make:cell RecentPostsCell
```

## Common View Patterns

Forms with CSRF:

```php
<form method="post" action="<?= route_to('blog.create') ?>">
    <?= csrf_field() ?>
    <input type="text" name="title" value="<?= esc(old('title')) ?>">
    <button type="submit">Save</button>
</form>
```

Flash messages:

```php
<?php if (session()->has('success')): ?>
    <div class="alert alert-success">
        <?= esc(session('success')) ?>
    </div>
<?php endif; ?>

<?php if (session()->has('errors')): ?>
    <div class="alert alert-danger">
        <?php foreach (session('errors') as $error): ?>
            <p><?= esc($error) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
```

Pagination:

```php
<?= $pager->links('default', 'custom_view') ?>
<?= $pager->simpleLinks() ?>
```
