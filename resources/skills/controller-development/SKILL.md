---
name: controller-development
description: Build and work with CodeIgniter 4 controllers, including RESTful patterns, filters, and response handling.
---

# Controller Development

## When to Use This Skill

- Creating new controllers for handling HTTP requests
- Modifying existing controller logic or adding endpoints
- Adding filters (auth, CORS, throttling) to controllers
- Structuring JSON API responses
- Handling file uploads in controllers
- Implementing redirect patterns

## Step-by-Step Controller Creation

Use the CLI to generate a controller:

```bash
php spark make:controller BlogController
php spark make:controller Api/UserController --restful
```

A basic controller extends `BaseController`:

```php
<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class BlogController extends BaseController
{
    public function index()
    {
        return view('blog/index');
    }

    public function show($id = null)
    {
        if ($id === null) {
            return redirect()->to('/blog')->with('error', 'Post not found');
        }

        return view('blog/show', ['id' => $id]);
    }

    public function create()
    {
        return view('blog/create');
    }

    public function store()
    {
        $validation = $this->validate([
            'title' => 'required|min_length[3]|max_length[255]',
            'body'  => 'required',
        ]);

        if (!$validation) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        return redirect()->to('/blog')->with('success', 'Post created');
    }
}
```

## RESTful Controller Patterns

For API-style controllers, extend `BaseController` and use `$this->request`:

```php
<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class UserController extends BaseController
{
    protected $format = 'json';

    private function respond($data, int $status = 200): ResponseInterface
    {
        return $this->response->setJSON([
            'success' => $status >= 200 && $status < 300,
            'data'    => $data,
        ])->setStatusCode($status);
    }

    public function index(): ResponseInterface
    {
        $users = model('UserModel')->findAll();
        return $this->respond($users);
    }

    public function show($id): ResponseInterface
    {
        $user = model('UserModel')->find($id);

        if (!$user) {
            return $this->respond(['message' => 'User not found'], 404);
        }

        return $this->respond($user);
    }

    public function create(): ResponseInterface
    {
        $data = $this->request->getJSON(true);

        $validation = $this->validate([
            'name'  => 'required|min_length[2]',
            'email' => 'required|valid_email|is_unique[users.email]',
        ]);

        if (!$validation) {
            return $this->respond($this->validator->getErrors(), 422);
        }

        $userId = model('UserModel')->insert($data);
        $user = model('UserModel')->find($userId);

        return $this->respond($user, 201);
    }

    public function update($id): ResponseInterface
    {
        $data = $this->request->getJSON(true);
        model('UserModel')->update($id, $data);
        $user = model('UserModel')->find($id);

        return $this->respond($user);
    }

    public function delete($id): ResponseInterface
    {
        model('UserModel')->delete($id);
        return $this->respond(null, 204);
    }
}
```

## Applying Filters to Controllers

Use the `$filters` property to restrict access:

```php
<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\Filters\CSRF;

class DashboardController extends BaseController
{
    protected $helpers = ['form'];

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
    }

    public function index()
    {
        if (!auth()->loggedIn()) {
            return redirect()->route('login')->with('error', 'Please log in');
        }

        return view('admin/dashboard');
    }
}
```

Filter via routes (preferred):

```php
$routes->group('admin', ['filter' => 'auth'], static function ($routes) {
    $routes->get('dashboard', 'Admin\DashboardController::index');
});
```

## JSON API Response Pattern

Create a reusable response trait:

```php
<?php

namespace App\Traits;

use CodeIgniter\HTTP\ResponseInterface;

trait ApiResponse
{
    private function jsonSuccess($data = null, string $message = '', int $status = 200): ResponseInterface
    {
        return $this->response->setJSON([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ])->setStatusCode($status);
    }

    private function jsonError(string $message = '', $errors = null, int $status = 400): ResponseInterface
    {
        return $this->response->setJSON([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors,
        ])->setStatusCode($status);
    }
}
```

## File Upload Handling

```php
<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class UploadController extends BaseController
{
    public function store()
    {
        $file = $this->request->getFile('avatar');

        if (!$file->isValid()) {
            return redirect()->back()->with('error', $file->getErrorString());
        }

        $validationRule = [
            'avatar' => [
                'label' => 'Avatar Image',
                'rules' => 'uploaded[avatar]|is_image[avatar]|max_size[avatar,1024]|ext_in[avatar,png,jpg,jpeg]',
            ],
        ];

        if (!$this->validate($validationRule)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $newName = $file->getRandomName();
        $file->move(WRITEPATH . 'uploads', $newName);

        return redirect()->to('/profile')->with('success', 'File uploaded');
    }
}
```

## Common Redirect Patterns

```php
return redirect()->to('/target');
return redirect()->route('named.route', ['id' => $id]);
return redirect()->back()->withInput();
return redirect()->back()->with('success', 'Saved!');
return redirect()->back()->with('error', 'Failed!');
return redirect()->to('/')->with('warning', 'Deprecated action');
```
