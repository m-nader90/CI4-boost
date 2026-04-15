<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\Contracts;

interface SupportsGuidelines
{
    public function publishGuidelines(string $targetPath): void;
}
