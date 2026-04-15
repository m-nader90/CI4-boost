<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\Boost\BoostManager;
use CodeIgniter\CLI\CLI;

class BoostUpdate extends BaseCommand
{
    protected $group = 'boost';

    protected $name = 'boost:update';

    protected $description = 'Update CI4 Boost AI guidelines and skills to the latest versions.';

    public function run(array $params)
    {
        CLI::write('CI4 Boost Updater', 'cyan');
        CLI::write('==================', 'cyan');
        CLI::newLine();

        $discover = (bool) ($params['discover'] ?? CLI::getOption('discover'));

        $manager = BoostManager::instance();
        $config = $manager->config();

        CLI::write('Updating guidelines...', 'yellow');

        $guidelinesManager = $manager->guidelines();
        $guidelinesPath = ROOTPATH . $config->guidelinesPath;
        $guidelinesManager->publishAll($guidelinesPath);

        $guidelines = $guidelinesManager->collectGuidelines();
        CLI::write('  Updated ' . count($guidelines) . ' guideline files.', 'green');

        CLI::newLine();
        CLI::write('Updating skills...', 'yellow');

        $skillsManager = $manager->skills();
        $skillsPath = ROOTPATH . $config->skillsPath;
        $skillsManager->publishAll($skillsPath);

        $skills = $skillsManager->collectSkills();
        CLI::write('  Updated ' . count($skills) . ' skill modules.', 'green');

        if ($discover) {
            CLI::newLine();
            CLI::write('Scanning for new packages with Boost resources...', 'yellow');

            $composerPath = ROOTPATH . 'composer.json';

            if (file_exists($composerPath)) {
                $composer = json_decode(file_get_contents($composerPath), true);
                $packages = array_merge(
                    array_keys($composer['require'] ?? []),
                    array_keys($composer['require-dev'] ?? [])
                );

                $vendorDir = ROOTPATH . 'vendor/';
                $found = 0;

                foreach ($packages as $package) {
                    $boostPath = $vendorDir . $package . '/resources/boost';

                    if (is_dir($boostPath)) {
                        CLI::write("  Found Boost resources in: {$package}", 'green');
                        $found++;
                    }
                }

                if ($found === 0) {
                    CLI::write('  No new packages with Boost resources found.', 'white');
                }
            }
        }

        CLI::newLine();
        CLI::write('CI4 Boost resources updated successfully!', 'green');
    }
}
