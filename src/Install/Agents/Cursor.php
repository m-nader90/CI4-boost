<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\Install\Agents;

use function CodeIgniter\Boost\boost_resource_path;

class Cursor extends Agent
{
    public function name(): string
    {
        return 'cursor';
    }

    public function label(): string
    {
        return 'Cursor';
    }

    public function description(): string
    {
        return 'Cursor IDE with AI-powered code editing.';
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
        $filepath = $targetPath . '/.cursor/rules/ci4-boost.md';

        helper('filesystem');

        if (! is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $content = $this->buildGuidelinesContent();

        write_file($filepath, $content);
    }

    public function publishSkills(string $targetPath): void
    {
        $filepath = $targetPath . '/.cursor/rules/ci4-boost-skills.md';

        helper('filesystem');

        if (! is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $content = "# CI4 Boost - Available Skills\n\n";
        $content .= "The following skills are available in `.ai/skills/`. Load them when working on relevant tasks:\n\n";
        $content .= "- **controller-development**: Building and working with CI4 controllers\n";
        $content .= "- **model-development**: Building and working with CI4 models and entities\n";
        $content .= "- **view-development**: Building and working with CI4 views\n";
        $content .= "- **database-development**: Database operations, migrations, and seeding\n";
        $content .= "- **validation-development**: Form validation and data validation\n";
        $content .= "- **routing-development**: Route definitions and route groups\n";
        $content .= "\nTo use a skill, read the SKILL.md file from `.ai/skills/{skill-name}/`.\n";

        write_file($filepath, $content);
    }

    public function publishMcpConfig(string $targetPath, string $command): void
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
        $content = "# CI4 Boost Guidelines\n\n";
        $content .= "You are working with a CodeIgniter 4 application. Follow these guidelines:\n\n";

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
