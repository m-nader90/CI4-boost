<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\Install\Agents;

use function CodeIgniter\Boost\boost_resource_path;

class KiloCode extends Agent
{
    public function name(): string
    {
        return 'kilo-code';
    }

    public function label(): string
    {
        return 'Kilo Code';
    }

    public function description(): string
    {
        return 'Kilo Code CLI agent for AI-powered software engineering tasks.';
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
        helper('filesystem');

        $filepath = $targetPath . '/AGENTS.md';

        $content = $this->buildGuidelinesContent();

        write_file($filepath, $content);
    }

    public function publishSkills(string $targetPath): void
    {
        helper('filesystem');

        $filepath = $targetPath . '/AGENTS.md';

        if (file_exists($filepath)) {
            $content = file_get_contents($filepath);
        } else {
            $content = "# CI4 Boost - Kilo Code Agent Instructions\n\n";
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

        write_file($filepath, $content);
    }

    public function publishMcpConfig(string $targetPath, array $command): void
    {
        helper('filesystem');

        $kiloDir = $targetPath . '/.kilo';

        if (! is_dir($kiloDir)) {
            mkdir($kiloDir, 0755, true);
        }

        $configFile = $kiloDir . '/kilomcp.json';

        $config = [
            'mcpServers' => [
                'ci4-boost' => [
                    'command' => $command[0],
                    'args' => array_slice($command, 1),
                ],
            ],
        ];

        write_file($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->publishKiloCommands($targetPath);
        $this->publishKiloAgentConfig($targetPath);
    }

    protected function buildGuidelinesContent(): string
    {
        $guidelinesPath = boost_resource_path('guidelines');
        $content = "# CI4 Boost - CodeIgniter 4 Agent Guidelines\n\n";
        $content .= "This file contains AI guidelines for CodeIgniter 4 development.\n\n";
        $content .= "When working with this CodeIgniter 4 application, follow these conventions and best practices.\n\n";

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

    protected function publishKiloCommands(string $targetPath): void
    {
        helper('filesystem');

        $commandDir = $targetPath . '/.kilo/command';

        if (! is_dir($commandDir)) {
            mkdir($commandDir, 0755, true);
        }

        $boostUpdateCommand = <<<'CMD'
---
description: Update CI4 Boost guidelines, skills, and MCP resources
---

Run `php spark boost:update` to update all CI4 Boost resources to their latest versions. This refreshes AI guidelines and agent skills to match the current installed packages.

If new packages were added since the last update, use `php spark boost:update --discover` to scan for new packages with Boost-compatible resources.
CMD;

        write_file($commandDir . '/boost-update.md', $boostUpdateCommand);

        $boostInstallCommand = <<<'CMD'
---
description: Reinstall CI4 Boost guidelines, skills, and MCP configuration
---

Run `php spark boost:install` to reinstall all CI4 Boost resources. This will regenerate agent guideline files, skill files, and MCP configuration for all configured AI agents.

After reinstalling, restart your AI agent to pick up the new configuration.
CMD;

        write_file($commandDir . '/boost-install.md', $boostInstallCommand);

        $ciMigrateCommand = <<<'CMD'
---
description: Run CodeIgniter 4 database migrations
---

Run the following command to execute all pending database migrations:

```
php spark migrate
```

To rollback the last migration group:

```
php spark migrate:rollback
```

To create a new migration:

```
php spark make:migration MigrationName
```
CMD;

        write_file($commandDir . '/ci-migrate.md', $ciMigrateCommand);

        $ciSeederCommand = <<<'CMD'
---
description: Run CodeIgniter 4 database seeders
---

Run the following command to execute all database seeders:

```
php spark db:seed DatabaseSeeder
```

To run a specific seeder:

```
php spark db:seed SpecificSeeder
```

To create a new seeder:

```
php spark make:seeder SeederName
```
CMD;

        write_file($commandDir . '/ci-seeder.md', $ciSeederCommand);
    }

    protected function publishKiloAgentConfig(string $targetPath): void
    {
        helper('filesystem');

        $agentDir = $targetPath . '/.kilo/agent';

        if (! is_dir($agentDir)) {
            mkdir($agentDir, 0755, true);
        }

        $agentMd = <<<'AGENT'
---
description: CodeIgniter 4 development agent with AI-boosted productivity
mode: primary
---
When working with this CodeIgniter 4 application:

1. Always follow CI4 conventions - controllers in `app/Controllers`, models in `app/Models`, views in `app/Views`
2. Use `php spark` CLI commands for code generation (make:controller, make:model, make:migration, etc.)
3. Use `$this->validate()` for form validation in controllers
4. Use the Query Builder (`$db->table()`) for database operations instead of raw SQL
5. Always escape output in views with `<?= esc($var) ?>`
6. Use CI4's built-in CSRF protection for all forms
7. Follow PSR-4 autoloading - namespace `App\*` maps to `app/*`
8. Use `route_to()` for reverse routing instead of hardcoding URLs
9. Use `base_url()` and `site_url()` from the URL helper
10. Register routes in `app/Config/Routes.php`

Refer to `AGENTS.md` for detailed guidelines on each CI4 component.
AGENT;

        write_file($agentDir . '/default.md', $agentMd);
    }
}
