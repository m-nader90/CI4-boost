<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\Contracts;

interface SupportsMcp
{
    public function publishMcpConfig(string $targetPath, string $command): void;
}
