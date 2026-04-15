<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\MCP\Tools;

interface ToolInterface
{
    public function name(): string;

    public function description(): string;

    public function parameters(): array;

    public function definition(): array;

    public function execute(array $arguments = []): array;
}
