<?php

declare(strict_types=1);

namespace Tests\MCP\Tools;

use CodeIgniter\Boost\MCP\Tools\ToolInterface;
use CodeIgniter\Boost\MCP\Tools\ApplicationInfo;
use CodeIgniter\Boost\MCP\Tools\DatabaseQuery;
use CodeIgniter\Boost\MCP\Tools\GetUrl;
use CodeIgniter\Boost\MCP\Tools\SearchDocs;
use CodeIgniter\Boost\MCP\Tools\LastError;
use CodeIgniter\Boost\MCP\Tools\ReadLogs;
use PHPUnit\Framework\TestCase;

class ToolContractTest extends TestCase
{
    public static function toolProvider(): array
    {
        return [
            'application_info' => [new ApplicationInfo()],
            'database_query' => [new DatabaseQuery()],
            'get_url' => [new GetUrl()],
            'search_docs' => [new SearchDocs()],
            'last_error' => [new LastError()],
            'read_logs' => [new ReadLogs()],
        ];
    }

    #[DataProvider('toolProvider')]
    public function testToolImplementsInterface(ToolInterface $tool): void
    {
        $this->assertInstanceOf(ToolInterface::class, $tool);
    }

    #[DataProvider('toolProvider')]
    public function testToolHasName(ToolInterface $tool): void
    {
        $this->assertIsString($tool->name());
        $this->assertNotEmpty($tool->name());
    }

    #[DataProvider('toolProvider')]
    public function testToolHasDescription(ToolInterface $tool): void
    {
        $this->assertIsString($tool->description());
        $this->assertNotEmpty($tool->description());
    }

    #[DataProvider('toolProvider')]
    public function testToolHasParameters(ToolInterface $tool): void
    {
        $params = $tool->parameters();
        $this->assertIsArray($params);
        $this->assertArrayHasKey('type', $params);
        $this->assertSame('object', $params['type']);
    }

    #[DataProvider('toolProvider')]
    public function testToolDefinitionIsComplete(ToolInterface $tool): void
    {
        $def = $tool->definition();

        $this->assertArrayHasKey('name', $def);
        $this->assertArrayHasKey('description', $def);
        $this->assertArrayHasKey('inputSchema', $def);
        $this->assertSame($tool->name(), $def['name']);
        $this->assertSame($tool->description(), $def['description']);
    }

    #[DataProvider('toolProvider')]
    public function testToolExecuteReturnsContentArray(ToolInterface $tool): void
    {
        $result = $tool->execute([]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertIsArray($result['content']);
        $this->assertNotEmpty($result['content']);
    }

    public function testDatabaseQueryRejectsNonSelect(): void
    {
        $tool = new DatabaseQuery();
        $result = $tool->execute(['query' => 'DELETE FROM users']);

        $this->assertTrue($result['isError']);
        $this->assertStringContainsString('Only SELECT', $result['content'][0]['text']);
    }

    public function testDatabaseQueryRequiresQuery(): void
    {
        $tool = new DatabaseQuery();
        $result = $tool->execute(['query' => '']);

        $this->assertTrue($result['isError']);
    }

    public function testGetUrlRequiresPath(): void
    {
        $tool = new GetUrl();
        $result = $tool->execute(['path' => '']);

        $this->assertTrue($result['isError']);
    }

    public function testSearchDocsRequiresQuery(): void
    {
        $tool = new SearchDocs();
        $result = $tool->execute(['query' => '']);

        $this->assertTrue($result['isError']);
    }
}