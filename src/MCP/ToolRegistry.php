<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\MCP;

use CodeIgniter\Boost\MCP\Tools\ApplicationInfo;
use CodeIgniter\Boost\MCP\Tools\DatabaseConnections;
use CodeIgniter\Boost\MCP\Tools\DatabaseQuery;
use CodeIgniter\Boost\MCP\Tools\DatabaseSchema;
use CodeIgniter\Boost\MCP\Tools\GetUrl;
use CodeIgniter\Boost\MCP\Tools\LastError;
use CodeIgniter\Boost\MCP\Tools\ReadLogs;
use CodeIgniter\Boost\MCP\Tools\SearchDocs;
use CodeIgniter\Boost\MCP\Tools\ToolInterface;

class ToolRegistry
{
    protected array $tools = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    protected function registerDefaults(): void
    {
        $this->register(new ApplicationInfo());
        $this->register(new DatabaseConnections());
        $this->register(new DatabaseQuery());
        $this->register(new DatabaseSchema());
        $this->register(new GetUrl());
        $this->register(new LastError());
        $this->register(new ReadLogs());
        $this->register(new SearchDocs());
    }

    public function register(ToolInterface $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    public function all(): array
    {
        return array_values($this->tools);
    }

    public function get(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    public function listForProtocol(): array
    {
        return array_map(fn (ToolInterface $tool) => $tool->definition(), $this->tools);
    }

    public function call(string $name, array $arguments = []): mixed
    {
        $tool = $this->get($name);

        if ($tool === null) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "Tool '{$name}' not found.",
                    ],
                ],
                'isError' => true,
            ];
        }

        return $tool->execute($arguments);
    }
}
