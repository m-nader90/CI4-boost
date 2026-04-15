<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\MCP\Tools;



class ReadLogs implements ToolInterface
{
    public function name(): string
    {
        return 'read_logs';
    }

    public function description(): string
    {
        return 'Read the last N log entries from the application log files.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Number of log entries to return (default: 50).',
                    'default' => 50,
                ],
                'level' => [
                    'type' => 'string',
                    'description' => 'Filter by log level: debug, info, notice, warning, error, critical. Default: all.',
                    'enum' => ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'all'],
                    'default' => 'all',
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
        $limit = min((int) ($arguments['limit'] ?? 50), 500);
        $level = strtolower($arguments['level'] ?? 'all');

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
        $entries = $this->parseLogEntries($content);

        if ($level !== 'all') {
            $entries = array_filter($entries, fn ($entry) => strtolower($entry['level'] ?? '') === $level);
        }

        $entries = array_slice(array_reverse(array_values($entries)), 0, $limit);

        if (empty($entries)) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'No log entries found.',
                    ],
                ],
            ];
        }

        $output = array_map(fn ($entry) => sprintf(
            '[%s] [%s] %s',
            $entry['date'] ?? '',
            $entry['level'] ?? '',
            $entry['message'] ?? ''
        ), $entries);

        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => implode("\n", $output),
                ],
            ],
        ];
    }

    protected function parseLogEntries(string $content): array
    {
        $entries = [];

        if (preg_match_all('/\[(.+?)\]\s+\[(.+?)\]\s+(.+?)(?=\[\d{4}-|\z)/s', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $entries[] = [
                    'date' => $match[1] ?? '',
                    'level' => $match[2] ?? '',
                    'message' => trim($match[3] ?? ''),
                ];
            }
        }

        return $entries;
    }
}
