<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\MCP\Tools;

use CodeIgniter\Boost\BoostManager;

class ApplicationInfo implements ToolInterface
{
    public function name(): string
    {
        return 'application_info';
    }

    public function description(): string
    {
        return 'Read PHP & CodeIgniter 4 versions, environment, database info, list of installed packages with versions, and models.';
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
        $info = BoostManager::instance()->appInfo();

        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode($info, JSON_PRETTY_PRINT),
                ],
            ],
        ];
    }
}
