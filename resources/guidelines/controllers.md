# CodeIgniter 4 Controller Guidelines

## Creating Controllers

All controllers extend `App\Controllers\BaseController` and live under the `App\Controllers` namespace.

```php
namespace App\Controllers;

use App\Controllers\BaseController;

class Blog extends BaseController
{
    public function index(): string
    {
        return view('blog/index');
    }

    public function show(int $id): string
    {
        return view('blog/show', ['id' => $id]);
    }
}
```

The `BaseController` (at `app/Controllers/BaseController.php`) initializes the request, response, and loader. Always extend it.

## Request and Response

Every controller has `$this->request` and `$this->response` available.

```php
namespace App\Controllers;

use App\Controllers\BaseController;

class Users extends BaseController
{
    public function profile(int $id)
    {
        // Reading request data
        $name = $this->request->getGet('name');
        $page = $this->request->getVar('page', 1);
        $email = $this->request->getPost('email');

        // JSON input
        $data = $this->request->getJSON(true);

        // Uploaded files
        $file = $this->request->getFile('avatar');

        // Server / header info
        $ip = $this->request->getIPAddress();
        $isAjax = $this->request->isAJAX();

        // Response methods
        return $this->response->setJSON([
            'status' => 'ok',
            'data' => $data
        ])->setStatusCode(200);

        // Redirect
        return redirect()->to('/dashboard')->with('success', 'Profile updated');

        // Download
        return $this->response->download('/path/to/file.pdf', null);
    }
}
```

## RESTful Method Naming

Follow CRUD conventions for controller methods:

```php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BlogModel;

class Blog extends BaseController
{
    public function index(): string
    {
        $model = new BlogModel();
        $data['posts'] = $model->orderBy('created_at', 'DESC')->paginate(10);
        $data['pager'] = $model->pager;
        return view('blog/index', $data);
    }

    public function show(int $id): string
    {
        $model = new BlogModel();
        $post = $model->find($id);
        if (!$post) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        return view('blog/show', ['post' => $post]);
    }

    public function new(): string
    {
        return view('blog/form');
    }

    public function create()
    {
        $model = new BlogModel();
        $data = $this->request->getPost();
        if ($model->insert($data)) {
            return redirect()->to('/blog')->with('success', 'Post created');
        }
        return redirect()->back()->withInput()->with('errors', $model->errors());
    }

    public function edit(int $id): string
    {
        $model = new BlogModel();
        $data['post'] = $model->find($id);
        return view('blog/form', $data);
    }

    public function update(int $id)
    {
        $model = new BlogModel();
        $data = $this->request->getPost();
        if ($model->update($id, $data)) {
            return redirect()->to("/blog/{$id}")->with('success', 'Post updated');
        }
        return redirect()->back()->withInput()->with('errors', $model->errors());
    }

    public function delete(int $id)
    {
        $model = new BlogModel();
        $model->delete($id);
        return redirect()->to('/blog')->with('success', 'Post deleted');
    }
}
```

## JSON API Responses

```php
public function apiList()
{
    $model = new BlogModel();
    $posts = $model->findAll();
    return $this->response->setJSON([
        'data' => $posts,
        'meta' => ['total' => count($posts)]
    ])->setStatusCode(200);
}

public function apiCreate()
{
    $data = $this->request->getJSON(true);
    $model = new BlogModel();
    $id = $model->insert($data);
    if ($model->errors()) {
        return $this->response
            ->setJSON(['errors' => $model->errors()])
            ->setStatusCode(422);
    }
    return $this->response
        ->setJSON(['id' => $id])
        ->setStatusCode(201);
}
```

## Filters on Controllers

Apply filters at the controller or method level using attributes or the Filters config:

```php
namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\IncomingRequest;

class Dashboard extends BaseController
{
    public function __construct()
    {
        // Alternative: register filters via Config/Filters.php
    }

    public function index(): string
    {
        return view('dashboard');
    }
}
```

Route-level filter assignment (preferred):

```php
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);
```

## Returning Views vs Echo

Always **return** view content, never echo:

```php
// Correct
return view('blog/index', $data);

// Wrong - never do this
echo view('blog/index', $data);
```

## Flash Data (Session Messages)

```php
// Set flash message
return redirect()->to('/blog')
    ->with('success', 'Post created successfully')
    ->with('error', 'Something went wrong');

// Read flash message in view
<?= session()->getFlashdata('success') ?>
```

## Pagination in Controllers

```php
public function index(): string
{
    $model = new BlogModel();
    $data['posts'] = $model->paginate(10, 'default');
    $data['pager'] = $model->pager;
    return view('blog/index', $data);
}
```

In the view:

```php
<?= $pager->links() ?>
```
