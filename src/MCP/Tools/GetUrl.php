<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\MCP\Tools;



class GetUrl implements ToolInterface
{
    public function name(): string
    {
        return 'get_url';
    }

    public function description(): string
    {
        return 'Convert relative path URIs to absolute URLs so agents generate valid URLs.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                    'description' => 'The relative URI path to convert to an absolute URL.',
                ],
            ],
            'required' => ['path'],
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
        $path = $arguments['path'] ?? '';

        if ($path === '') {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Path parameter is required.',
                    ],
                ],
                'isError' => true,
            ];
        }

        try {
            $url = base_url($path);

            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $url,
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'URL error: ' . $e->getMessage(),
                    ],
                ],
                'isError' => true,
            ];
        }
    }
}
