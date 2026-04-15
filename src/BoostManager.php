<?php

declare(strict_types=1);

namespace CodeIgniter\Boost;

use CodeIgniter\Boost\Config\Boost as BoostConfig;
use CodeIgniter\Boost\Guidelines\Manager as GuidelinesManager;
use CodeIgniter\Boost\Install\Agents\Agent;
use CodeIgniter\Boost\Skills\Manager as SkillsManager;
use Config\Services;

class BoostManager
{
    protected static ?self $instance = null;

    protected BoostConfig $config;

    protected array $agents = [];

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function registerAgent(string $name, string $class): void
    {
        self::instance()->agents[$name] = $class;
    }

    public function __construct(?BoostConfig $config = null)
    {
        $this->config = $config ?? config(BoostConfig::class);

        $this->agents = array_merge($this->config->agents, $this->agents);
    }

    public function config(): BoostConfig
    {
        return $this->config;
    }

    public function get(string $key): mixed
    {
        return $this->config->$key ?? null;
    }

    public function agents(): array
    {
        return $this->agents;
    }

    public function agent(string $name): ?Agent
    {
        if (! isset($this->agents[$name])) {
            return null;
        }

        $class = $this->agents[$name];

        return new $class($this->config);
    }

    public function guidelines(): GuidelinesManager
    {
        return new GuidelinesManager($this->config);
    }

    public function skills(): SkillsManager
    {
        return new SkillsManager($this->config);
    }

    public function installedPackages(): array
    {
        $composerPath = ROOTPATH . 'composer.json';

        if (! file_exists($composerPath)) {
            return [];
        }

        $composer = json_decode(file_get_contents($composerPath), true);

        return array_merge(
            array_keys($composer['require'] ?? []),
            array_keys($composer['require-dev'] ?? [])
        );
    }

    public function eloquentModels(): array
    {
        return [];
    }

    public function ciModels(): array
    {
        $models = [];

        if (! is_dir(APPPATH . 'Models')) {
            return $models;
        }

        $files = glob(APPPATH . 'Models/*.php');

        foreach ($files as $file) {
            $content = file_get_contents($file);

            if (preg_match('/namespace\s+App\\\\Models;\s*class\s+(\w+)/', $content, $matches)) {
                $models[] = $matches[1];
            }
        }

        return $models;
    }

    public function appInfo(): array
    {
        $ciVersion = CI_VERSION;
        $phpVersion = PHP_VERSION;

        $db = null;
        try {
            $dbConfig = config('Database');
            if ($dbConfig !== null) {
                $defaultGroup = $dbConfig->defaultGroup;
                $db = $dbConfig->$defaultGroup;
            }
        } catch (\Throwable) {
        }

        return [
            'php_version' => $phpVersion,
            'ci_version' => $ciVersion,
            'environment' => ENVIRONMENT,
            'database' => [
                'driver' => $db['DBDriver'] ?? 'unknown',
                'database' => $db['database'] ?? 'unknown',
                'hostname' => $db['hostname'] ?? 'localhost',
            ],
            'installed_packages' => $this->installedPackages(),
            'models' => $this->ciModels(),
            'base_url' => base_url(),
        ];
    }
}
