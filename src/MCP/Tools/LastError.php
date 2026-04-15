<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\MCP\Tools;



class LastError implements ToolInterface
{
    public function name(): string
    {
        return 'last_error';
    }

    public function description(): string
    {
        return 'Read the last error from the application\'s log files.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Number of most recent error entries to return (default: 1).',
                    'default' => 1,
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
        $logPath = WRITEPATH . 'logs/';
        $limit = (int) ($arguments['limit'] ?? 1);

        if (! is_dir($logPath)) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Log directory not found.',
                    ],
                ],
                'isError' => true,
            ];
        }

        $logFile = $logPath . 'log-' . date('Y-m-d') . '.log';

        if (! file_exists($logFile)) {
            $files = glob($logPath . 'log-*.log');

            if (empty($files)) {
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'No log files found.',
                        ],
                    ],
                ];
            }

            usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));
            $logFile = $files[0];
        }

        $content = file_get_contents($logFile);
        $lines = explode("\n", trim($content));

        $errorLines = [];
        foreach ($lines as $line) {
            $upper = strtoupper($line);
            if (str_contains($upper, 'ERROR') || str_contains($upper, 'CRITICAL') || str_contains($upper, 'EXCEPTION') || str_contains($upper, 'WARNING')) {
                $errorLines[] = $line;
            }
        }

        if (empty($errorLines)) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'No errors found in log file.',
                    ],
                ],
            ];
        }

        $errorLines = array_slice(array_reverse($errorLines), 0, $limit);

        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => implode("\n", $errorLines),
                ],
            ],
        ];
    }
}
