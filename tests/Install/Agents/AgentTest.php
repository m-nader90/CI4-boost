<?php

declare(strict_types=1);

namespace Tests\Install\Agents;

use CodeIgniter\Boost\Install\Agents\Agent;
use CodeIgniter\Boost\Install\Agents\ClaudeCode;
use CodeIgniter\Boost\Install\Agents\Cursor;
use CodeIgniter\Boost\Install\Agents\ClaudeDesktop;
use CodeIgniter\Boost\Install\Agents\VsCodeCopilot;
use CodeIgniter\Boost\Install\Agents\KiloCode;
use CodeIgniter\Boost\Config\Boost;
use PHPUnit\Framework\TestCase;

class AgentTest extends TestCase
{
    protected Boost $config;

    protected function setUp(): void
    {
        $this->config = new Boost();
    }

    public static function agentProvider(): array
    {
        $config = new Boost();

        return [
            'claude-code' => [new ClaudeCode($config)],
            'cursor' => [new Cursor($config)],
            'claude-desktop' => [new ClaudeDesktop($config)],
            'vscode-copilot' => [new VsCodeCopilot($config)],
            'kilo-code' => [new KiloCode($config)],
        ];
    }

    #[DataProvider('agentProvider')]
    public function testAgentHasName(Agent $agent): void
    {
        $this->assertIsString($agent->name());
        $this->assertNotEmpty($agent->name());
    }

    #[DataProvider('agentProvider')]
    public function testAgentHasLabel(Agent $agent): void
    {
        $this->assertIsString($agent->label());
        $this->assertNotEmpty($agent->label());
    }

    #[DataProvider('agentProvider')]
    public function testAgentHasDescription(Agent $agent): void
    {
        $this->assertIsString($agent->description());
        $this->assertNotEmpty($agent->description());
    }

    public function testKiloCodeSupportsAllFeatures(): void
    {
        $agent = new KiloCode($this->config);

        $this->assertTrue($agent->supportsGuidelines());
        $this->assertTrue($agent->supportsSkills());
        $this->assertTrue($agent->supportsMcp());
    }

    public function testClaudeCodeSupportsAllFeatures(): void
    {
        $agent = new ClaudeCode($this->config);

        $this->assertTrue($agent->supportsGuidelines());
        $this->assertTrue($agent->supportsSkills());
        $this->assertTrue($agent->supportsMcp());
    }

    public function testCursorSupportsAllFeatures(): void
    {
        $agent = new Cursor($this->config);

        $this->assertTrue($agent->supportsGuidelines());
        $this->assertTrue($agent->supportsSkills());
        $this->assertTrue($agent->supportsMcp());
    }

    public function testClaudeDesktopOnlySupportsMcp(): void
    {
        $agent = new ClaudeDesktop($this->config);

        $this->assertFalse($agent->supportsGuidelines());
        $this->assertFalse($agent->supportsSkills());
        $this->assertTrue($agent->supportsMcp());
    }

    public function testKiloCodeNameIsCorrect(): void
    {
        $agent = new KiloCode($this->config);

        $this->assertSame('kilo-code', $agent->name());
        $this->assertSame('Kilo Code', $agent->label());
    }
}