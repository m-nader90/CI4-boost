<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\Contracts;

interface SupportsSkills
{
    public function publishSkills(string $targetPath): void;
}
