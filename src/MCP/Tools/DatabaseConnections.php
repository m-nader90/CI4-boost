<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\MCP\Tools;



class DatabaseConnections implements ToolInterface
{
    public function name(): string
    {
        return 'database_connections';
    }

    public function description(): string
    {
        return 'Inspect available database connections, including the default connection.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [],
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
        try {
            $dbConfig = config('Database');

            if ($dbConfig === null) {
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Database configuration not found.',
                        ],
                    ],
                    'isError' => true,
                ];
            }

            $connections = [];
            $groups = ['default', 'test'];

            foreach ($groups as $group) {
                $groupConfig = $dbConfig->$group ?? $dbConfig->{$dbConfig->defaultGroup};

                if ($groupConfig === null) {
                    continue;
                }

                $connections[$group] = [
                    'driver' => $groupConfig['DBDriver'] ?? 'MySQLi',
                    'database' => $groupConfig['database'] ?? '',
                    'hostname' => $groupConfig['hostname'] ?? 'localhost',
                    'port' => $groupConfig['port'] ?? 3306,
                    'username' => $groupConfig['username'] ?? '',
                    'charset' => $groupConfig['charset'] ?? 'utf8mb4',
                    'prefix' => $groupConfig['DBPrefix'] ?? '',
                ];
            }

            $result = [
                'default_group' => $dbConfig->defaultGroup,
                'connections' => $connections,
            ];

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
                        'text' => 'Error reading database connections: ' . $e->getMessage(),
                    ],
                ],
                'isError' => true,
            ];
        }
    }
}
