<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\Guidelines;

use CodeIgniter\Boost\Config\Boost as BoostConfig;

class Manager
{
    protected BoostConfig $config;

    public function __construct(BoostConfig $config)
    {
        $this->config = $config;
    }

    public function collectGuidelines(): array
    {
        $guidelines = [];

        $builtinPath = boost_resource_path('guidelines');

        if (is_dir($builtinPath)) {
            $files = $this->scanDirectory($builtinPath);

            foreach ($files as $relativePath => $absolutePath) {
                $guidelines[$relativePath] = $absolutePath;
            }
        }

        $customPath = ROOTPATH . $this->config->guidelinesPath;

        if (is_dir($customPath)) {
            $files = $this->scanDirectory($customPath);

            foreach ($files as $relativePath => $absolutePath) {
                $guidelines[$relativePath] = $absolutePath;
            }
        }

        $vendorGuidelines = $this->scanVendorPackages();

        foreach ($vendorGuidelines as $relativePath => $absolutePath) {
            if (! isset($guidelines[$relativePath])) {
                $guidelines[$relativePath] = $absolutePath;
            }
        }

        return $guidelines;
    }

    public function publishAll(string $targetPath): void
    {
        $guidelines = $this->collectGuidelines();

        foreach ($guidelines as $relativePath => $absolutePath) {
            $destination = $targetPath . '/' . $relativePath;

            $dir = dirname($destination);

            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            copy($absolutePath, $destination);
        }
    }

    protected function scanDirectory(string $path): array
    {
        $files = [];

        $pattern = $path . '/**/*.{md,php}';

        $matches = glob($pattern, GLOB_BRACE);

        if ($matches === false) {
            return $files;
        }

        foreach ($matches as $file) {
            if (is_file($file)) {
                $relativePath = substr($file, strlen($path) + 1);
                $files[$relativePath] = $file;
            }
        }

        return $files;
    }

    protected function scanVendorPackages(): array
    {
        $files = [];
        $composerPath = ROOTPATH . 'composer.json';

        if (! file_exists($composerPath)) {
            return $files;
        }

        $composer = json_decode(file_get_contents($composerPath), true);
        $packages = array_merge(
            array_keys($composer['require'] ?? []),
            array_keys($composer['require-dev'] ?? [])
        );

        $vendorDir = ROOTPATH . 'vendor/';

        foreach ($packages as $package) {
            $boostGuidelinesPath = $vendorDir . $package . '/resources/boost/guidelines';

            if (is_dir($boostGuidelinesPath)) {
                $packageFiles = $this->scanDirectory($boostGuidelinesPath);

                foreach ($packageFiles as $relativePath => $absolutePath) {
                    $files[$package . '/' . $relativePath] = $absolutePath;
                }
            }
        }

        return $files;
    }
}
