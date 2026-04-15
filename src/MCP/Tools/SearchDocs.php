<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\MCP\Tools;

use CodeIgniter\Boost\BoostManager;


class SearchDocs implements ToolInterface
{
    public function name(): string
    {
        return 'search_docs';
    }

    public function description(): string
    {
        return 'Query the CodeIgniter 4 documentation to retrieve information about framework features and packages.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'The search query for documentation.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of results (default: 5).',
                    'default' => 5,
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function definition(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'inputSchema' => $this->parameters(),
        ];
    }

    public function execute(array $arguments = []): array
    {
        $query = trim($arguments['query'] ?? '');
        $limit = min((int) ($arguments['limit'] ?? 5), 20);

        if ($query === '') {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Query parameter is required.',
                    ],
                ],
                'isError' => true,
            ];
        }

        $config = BoostManager::instance()->config();
        $apiUrl = $config->docsApiUrl;

        $client = \Config\Services::curlrequest([
            'timeout' => 10,
        ]);

        try {
            $response = $client->post($apiUrl, [
                'json' => [
                    'query' => $query,
                    'limit' => $limit,
                    'packages' => $config->docsApiPackages,
                ],
            ]);

            $body = $response->getBody();

            if ($response->getStatusCode() === 200 && ! empty($body)) {
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $body,
                        ],
                    ],
                ];
            }

            $localResults = $this->searchLocalDocs($query, $limit);

            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $localResults,
                    ],
                ],
            ];
        } catch (\Throwable) {
            $localResults = $this->searchLocalDocs($query, $limit);

            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $localResults,
                    ],
                ],
            ];
        }
    }

    protected function searchLocalDocs(string $query, int $limit): string
    {
        $docs = [
            'routing' => 'CodeIgniter 4 Routing: Use app/Config/Routes.php. Routes are defined using $routes->get(), $routes->post(), $routes->put(), $routes->delete(), $routes->match(), $routes->add(), $routes->resource(), $routes->presenter(). Filters can be applied via ->filter(). Route groups via $routes->group(). Named routes via ->name().',
            'controllers' => 'CodeIgniter 4 Controllers: Extend BaseController. Place in app/Controllers. Use namespace App\Controllers. Access request via $this->request and response via $this->response. Method naming: index(), show(), create(), update(), delete(). Use $this->request->getVar(), $this->request->getJSON(), $this->request->getPost().',
            'models' => 'CodeIgniter 4 Models: Extend CodeIgniter\Model. Place in app/Models. Use namespace App\Models. Built-in methods: find(), findAll(), where(), insert(), update(), delete(). Use $allowedFields for mass assignment. Use $validationRules for auto-validation. Use $useTimestamps, $createdField, $updatedField.',
            'database' => 'CodeIgniter 4 Database: Config in app/Config/Database.php. Query Builder: $db->table(\'tablename\')->select()->get()->getResult(). Raw queries: $db->query(). Migrations: php spark make:migration, php spark migrate. Seeding: php spark make:seeder, php spark db:seed.',
            'validation' => 'CodeIgniter 4 Validation: Use $this->validate($rules) in controllers. Rule sets in app/Config/Validation.php. Custom rules via $rules array or Validation library. Available rules: required, min_length, max_length, valid_email, is_unique, matches, regex_match, etc.',
            'views' => 'CodeIgniter 4 Views: Place in app/Views. Return view() from controller. Use PHP templating. Use <?= esc($var) ?> for escaping. Layouts via $this->extend(). Sections via $this->section() and $this->endSection().',
            'middleware' => 'CodeIgniter 4 Middleware: Place in app/Filters. Register in app/Config/Filters.php. Implement CodeIgniter\Filters\FilterInterface with before() and after() methods. Apply to routes: $routes->get(\'/\', \'Home::index\')->filter(\'auth\').',
            'entities' => 'CodeIgniter 4 Entities: Extend CodeIgniter\Entity. Use with models by setting $returnType = \'App\\Entities\\MyEntity\'. Define accessors with getColumnName() methods and mutators with setColumnName(). Use cast features for automatic type casting.',
            'helpers' => 'CodeIgniter 4 Helpers: Load via helper(\'url\'), helper(\'form\'), etc. Common helpers: url_helper, form_helper, html_helper, text_helper, date_helper, file_helper, array_helper, security_helper. Auto-load in app/Config/Autoload.php.',
            'sessions' => 'CodeIgniter 4 Sessions: Config in app/Config/App.php ($sessionDriver, $sessionCookieName, $sessionExpiration). Access via $session = session(); $session->set(\'key\', \'value\'); $session->get(\'key\'); $session->has(\'key\'); $session->remove(\'key\'); $session->destroy();',
            'cli' => 'CodeIgniter 4 CLI Commands: php spark list to see commands. Create with php spark make:command. Extend CodeIgniter\\CLI\\BaseCommand. Set $group, $name. Implement run() method. Use CLI::write(), CLI::error(), CLI::info(), CLI::newLine(), CLI::prompt().',
        ];

        $results = [];
        $queryLower = strtolower($query);

        foreach ($docs as $topic => $content) {
            $score = 0;
            $topicLower = strtolower($topic);
            $contentLower = strtolower($content);

            if (str_contains($topicLower, $queryLower)) {
                $score += 10;
            }

            $words = explode(' ', $queryLower);
            foreach ($words as $word) {
                if (str_contains($topicLower, $word)) {
                    $score += 5;
                }
                if (str_contains($contentLower, $word)) {
                    $score += 2;
                }
            }

            if ($score > 0) {
                $results[] = [
                    'topic' => $topic,
                    'content' => $content,
                    'score' => $score,
                ];
            }
        }

        usort($results, fn ($a, $b) => $b['score'] <=> $a['score']);
        $results = array_slice($results, 0, $limit);

        if (empty($results)) {
            return "No local documentation found for query: {$query}\n\nFull docs available at: https://codeigniter.com/user_guide/";
        }

        $output = "Local documentation results for \"{$query}\":\n\n";
        foreach ($results as $result) {
            $output .= "## {$result['topic']}\n{$result['content']}\n\n";
        }

        $output .= "Full documentation: https://codeigniter.com/user_guide/";

        return $output;
    }
}
