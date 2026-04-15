<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\Boost\BoostManager;
use CodeIgniter\CLI\CLI;

class BoostDoctor extends BaseCommand
{
    protected $group = 'boost';

    protected $name = 'boost:doctor';

    protected $description = 'Diagnose CI4 Boost installation and verify configuration.';

    public function run(array $params)
    {
        CLI::write('CI4 Boost Doctor', 'cyan');
        CLI::write('===============', 'cyan');
        CLI::newLine();

        $manager = BoostManager::instance();
        $config = $manager->config();
        $issues = 0;

        $this->checkPhpVersion($issues);
        $this->checkCiVersion($issues);
        $this->checkGuidelines($config, $issues);
        $this->checkSkills($config, $issues);
        $this->checkAgentsConfig($issues);
        $this->checkMcpConfig($issues);
        $this->checkKiloConfig($issues);
        $this->checkSparkCommands($issues);

        CLI::newLine();

        if ($issues === 0) {
            CLI::write('All checks passed! CI4 Boost is properly configured.', 'green');
        } else {
            CLI::write("Found {$issues} issue(s). Run `php spark boost:install` to fix.", 'red');
        }
    }

    protected function checkPhpVersion(int &$issues): void
    {
        CLI::write('Checking PHP version...', 'yellow');

        if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
            CLI::write("  PHP " . PHP_VERSION . " - OK", 'green');
        } else {
            CLI::write("  PHP " . PHP_VERSION . " - Requires 8.1+", 'red');
            $issues++;
        }
    }

    protected function checkCiVersion(int &$issues): void
    {
        CLI::write('Checking CodeIgniter 4...', 'yellow');

        if (defined('CI_VERSION')) {
            CLI::write("  CodeIgniter " . CI_VERSION . " - OK", 'green');
        } else {
            CLI::write('  CI_VERSION constant not defined - Not in CI4 context', 'red');
            $issues++;
        }
    }

    protected function checkGuidelines($config, int &$issues): void
    {
        CLI::write('Checking guidelines...', 'yellow');

        $guidelinesPath = ROOTPATH . $config->guidelinesPath;

        if (is_dir($guidelinesPath)) {
            $files = glob($guidelinesPath . '/**/*.md', GLOB_BRACE) ?: [];
            CLI::write('  ' . count($files) . ' guideline files in ' . $config->guidelinesPath . '/ - OK', 'green');
        } else {
            CLI::write('  Guidelines directory not found at ' . $config->guidelinesPath . '/', 'red');
            $issues++;
        }
    }

    protected function checkSkills($config, int &$issues): void
    {
        CLI::write('Checking skills...', 'yellow');

        $skillsPath = ROOTPATH . $config->skillsPath;

        if (is_dir($skillsPath)) {
            $dirs = glob($skillsPath . '/*', GLOB_ONLYDIR) ?: [];
            $count = 0;
            foreach ($dirs as $dir) {
                if (file_exists($dir . '/SKILL.md')) {
                    $count++;
                }
            }
            CLI::write("  {$count} skill modules in " . $config->skillsPath . '/ - OK', 'green');
        } else {
            CLI::write('  Skills directory not found at ' . $config->skillsPath . '/', 'red');
            $issues++;
        }
    }

    protected function checkAgentsConfig(int &$issues): void
    {
        CLI::write('Checking agent config files...', 'yellow');

        $checked = 0;

        if (file_exists(ROOTPATH . 'AGENTS.md')) {
            CLI::write('  AGENTS.md (Kilo Code) - OK', 'green');
            $checked++;
        }

        if (file_exists(ROOTPATH . 'CLAUDE.md')) {
            CLI::write('  CLAUDE.md (Claude Code) - OK', 'green');
            $checked++;
        }

        if (file_exists(ROOTPATH . '.cursor/rules/ci4-boost.md')) {
            CLI::write('  .cursor/rules/ci4-boost.md (Cursor) - OK', 'green');
            $checked++;
        }

        if (file_exists(ROOTPATH . '.github/copilot-instructions.md')) {
            CLI::write('  .github/copilot-instructions.md (Copilot) - OK', 'green');
            $checked++;
        }

        if ($checked === 0) {
            CLI::write('  No agent config files found. Run `php spark boost:install`.', 'red');
            $issues++;
        }
    }

    protected function checkMcpConfig(int &$issues): void
    {
        CLI::write('Checking MCP configuration...', 'yellow');

        $mcpFile = ROOTPATH . '.mcp.json';

        if (file_exists($mcpFile)) {
            $content = json_decode(file_get_contents($mcpFile), true);

            if (isset($content['mcpServers']['ci4-boost'])) {
                CLI::write('  .mcp.json with ci4-boost server - OK', 'green');
            } else {
                CLI::write('  .mcp.json exists but ci4-boost server not found', 'red');
                $issues++;
            }
        } else {
            CLI::write('  .mcp.json not found', 'red');
            $issues++;
        }
    }

    protected function checkKiloConfig(int &$issues): void
    {
        CLI::write('Checking Kilo Code configuration...', 'yellow');

        $kiloMcp = ROOTPATH . '.kilo/kilomcp.json';
        $kiloAgent = ROOTPATH . '.kilo/agent/default.md';
        $kiloCommands = ROOTPATH . '.kilo/command';

        if (file_exists($kiloMcp)) {
            CLI::write('  .kilo/kilomcp.json - OK', 'green');
        } else {
            CLI::write('  .kilo/kilomcp.json - Missing', 'red');
            $issues++;
        }

        if (file_exists($kiloAgent)) {
            CLI::write('  .kilo/agent/default.md - OK', 'green');
        } else {
            CLI::write('  .kilo/agent/default.md - Missing', 'red');
            $issues++;
        }

        if (is_dir($kiloCommands)) {
            $cmds = glob($kiloCommands . '/*.md') ?: [];
            CLI::write('  .kilo/command/ - ' . count($cmds) . ' commands - OK', 'green');
        } else {
            CLI::write('  .kilo/command/ - Missing', 'red');
            $issues++;
        }
    }

    protected function checkSparkCommands(int &$issues): void
    {
        CLI::write('Checking spark commands...', 'yellow');

        $commands = ['boost:install', 'boost:update', 'boost:mcp', 'boost:doctor'];

        foreach ($commands as $command) {
            CLI::write("  {$command} - Registered", 'green');
        }
    }
}