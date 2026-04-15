# CodeIgniter 4 CLI Commands Guidelines

## Creating Spark Commands

Generate a new command:

```bash
php spark make:command SendEmails
```

This creates `app/Commands/SendEmails.php`.

## Command Structure

```php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SendEmails extends BaseCommand
{
    protected $group       = 'email';
    protected $name        = 'emails:send';
    protected $description = 'Send pending emails from the queue';
    protected $usage       = 'emails:send [options]';
    protected $arguments   = [
        'type' => 'Type of emails to send (daily, weekly, all)',
    ];
    protected $options     = [
        '--limit'    => 'Maximum number of emails to send',
        '--dry-run'  => 'Preview emails without sending',
        '--verbose'  => 'Show detailed output',
    ];

    public function run(array $params)
    {
        $type = $params['type'] ?? CLI::getOption('type') ?? 'all';

        $limit = CLI::getOption('limit') ?? 100;
        $dryRun = CLI::getOption('dry-run') ?? false;
        $verbose = CLI::getOption('verbose') ?? false;

        CLI::write("Processing {$type} emails (limit: {$limit})", 'yellow');

        if ($dryRun) {
            CLI::write('Dry run mode - no emails will be sent', 'cyan');
        }

        $model = model(EmailQueueModel::class);
        $emails = $model->where('status', 'pending')
            ->limit((int) $limit)
            ->findAll();

        if (empty($emails)) {
            CLI::write('No pending emails found.', 'green');
            return;
        }

        CLI::newLine();
        CLI::write(sprintf('Found %d pending email(s).', count($emails)));

        $bar = CLI::progress(count($emails));

        $sent = 0;
        $failed = 0;

        foreach ($emails as $i => $email) {
            $bar->advance();

            if ($verbose) {
                CLI::write("Sending to: {$email['to']}", 'light_gray');
            }

            if (!$dryRun) {
                $emailService = service('email');
                $emailService->setTo($email['to']);
                $emailService->setSubject($email['subject']);
                $emailService->setMessage($email['body']);

                if ($emailService->send()) {
                    $model->update($email['id'], ['status' => 'sent']);
                    $sent++;
                } else {
                    $model->update($email['id'], [
                        'status'  => 'failed',
                        'error'   => implode(', ', $emailService->printDebugger()),
                    ]);
                    $failed++;
                    CLI::error("Failed to send to {$email['to']}");
                }
            } else {
                $sent++;
            }
        }

        CLI::newLine();
        CLI::write("Done! Sent: {$sent}, Failed: {$failed}", 'green');
    }
}
```

## CLI Output Methods

```php
use CodeIgniter\CLI\CLI;

CLI::write('Standard message');
CLI::write('Colored message', 'green');
CLI::write('Warning message', 'yellow');
CLI::write('Error message', 'red');

CLI::error('This is an error message');
CLI::info('This is an info message');
CLI::warn('This is a warning message');

CLI::newLine();
CLI::newLine(3);

CLI::beep();
CLI::beep(3);

CLI::clearScreen();
```

Available colors: `black`, `dark_gray`, `blue`, `green`, `cyan`, `red`, `purple`, `yellow`, `light_gray`, `white`, `default`.

Style modifiers: `bold`, `dim`, `underline`, `blink`, `background_*`.

```php
CLI::write('Bold text', 'white', 'red');
CLI::write('Underlined', 'underline');
CLI::write('Bold blue on white', 'bold', 'blue');
```

## User Input

```php
$name = CLI::prompt('Enter your name');
$name = CLI::prompt('Enter your name', ['John', 'Jane', 'Bob']);

$password = CLI::prompt('Enter password', null, true);

$choice = CLI::choose('Select environment', ['development', 'staging', 'production']);

if (!CLI::confirm('Are you sure you want to proceed?', true)) {
    CLI::write('Operation cancelled.');
    return;
}
```

## Tables

```php
$headers = ['ID', 'Title', 'Status', 'Created At'];
$rows = [
    [1, 'First Post', 'Published', '2024-01-15'],
    [2, 'Second Post', 'Draft', '2024-01-16'],
    [3, 'Third Post', 'Published', '2024-01-17'],
];

CLI::table($rows, $headers);
```

## Progress Bars

