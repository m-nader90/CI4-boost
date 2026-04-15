<?php

declare(strict_types=1);

namespace Tests\MCP;

use CodeIgniter\Boost\MCP\Server;
use CodeIgniter\Boost\MCP\ToolRegistry;
use PHPUnit\Framework\TestCase;

class ServerTest extends TestCase
{
    protected Server $server;

    protected function setUp(): void
    {
        $this->server = new Server();
    }

    public function testServerCreation(): void
    {
        $this->assertInstanceOf(Server::class, $this->server);
    }

    public function testServerWithCustomRegistry(): void
    {
        $registry = new ToolRegistry();
        $server = new Server($registry);

        $this->assertInstanceOf(Server::class, $server);
    }

    public function testExtractMessageWithValidInput(): void
    {
        $json = json_encode(['jsonrpc' => '2.0', 'method' => 'ping', 'id' => 1]);
        $buffer = "Content-Length: " . strlen($json) . "\r\n\r\n" . $json;

        $reflection = new \ReflectionClass($this->server);
        $method = $reflection->getMethod('extractMessage');
        $method->setAccessible(true);

        $message = null;
        $remaining = '';
        $result = $method->invoke($this->server, $buffer, $message, $remaining);

        $this->assertTrue($result);
        $this->assertIsArray($message);
        $this->assertSame('ping', $message['method']);
        $this->assertSame(1, $message['id']);
    }

    public function testExtractMessageWithIncompleteInput(): void
    {
        $json = json_encode(['jsonrpc' => '2.0', 'method' => 'ping', 'id' => 1]);
        $buffer = "Content-Length: " . (strlen($json) + 100) . "\r\n\r\n" . $json;

        $reflection = new \ReflectionClass($this->server);
        $method = $reflection->getMethod('extractMessage');
        $method->setAccessible(true);

        $message = null;
        $remaining = '';
        $result = $method->invoke($this->server, $buffer, $message, $remaining);

        $this->assertFalse($result);
    }

    public function testExtractMessageWithNoHeader(): void
    {
        $buffer = 'just some random text without a header';

        $reflection = new \ReflectionClass($this->server);
        $method = $reflection->getMethod('extractMessage');
        $method->setAccessible(true);

        $message = null;
        $remaining = '';
        $result = $method->invoke($this->server, $buffer, $message, $remaining);

        $this->assertFalse($result);
    }

    public function testBuildInitializeResponse(): void
    {
        $reflection = new \ReflectionClass($this->server);
        $method = $reflection->getMethod('handleInitialize');
        $method->setAccessible(true);

        $capturedResponse = null;

        $stdout = fopen('php://temp', 'r+');

        $method->invoke($this->server, 1, ['clientInfo' => ['name' => 'test']], $stdout);

        rewind($stdout);
        $output = stream_get_contents($stdout);
        fclose($stdout);

        $this->assertNotEmpty($output);

        preg_match('/\{.*\}/s', $output, $matches);
        $decoded = json_decode($matches[0], true);

        $this->assertSame('2.0', $decoded['jsonrpc']);
        $this->assertSame(1, $decoded['id']);
        $this->assertArrayHasKey('protocolVersion', $decoded['result']);
        $this->assertArrayHasKey('capabilities', $decoded['result']);
        $this->assertArrayHasKey('serverInfo', $decoded['result']);
        $this->assertSame('ci4-boost', $decoded['result']['serverInfo']['name']);
    }

    public function testHandleToolListReturnsTools(): void
    {
        $reflection = new \ReflectionClass($this->server);
        $method = $reflection->getMethod('handleToolsList');
        $method->setAccessible(true);

        $stdout = fopen('php://temp', 'r+');

        $method->invoke($this->server, 2, [], $stdout);

        rewind($stdout);
        $output = stream_get_contents($stdout);
        fclose($stdout);

        preg_match('/\{.*\}/s', $output, $matches);
        $decoded = json_decode($matches[0], true);

        $this->assertSame(2, $decoded['id']);
        $this->assertArrayHasKey('tools', $decoded['result']);
        $this->assertNotEmpty($decoded['result']['tools']);
    }
}