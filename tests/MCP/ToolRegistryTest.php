<?php

declare(strict_types=1);

namespace Tests\MCP;

use CodeIgniter\Boost\MCP\ToolRegistry;
use CodeIgniter\Boost\MCP\Tools\ApplicationInfo;
use CodeIgniter\Boost\MCP\Tools\DatabaseConnections;
use CodeIgniter\Boost\MCP\Tools\DatabaseQuery;
use CodeIgniter\Boost\MCP\Tools\DatabaseSchema;
use CodeIgniter\Boost\MCP\Tools\GetUrl;
use CodeIgniter\Boost\MCP\Tools\LastError;
use CodeIgniter\Boost\MCP\Tools\ReadLogs;
use CodeIgniter\Boost\MCP\Tools\SearchDocs;
use CodeIgniter\Boost\MCP\Tools\ToolInterface;
use PHPUnit\Framework\TestCase;

class ToolRegistryTest extends TestCase
{
    protected ToolRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new ToolRegistry();
    }

    public function testAllDefaultToolsAreRegistered(): void
    {
        $tools = $this->registry->all();

        $this->assertCount(8, $tools);
    }

    public function testGetReturnsCorrectTool(): void
    {
        $tool = $this->registry->get('application_info');

        $this->assertInstanceOf(ApplicationInfo::class, $tool);
    }

    public function testGetReturnsNullForUnknownTool(): void
    {
        $tool = $this->registry->get('nonexistent_tool');

        $this->assertNull($tool);
    }

    public function testListForProtocolReturnsArray(): void
    {
        $list = $this->registry->listForProtocol();

        $this->assertIsArray($list);
        $this->assertCount(8, $list);
    }

    public function testToolDefinitionHasRequiredKeys(): void
    {
        $list = $this->registry->listForProtocol();

        foreach ($list as $definition) {
            $this->assertArrayHasKey('name', $definition);
            $this->assertArrayHasKey('description', $definition);
            $this->assertArrayHasKey('inputSchema', $definition);
        }
    }

    public function testCallUnknownToolReturnsError(): void
    {
        $result = $this->registry->call('nonexistent');

        $this->assertTrue($result['isError']);
        $this->assertStringContainsString('not found', $result['content'][0]['text']);
    }

    public function testRegisterCustomTool(): void
    {
        $customTool = new class implements ToolInterface {
            public function name(): string { return 'custom_test'; }
            public function description(): string { return 'A custom test tool.'; }
            public function parameters(): array { return ['type' => 'object']; }
            public function definition(): array { return ['name' => $this->name(), 'description' => $this->description(), 'inputSchema' => $this->parameters()]; }
            public function execute(array $arguments = []): array { return ['content' => [['type' => 'text', 'text' => 'custom result']]]; }
        };

        $this->registry->register($customTool);

        $this->assertInstanceOf(get_class($customTool), $this->registry->get('custom_test'));

        $result = $this->registry->call('custom_test');
        $this->assertSame('custom result', $result['content'][0]['text']);
    }
}