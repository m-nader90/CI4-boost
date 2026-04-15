<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\Install\Agents;

use function CodeIgniter\Boost\boost_resource_path;

class VsCodeCopilot extends Agent
{
    public function name(): string
    {
        return 'vscode-copilot';
    }

    public function label(): string
    {
        return 'GitHub Copilot (VS Code)';
    }

    public function description(): string
    {
        return 'GitHub Copilot AI assistant in Visual Studio Code.';
    }

    public function supportsGuidelines(): bool
    {
        return true;
    }

    public function supportsMcp(): bool
    {
        return true;
    }

    public function publishGuidelines(string $targetPath): void
    {
        $filepath = $targetPath . '/.github/copilot-instructions.md';

        helper('filesystem');

        if (! is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $content = $this->buildGuidelinesContent();

        write_file($filepath, $content);
    }

    public function publishMcpConfig(string $targetPath, string $command): void
    {
        $configFile = $targetPath . '/.vscode/mcp.json';

        helper('filesystem');

        if (! is_dir(dirname($configFile))) {
            mkdir(dirname($configFile), 0755, true);
        }

        $config = [
            'servers' => [
                'ci4-boost' => [
                    'type' => 'stdio',
                    'command' => $command[0],
                    'args' => array_slice($command, 1),
                ],
            ],
        ];

        write_file($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function buildGuidelinesContent(): string
    {
        $guidelinesPath = boost_resource_path('guidelines');
        $content = "# CodeIgniter 4 Development Guidelines\n\n";

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
