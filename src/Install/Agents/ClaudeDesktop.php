<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\Install\Agents;

class ClaudeDesktop extends Agent
{
    public function name(): string
    {
        return 'claude-desktop';
    }

    public function label(): string
    {
        return 'Claude Desktop';
    }

    public function description(): string
    {
        return 'Claude Desktop app by Anthropic.';
    }

    public function supportsMcp(): bool
    {
        return true;
    }

    public function publishMcpConfig(string $targetPath, string $command): void
    {
        $configDir = getenv('APPDATA') !== false
            ? getenv('APPDATA') . '/Claude'
            : (getenv('HOME') !== false ? getenv('HOME') . '/.config/Claude' : '');

        if ($configDir === '') {
            return;
        }

        $configFile = $configDir . '/claude_desktop_config.json';

        $config = [
            'mcpServers' => [
                'ci4-boost' => [
                    'command' => $command[0],
                    'args' => array_slice($command, 1),
                ],
            ],
        ];

        if (file_exists($configFile)) {
            $existing = json_decode(file_get_contents($configFile), true);

            if (is_array($existing)) {
                $existing['mcpServers']['ci4-boost'] = $config['mcpServers']['ci4-boost'];
                $config = $existing;
            }
        }

        helper('filesystem');
        write_file($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
