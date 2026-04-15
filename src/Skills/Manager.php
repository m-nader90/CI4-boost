<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\Skills;

use CodeIgniter\Boost\Config\Boost as BoostConfig;
use function CodeIgniter\Boost\boost_resource_path;

class Manager
{
    protected BoostConfig $config;

    public function __construct(BoostConfig $config)
    {
        $this->config = $config;
    }

    public function collectSkills(): array
    {
        $skills = [];

        $builtinPath = boost_resource_path('skills');

        if (is_dir($builtinPath)) {
            $builtinSkills = $this->scanSkills($builtinPath);

            foreach ($builtinSkills as $name => $skillFile) {
                $skills[$name] = $skillFile;
            }
        }

        $customPath = ROOTPATH . $this->config->skillsPath;

        if (is_dir($customPath)) {
            $customSkills = $this->scanSkills($customPath);

            foreach ($customSkills as $name => $skillFile) {
                $skills[$name] = $skillFile;
            }
        }

        $vendorSkills = $this->scanVendorPackages();

        foreach ($vendorSkills as $name => $skillFile) {
            if (! isset($skills[$name])) {
                $skills[$name] = $skillFile;
            }
        }

        return $skills;
    }

    public function publishAll(string $targetPath): void
    {
        $skills = $this->collectSkills();

        foreach ($skills as $name => $sourceDir) {
            $destination = $targetPath . '/' . $name;

            if (! is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $this->copyDirectory($sourceDir, $destination);
        }
    }

    protected function scanSkills(string $path): array
    {
        $skills = [];
        $directories = glob($path . '/*', GLOB_ONLYDIR);

        if ($directories === false) {
            return $skills;
        }

        foreach ($directories as $dir) {
            $skillFile = $dir . '/SKILL.md';

            if (file_exists($skillFile)) {
                $name = basename($dir);
                $skills[$name] = $dir;
            }
        }

        return $skills;
    }

    protected function copyDirectory(string $source, string $destination): void
    {
        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $files = scandir($source);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $src = $source . '/' . $file;
            $dst = $destination . '/' . $file;

            if (is_dir($src)) {
                $this->copyDirectory($src, $dst);
            } else {
                copy($src, $dst);
            }
        }
    }

    protected function scanVendorPackages(): array
    {
        $skills = [];
        $composerPath = ROOTPATH . 'composer.json';

        if (! file_exists($composerPath)) {
            return $skills;
        }

        $composer = json_decode(file_get_contents($composerPath), true);
        $packages = array_merge(
            array_keys($composer['require'] ?? []),
            array_keys($composer['require-dev'] ?? [])
        );

        $vendorDir = ROOTPATH . 'vendor/';

        foreach ($packages as $package) {
            $boostSkillsPath = $vendorDir . $package . '/resources/boost/skills';

            if (is_dir($boostSkillsPath)) {
                $packageSkills = $this->scanSkills($boostSkillsPath);

                foreach ($packageSkills as $name => $skillDir) {
                    $skills[$name] = $skillDir;
                }
            }
        }

        return $skills;
    }
}