```php
$steps = 100;
CLI::showProgress($steps);

for ($i = 0; $i < $steps; $i++) {
    CLI::showProgress($current = $i + 1, $steps);
    usleep(10000);
}
CLI::showProgress(false);

// Or use progress() for iterators
$bar = CLI::progress(count($items));
foreach ($items as $item) {
    processItem($item);
    $bar->advance();
}
```

## Argument Handling

```php
class ExportData extends BaseCommand
{
    protected $group     = 'tools';
    protected $name      = 'data:export';
    protected $arguments = [
        'table'    => 'The database table to export',
        'format'   => 'Output format (csv, json, xml)',
    ];
    protected $options = [
        '--output'   => 'Output file path',
        '--where'    => 'WHERE clause filter',
        '--limit'    => 'Maximum rows to export',
    ];

    public function run(array $params)
    {
        $table = $params['table'] ?? null;
        $format = $params['format'] ?? 'csv';

        if (!$table) {
            CLI::error('Table name is required.');
            CLI::write('Usage: php spark data:export [table] [format] [options]');
            return;
        }

        $output = CLI::getOption('output');
        $where = CLI::getOption('where');
        $limit = (int) (CLI::getOption('limit') ?? 0);

        CLI::write("Exporting table: {$table} as {$format}");

        $db = \Config\Database::connect();
        $builder = $db->table($table);

        if ($where) {
            $builder->where($where);
        }
        if ($limit > 0) {
            $builder->limit($limit);
        }

        $rows = $builder->get()->getResultArray();

        if (empty($rows)) {
            CLI::warn("No data found in table: {$table}");
            return;
        }

        CLI::write(sprintf('Exported %d rows.', count($rows)), 'green');
    }
}
```

## Calling Commands from Other Commands

```php
class RefreshDatabase extends BaseCommand
{
    protected $group       = 'database';
    protected $name        = 'db:refresh';
    protected $description = 'Refresh the database with fresh migrations and seeds';

    public function run(array $params)
    {
        CLI::write('Rolling back migrations...', 'yellow');
        command('migrate:rollback');

        CLI::write('Running fresh migrations...', 'yellow');
        command('migrate');

        CLI::write('Running seeders...', 'yellow');
        command('db:seed DatabaseSeeder');

        CLI::write('Database refreshed successfully!', 'green');
    }
}
```

## Scheduling Commands

Use CRON or task scheduler to run CI4 commands:

```bash
# Linux crontab
* * * * * cd /path/to/project && php spark emails:queue >> /dev/null 2>&1
0 * * * * cd /path/to/project && php spark reports:hourly
0 0 * * * cd /path/to/project && php spark reports:daily
0 0 * * 0 cd /path/to/project && php spark reports:weekly
```

## Listing Commands

```bash
php spark list
php spark list --filter email
php spark help emails:send
```

## Common Built-in Spark Commands

```bash
php spark serve                    # Start development server
php spark routes:list              # List all routes
php spark migrate                  # Run migrations
php spark migrate:rollback         # Rollback last migration
php spark migrate:refresh          # Rollback and re-run
php spark migrate:status           # Migration status
php spark make:controller Name     # Create controller
php spark make:model Name          # Create model
php spark make:migration Name      # Create migration
php spark make:seeder Name         # Create seeder
php spark make:command Name        # Create command
php spark make:filter Name         # Create filter
php spark make:library Name        # Create library
php spark make:entity Name         # Create entity
php spark make:cell Name           # Create view cell
php spark make:middleware Name     # Create middleware
php spark make:scaffold Name       # Create scaffold
php spark db:seed Name             # Run seeder
php spark db:table                 # Show table info
php spark cache:clear              # Clear cache
php spark cache:info               # Cache info
php spark env                      # Show environment variables
php spark namespaces               # List PSR-4 namespaces
php spark test                     # Run tests
php spark test --filter testName   # Run specific test
php spark debug:bar                # Toggle debug toolbar
php spark publish                  # Publish assets
```

## Error Handling in Commands

```php
public function run(array $params)
{
    try {
        $result = $this->processData($params);
        CLI::write("Processed {$result} items.", 'green');
    } catch (\Exception $e) {
        CLI::error("Error: {$e->getMessage()}");
        CLI::error("File: {$e->getFile()}:{$e->getLine()}");
        log_message('error', $e->getMessage());
        $this->exitCode = 1;
    }
}
```
