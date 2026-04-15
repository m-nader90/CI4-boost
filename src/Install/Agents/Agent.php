<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\Install\Agents;

use CodeIgniter\Boost\Config\Boost as BoostConfig;

abstract class Agent
{
    protected BoostConfig $config;

    public function __construct(BoostConfig $config)
    {
        $this->config = $config;
    }

    abstract public function name(): string;

    abstract public function label(): string;

    abstract public function description(): string;

    public function publishGuidelines(string $targetPath): void
    {
    }

    public function publishSkills(string $targetPath): void
    {
    }

    public function publishMcpConfig(string $targetPath, string $command): void
    {
    }

    public function supportsGuidelines(): bool
    {
        return false;
    }

    public function supportsSkills(): bool
    {
        return false;
    }

    public function supportsMcp(): bool
    {
        return false;
    }
}
