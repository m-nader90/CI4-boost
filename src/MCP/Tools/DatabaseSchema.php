<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\MCP\Tools;



class DatabaseSchema implements ToolInterface
{
    public function name(): string
    {
        return 'database_schema';
    }

    public function description(): string
    {
        return 'Read the database schema including all tables and their columns with types.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'table' => [
                    'type' => 'string',
                    'description' => 'Optional specific table name to inspect. If omitted, returns all tables.',
                ],
            ],
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
            $db = \Config\Database::connect();

            if (! empty($arguments['table'])) {
                $schema = $this->getTableSchema($db, $arguments['table']);

                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => json_encode($schema, JSON_PRETTY_PRINT),
                        ],
                    ],
                ];
            }

            $tables = $db->listTables();

            $schema = [];
            foreach ($tables as $table) {
                $schema[$table] = $this->getTableSchema($db, $table);
            }

            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($schema, JSON_PRETTY_PRINT),
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Schema error: ' . $e->getMessage(),
                    ],
                ],
                'isError' => true,
            ];
        }
    }

    protected function getTableSchema($db, string $table): array
    {
        $fields = $db->getFieldData($table);

        $columns = [];
        foreach ($fields as $field) {
            $columns[] = [
                'name' => $field->name,
                'type' => $field->type,
                'max_length' => $field->max_length,
                'nullable' => (bool) $field->nullable,
                'default' => $field->default,
                'primary_key' => (bool) $field->primary_key,
            ];
        }

        $foreignKeys = [];
        if (method_exists($db, 'getForeignKeys')) {
            $fks = $db->getForeignKeys($table);
            foreach ($fks as $fk) {
                $foreignKeys[] = [
                    'column' => $fk->column_name ?? $fk->from,
                    'reference_table' => $fk->reference_table ?? $fk->table,
                    'reference_column' => $fk->reference_column ?? $fk->to,
                ];
            }
        }

        return [
            'columns' => $columns,
            'foreign_keys' => $foreignKeys,
        ];
    }
}
