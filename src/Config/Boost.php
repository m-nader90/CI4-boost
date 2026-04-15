<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\Config;

use CodeIgniter\Config\BaseConfig;

class Boost extends BaseConfig
{
    public array $agents = [
        'claude-code' => \CodeIgniter\Boost\Install\Agents\ClaudeCode::class,
        'cursor' => \CodeIgniter\Boost\Install\Agents\Cursor::class,
        'claude-desktop' => \CodeIgniter\Boost\Install\Agents\ClaudeDesktop::class,
        'vscode-copilot' => \CodeIgniter\Boost\Install\Agents\VsCodeCopilot::class,
        'kilo-code' => \CodeIgniter\Boost\Install\Agents\KiloCode::class,
    ];

    public string $guidelinesPath = '.ai/guidelines';

    public string $skillsPath = '.ai/skills';

    public string $mcpConfigFile = '.mcp.json';

    public bool $enforceTests = false;

    public array $docsApiPackages = [
        'codeigniter4',
    ];

    public string $docsApiUrl = 'https://boost.codeigniter.com/api/docs/search';
}
