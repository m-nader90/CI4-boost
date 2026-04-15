<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\Install\Agents;

use function CodeIgniter\Boost\boost_resource_path;

class ClaudeCode extends Agent
{
    public function name(): string
    {
        return 'claude-code';
    }

    public function label(): string
    {
        return 'Claude Code';
    }

    public function description(): string
    {
        return 'Anthropic Claude Code CLI agent.';
    }

    public function supportsGuidelines(): bool
    {
        return true;
    }

    public function supportsSkills(): bool
    {
        return true;
    }

    public function supportsMcp(): bool
    {
        return true;
    }

    public function publishGuidelines(string $targetPath): void
    {
        $filepath = $targetPath . '/CLAUDE.md';

        $content = $this->buildGuidelinesContent();

        helper('filesystem');

        if (! is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        write_file($filepath, $content);
    }

    public function publishSkills(string $targetPath): void
    {
        $filepath = $targetPath . '/CLAUDE.md';

        if (file_exists($filepath)) {
            $content = file_get_contents($filepath);
        } else {
            $content = "# CI4 Boost - Claude Code Instructions\n\n";
        }

        $content .= "\n## Available Skills\n\n";
        $content .= "The following skills are available in `.ai/skills/`. Load them when working on relevant tasks:\n\n";
        $content .= "- **controller-development**: Building and working with CI4 controllers\n";
        $content .= "- **model-development**: Building and working with CI4 models and entities\n";
        $content .= "- **view-development**: Building and working with CI4 views\n";
        $content .= "- **database-development**: Database operations, migrations, and seeding\n";
        $content .= "- **validation-development**: Form validation and data validation\n";
        $content .= "- **routing-development**: Route definitions and route groups\n";
        $content .= "\nTo use a skill, read the SKILL.md file from `.ai/skills/{skill-name}/`.\n";

        helper('filesystem');
        write_file($filepath, $content);
    }

    public function publishMcpConfig(string $targetPath, array $command): void
    {
        $configFile = $targetPath . '/.mcp.json';

        $config = [
            'mcpServers' => [
                'ci4-boost' => [
                    'command' => $command[0],
                    'args' => array_slice($command, 1),
                ],
            ],
        ];

        helper('filesystem');
        write_file($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function buildGuidelinesContent(): string
    {
        $guidelinesPath = boost_resource_path('guidelines');
        $content = "# CI4 Boost - Claude Code Instructions\n\n";
        $content .= "This file contains AI guidelines for CodeIgniter 4 development.\n\n";

        if (is_dir($guidelinesPath)) {
            $files = glob($guidelinesPath . '/**/*.md', GLOB_BRACE);

            foreach ($files as $file) {
                $relativePath = str_replace($guidelinesPath . '/', '', $file);
                $content .= "## " . ucfirst(str_replace(['/', '.md'], [' > ', ''], $relativePath)) . "\n\n";
                $content .= file_get_contents($file) . "\n\n";
            }
        }

        return $content;
    }
}
