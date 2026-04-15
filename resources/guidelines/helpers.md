# CodeIgniter 4 Helpers Guidelines

## Loading Helpers

Helpers are collections of standalone procedural functions grouped by category.

```php
// Load a single helper
helper('url');
helper('form');
helper('text');

// Load multiple helpers
helper(['url', 'form', 'html']);
```

Load helpers once, anywhere in your code (controllers, views, models). They are globally available after loading.

## Auto-Loading Helpers

Configure in `app/Config/Autoload.php` to load helpers automatically on every request:

```php
namespace App\Config;

use CodeIgniter\Config\BaseConfig;

class Autoload extends BaseConfig
{
    public $psr4 = [
        APP_NAMESPACE => APPPATH,
    ];

    public $helpers = ['url', 'form', 'html'];
}
```

## Common Built-in Helpers

### url_helper

```php
// Base URL
echo base_url();
echo base_url('css/style.css');
echo base_url('blog/post/1');

// Current URL
echo current_url();
echo current_url(true);

// Site URL (includes index page)
echo site_url('blog/show/1');

// URI segments
$segment = uri_string();
echo uri(1);
echo uri(2);

// Redirect
return redirect()->to('/dashboard');
return redirect()->route('blog.show', [$id]);
return redirect()->back()->with('success', 'Done');
return redirect()->away('https://external.com');

// Route generation
$url = route_to('blog.show', $id);
```

### form_helper

```php
// Open/close forms
echo form_open('blog/create', ['class' => 'form']);
echo form_open_multipart('upload/avatar', ['enctype' => 'multipart/form-data']);
echo form_close();

// CSRF
echo csrf_field();
echo csrf_token();
echo csrf_hash();

// Hidden inputs
echo form_hidden('user_id', 42);
echo form_hidden(['id' => 1, 'token' => 'abc']);

// Text inputs
echo form_input('username', set_value('username', $user->username), ['class' => 'input']);
echo form_password('password', '', ['class' => 'input']);
echo form_email('email', '', ['class' => 'input']);
echo form_number('age', '', ['min' => 0, 'max' => 120]);
echo form_textarea('body', set_value('body'), ['rows' => 10]);

// Dropdowns
$options = ['active' => 'Active', 'inactive' => 'Inactive'];
echo form_dropdown('status', $options, 'active', ['class' => 'select']);

// Multi-select
echo form_multiselect('roles[]', $roleOptions, $selectedRoles);

// Checkboxes and radio
echo form_checkbox('is_admin', 1, true, ['class' => 'checkbox']);
echo form_radio('gender', 'male', true);
echo form_radio('gender', 'female');

// Submit buttons
echo form_submit('submit', 'Save', ['class' => 'btn']);
echo form_reset('reset', 'Clear');

// Labels
echo form_label('Username', 'username', ['class' => 'label']);

// Repopulate old values
echo form_input('title', old('title'));

// Form values
$value = old('field_name', 'default_value');
```

### html_helper

```php
// Image tag
echo img('images/logo.png', true);
echo img(['src' => 'images/logo.png', 'alt' => 'Logo', 'class' => 'logo']);

// Link tags
echo link_tag('css/style.css');
echo link_tag(['href' => 'css/print.css', 'rel' => 'stylesheet', 'media' => 'print']);

// Script tags
echo script_tag('js/app.js');

// Meta tags
echo meta('description', 'My Site Description');
echo meta('Content-Type', 'text/html; charset=UTF-8', 'equiv');

// Heading
echo heading('My Title', 1);
echo heading('Subtitle', 2, ['class' => 'subtitle']);

// Unordered/ordered lists
$items = ['Item 1', 'Item 2', 'Item 3'];
echo ul($items);
echo ol($items, ['class' => 'numbered-list']);

// Nested lists
$nested = [
    'Item 1',
    [
        'Sub-item 1',
        'Sub-item 2',
    ],
    'Item 2',
];
echo ul($nested);

// Doctype
echo doctype('html5');

// Br and hr
echo br(2);
echo hr();
echo nbs(3);
```

### text_helper

```php
// Word limiter
echo word_limiter($longText, 30);

// Character limiter
echo character_limiter($longText, 100);

// Highlight words
echo highlight_phrase($text, 'search term', '<mark>', '</mark>');

// Convert to readable text
echo highlight_code($code);
echo word_censor($text, ['badword']);

// Random strings
echo random_string('alnum', 16);
echo random_string('numeric', 8);
echo random_string('alpha', 10);
echo random_string('hex', 32);

// Slug generation
echo url_title('My Blog Post Title', '-', true);

// Reduce multiple spaces
echo reduce_double_slashes('http://example.com//path');

// Strip tags safely
echo strip_image_tags('<img src="img.jpg" alt="test">');

// Bytes to human-readable
echo number_to_size(1024);
echo number_to_size(1048576, 1);

// Increment a string (Appends number to make unique)
echo increment_string('file', '_', 1);
```

### date_helper

```php
// Unix timestamp to human-readable
echo unix_to_human(time());
echo unix_to_human(time(), true, 'eu');

// Human-readable to Unix
echo human_to_unix('2024-01-15 14:30:00');

// Time zones
echo timezone_menu('America/New_York');
echo timezones();

// Timezone names
echo tz_format('UTC');

// Date formatting helper
echo standard_date('DATE_ISO8601', time());
```

### security_helper

```php
// XSS cleaning
$clean = sanitize_filename($input);
$clean = strip_image_tags($input);

// Token generation
$token = generate_token();
```

### cookie_helper

```php
// Set cookie
set_cookie([
    'name'     => 'remember_me',
    'value'    => $token,
    'expire'   => 86400 * 30,
    'domain'   => '.example.com',
    'path'     => '/',
    'secure'   => true,
    'httponly'  => true,
    'samesite'  => 'Lax',
]);

// Get cookie
$token = get_cookie('remember_me');

// Delete cookie
delete_cookie('remember_me');
```

## Creating Custom Helpers

Create a file in `app/Helpers/`:

```php
// app/Helpers/blog_helper.php
if (!function_exists('blog_excerpt')) {
    function blog_excerpt(string $text, int $length = 150): string
    {
        $text = strip_tags($text);
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . '...';
    }
}

if (!function_exists('reading_time')) {
    function reading_time(string $content): string
    {
        $wordCount = str_word_count(strip_tags($content));
        $minutes = max(1, (int) ceil($wordCount / 200));
        return $minutes . ' min read';
    }
}

if (!function_exists('format_number')) {
    function format_number(int $number): string
    {
        if ($number >= 1000000) {
            return number_format($number / 1000000, 1) . 'M';
        }
        if ($number >= 1000) {
            return number_format($number / 1000, 1) . 'K';
        }
        return (string) $number;
    }
}
```

Auto-load custom helpers in `app/Config/Autoload.php`:

```php
public $helpers = ['url', 'form', 'blog'];
```

Or load manually:

```php
helper('blog');
```

## Common Helper Functions (Available Globally)

These are always available without loading any helper:

```php
// Session
session();

// Cache
cache();

// Environment
env('key', 'default');

// Logger
log_message('error', 'Something went wrong');

// View
view('path', $data);

// Escape output
esc($string, 'html');

// Redirect
redirect();

// Service
service('email');

// Timing
$timer = timer();
timer('benchmark_name');
```
