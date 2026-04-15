<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\MCP\Tools;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\ConnectionInterface;

class DatabaseQuery implements ToolInterface
{
    public function name(): string
    {
        return 'database_query';
    }

    public function description(): string
    {
        return 'Execute a SELECT query against the database. Only SELECT queries are allowed for safety.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'The SQL SELECT query to execute.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of rows to return (default: 100).',
                    'default' => 100,
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

        $normalized = strtoupper(preg_replace('/\s+/', ' ', $query));

        if (! str_starts_with($normalized, 'SELECT')) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Only SELECT queries are allowed.',
                    ],
                ],
                'isError' => true,
            ];
        }

        $limit = min((int) ($arguments['limit'] ?? 100), 1000);

        if (! str_contains($normalized, 'LIMIT')) {
            $query .= " LIMIT {$limit}";
        }

        try {
            $db = \Config\Database::connect();
            $result = $db->query($query)->getResultArray();

            if (empty($result)) {
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Query returned no results.',
                        ],
                    ],
                ];
            }

            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($result, JSON_PRETTY_PRINT),
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Query error: ' . $e->getMessage(),
                    ],
                ],
                'isError' => true,
            ];
        }
    }
}
