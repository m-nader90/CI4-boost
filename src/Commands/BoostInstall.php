<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\Boost\BoostManager;
use CodeIgniter\Boost\Config\Boost as BoostConfig;
use CodeIgniter\CLI\CLI;

class BoostInstall extends BaseCommand
{
    protected $group = 'boost';

    protected $name = 'boost:install';

    protected $description = 'Install CI4 Boost AI guidelines, skills, and MCP configuration.';

    public function run(array $params)
    {
        CLI::write('CI4 Boost Installer', 'cyan');
        CLI::write('===================', 'cyan');
        CLI::newLine();

        $manager = BoostManager::instance();
        $config = $manager->config();

        $agents = $manager->agents();
        $agentChoices = [];

        foreach ($agents as $name => $class) {
            $agentChoices[$name] = $class;
        }

        CLI::write('Select the AI agents you want to configure:', 'yellow');
        CLI::newLine();

        $availableAgents = [];
        $index = 1;

        foreach ($agentChoices as $name => $class) {
            $agent = new $class($config);
            $availableAgents[$index] = $name;
            CLI::write("  [{$index}] {$agent->label()} - {$agent->description()}");
            $index++;
        }

        CLI::newLine();
        $selection = CLI::prompt('Enter agent numbers (comma-separated, e.g., 1,2,3)', '1');

        $selectedIndexes = array_map('trim', explode(',', $selection));
        $selectedAgents = [];

        foreach ($selectedIndexes as $idx) {
            $idx = (int) $idx;

            if (isset($availableAgents[$idx])) {
                $selectedAgents[] = $availableAgents[$idx];
            }
        }

        if (empty($selectedAgents)) {
            CLI::error('No agents selected. Aborting.');

            return;
        }

        CLI::newLine();
        CLI::write('Installing guidelines...', 'yellow');

        $guidelinesManager = $manager->guidelines();
        $guidelinesPath = ROOTPATH . $config->guidelinesPath;
        $guidelinesManager->publishAll($guidelinesPath);

        $guidelines = $guidelinesManager->collectGuidelines();
        CLI::write('  Published ' . count($guidelines) . ' guideline files.', 'green');

        CLI::newLine();
        CLI::write('Installing skills...', 'yellow');

        $skillsManager = $manager->skills();
        $skillsPath = ROOTPATH . $config->skillsPath;
        $skillsManager->publishAll($skillsPath);

        $skills = $skillsManager->collectSkills();
        CLI::write('  Published ' . count($skills) . ' skill modules.', 'green');

        $sparkPath = is_file(ROOTPATH . 'spark') ? ROOTPATH . 'spark' : 'spark';

        CLI::newLine();
        CLI::write('Configuring agents...', 'yellow');

        foreach ($selectedAgents as $agentName) {
            $agent = $manager->agent($agentName);

            if ($agent === null) {
                continue;
            }

            CLI::write("  Configuring {$agent->label()}...", 'white');

            if ($agent->supportsGuidelines()) {
                $agent->publishGuidelines(ROOTPATH);
                CLI::write("    - Guidelines published.", 'green');
            }

            if ($agent->supportsSkills()) {
                $agent->publishSkills(ROOTPATH);
                CLI::write("    - Skills published.", 'green');
            }

            if ($agent->supportsMcp()) {
                $mcpCommand = ['php', $sparkPath, 'boost:mcp'];
                $agent->publishMcpConfig(ROOTPATH, $mcpCommand);
                CLI::write("    - MCP config published.", 'green');
            }
        }

        CLI::newLine();
        CLI::write('CI4 Boost installed successfully!', 'green');
        CLI::newLine();

        CLI::write('Manual MCP setup commands:', 'yellow');
        CLI::newLine();
        CLI::write('  Claude Code:    claude mcp add -s local -t stdio ci4-boost php spark boost:mcp', 'white');
        CLI::write('  Cursor:         Open command palette > /open MCP Settings > toggle ci4-boost', 'white');
        CLI::write('  Claude Desktop: claude mcp add -s project -t stdio ci4-boost php spark boost:mcp', 'white');
        CLI::write('  Kilo Code:      MCP config auto-generated in .kilo/kilomcp.json', 'white');
        CLI::write('  VS Code:        Open command palette > MCP Settings > check ci4-boost', 'white');
        CLI::newLine();

        CLI::write('To update Boost resources later, run: php spark boost:update', 'cyan');
    }
}
