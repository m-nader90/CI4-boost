---
name: view-development
description: Build and work with CodeIgniter 4 views, layouts, sections, and view cells.
---

# View Development

## When to Use This Skill

- Creating or modifying view templates
- Setting up layout systems with extend/section
- Passing data from controllers to views
- Creating reusable view cells
- Building forms with CSRF protection
- Displaying flash messages and pagination

## View Creation

Views are PHP files in `app/Views/`. Render from a controller:

```php
return view('welcome');
return view('blog/post', ['title' => 'Hello', 'content' => 'Body text']);
return view('admin/dashboard', $data);
```

## Layout System

Use `extend()` and `section()` for a layout pattern.

**Layout:** `app/Views/layouts/main.php`

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'My App' ?></title>
    <link rel="stylesheet" href="/css/app.css">
    <?= $this->renderSection('styles') ?>
</head>
<body>
    <?= $this->renderSection('navbar') ?>

    <main class="container">
        <?= $this->renderSection('content') ?>
    </main>

    <script src="/js/app.js"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
```

**Content view:** `app/Views/blog/post.php`

```php
<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/css/blog.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<article>
    <h1><?= esc($title) ?></h1>
    <div class="post-body">
        <?= $this->include('partials/post_content') ?>
    </div>
</article>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="/js/blog.js"></script>
<?= $this->endSection() ?>
```

## Passing Data to Views

From controller:

```php
$data = [
    'posts'  => $postModel->paginate(10),
    'pager'  => $postModel->pager,
    'search' => $searchTerm,
];

return view('blog/list', $data);
```

Access in views:

```php
<?php foreach ($posts as $post): ?>
    <h2><?= esc($post->title) ?></h2>
    <p><?= esc($post->excerpt) ?></p>
<?php endforeach; ?>
```

Using view parser with `{}` syntax:

```php
return view('welcome_message', ['title' => 'Home'], ['saveData' => true]);
```

## View Cells

Cells are reusable mini-controllers that render small view components.

**Cell class:** `app/Cells/RecentPostsCell.php`

```php
<?php

namespace App\Cells;

use CodeIgniter\View\Cells\Cell;

class RecentPostsCell extends Cell
{
    public $limit = 5;

    public function render(): string
    {
        $posts = model('PostModel')
            ->orderBy('created_at', 'DESC')
            ->limit($this->limit)
            ->findAll();

        return $this->view('cells/recent_posts', ['posts' => $posts]);
    }

    public function featured(): string
    {
        $posts = model('PostModel')
            ->where('featured', 1)
            ->orderBy('created_at', 'DESC')
            ->limit($this->limit)
            ->findAll();

        return $this->view('cells/recent_posts', ['posts' => $posts]);
    }
}
```

**Cell view:** `app/Views/cells/recent_posts.php`

```php
<ul class="recent-posts">
<?php foreach ($posts as $post): ?>
    <li>
        <a href="/blog/<?= $post->slug ?>">
            <?= esc($post->title) ?>
        </a>
        <time><?= $post->created_at->humanize() ?></time>
    </li>
<?php endforeach; ?>
</ul>
```

Use in any view:

```php
<?= view_cell('RecentPostsCell') ?>
<?= view_cell('RecentPostsCell', ['limit' => 10]) ?>
<?= view_cell('RecentPostsCell::featured', ['limit' => 3]) ?>
```

## Form Helpers

Load form helper: `protected $helpers = ['form'];`

```php
<?= form_open('blog/store', ['class' => 'form-horizontal']) ?>
    <?= csrf_field() ?>

    <div class="form-group">
        <?= form_label('Title', 'title') ?>
        <?= form_input([
            'name'  => 'title',
            'id'    => 'title',
            'class' => 'form-control',
            'value' => old('title', $post->title ?? ''),
        ]) ?>
        <?= form_error('title', '<span class="text-danger">', '</span>') ?>
    </div>

    <div class="form-group">
        <?= form_label('Body', 'body') ?>
        <?= form_textarea([
            'name'  => 'body',
            'id'    => 'body',
            'class' => 'form-control',
            'rows'  => 8,
            'value' => old('body', $post->body ?? ''),
        ]) ?>
        <?= form_error('body', '<span class="text-danger">', '</span>') ?>
    </div>

    <div class="form-group">
        <?= form_label('Category', 'category_id') ?>
        <?= form_dropdown('category_id', $categories, old('category_id'), ['class' => 'form-control']) ?>
    </div>

    <div class="form-group">
        <?= form_label('Published', 'published') ?>
        <?= form_checkbox('published', 1, old('published', false)) ?>
    </div>

    <?= form_submit('submit', 'Save Post', ['class' => 'btn btn-primary']) ?>
<?= form_close() ?>
```

## Flash Messages

Set in controller:

```php
return redirect()->to('/dashboard')
    ->with('success', 'Profile updated successfully')
    ->with('error', 'Something went wrong')
    ->with('warning', 'Your session will expire soon')
    ->with('info', 'New features available');
```

Display in view:

```php
<?php if (session('success')): ?>
    <div class="alert alert-success" role="alert">
        <?= esc(session('success')) ?>
    </div>
<?php endif; ?>

<?php if (session('error')): ?>
    <div class="alert alert-danger" role="alert">
        <?= esc(session('error')) ?>
    </div>
<?php endif; ?>
```

Reusable partial: `app/Views/partials/flash_messages.php`

```php
<?php foreach (['success', 'error', 'warning', 'info'] as $type): ?>
    <?php if (session($type)): ?>
        <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?>" role="alert">
            <?= esc(session($type)) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
```

Include in layout:

```php
<?= $this->include('partials/flash_messages') ?>
```

## Pagination Views

```php
<?php if (isset($pager)): ?>
    <nav aria-label="Page navigation">
        <?= $pager->links() ?>
    </nav>
<?php endif; ?>
```

Custom pagination template in `app/Config/Pager.php`:

```php
public array $templates = [
    'bootstrap_full' => 'app\Views\Pager\bootstrap_full',
];
```
