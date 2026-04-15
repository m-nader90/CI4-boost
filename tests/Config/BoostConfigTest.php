<?php

declare(strict_types=1);

namespace Tests\Config;

use CodeIgniter\Boost\Config\Boost;
use CodeIgniter\Boost\Install\Agents\ClaudeCode;
use CodeIgniter\Boost\Install\Agents\Cursor;
use CodeIgniter\Boost\Install\Agents\ClaudeDesktop;
use CodeIgniter\Boost\Install\Agents\VsCodeCopilot;
use CodeIgniter\Boost\Install\Agents\KiloCode;
use PHPUnit\Framework\TestCase;

class BoostConfigTest extends TestCase
{
    protected Boost $config;

    protected function setUp(): void
    {
        $this->config = new Boost();
    }

    public function testDefaultAgentsAreRegistered(): void
    {
        $agents = $this->config->agents;

        $this->assertArrayHasKey('claude-code', $agents);
        $this->assertArrayHasKey('cursor', $agents);
        $this->assertArrayHasKey('claude-desktop', $agents);
        $this->assertArrayHasKey('vscode-copilot', $agents);
        $this->assertArrayHasKey('kilo-code', $agents);
        $this->assertCount(5, $agents);
    }

    public function testAgentClassesAreCorrect(): void
    {
        $this->assertSame(ClaudeCode::class, $this->config->agents['claude-code']);
        $this->assertSame(Cursor::class, $this->config->agents['cursor']);
        $this->assertSame(ClaudeDesktop::class, $this->config->agents['claude-desktop']);
        $this->assertSame(VsCodeCopilot::class, $this->config->agents['vscode-copilot']);
        $this->assertSame(KiloCode::class, $this->config->agents['kilo-code']);
    }

    public function testDefaultPathsAreSet(): void
    {
        $this->assertSame('.ai/guidelines', $this->config->guidelinesPath);
        $this->assertSame('.ai/skills', $this->config->skillsPath);
        $this->assertSame('.mcp.json', $this->config->mcpConfigFile);
    }

    public function testDocsApiUrlIsSet(): void
    {
        $this->assertNotEmpty($this->config->docsApiUrl);
        $this->assertStringContainsString('boost.codeigniter.com', $this->config->docsApiUrl);
    }

    public function testDocsApiPackagesIncludeCodeigniter4(): void
    {
        $this->assertContains('codeigniter4', $this->config->docsApiPackages);
    }
}