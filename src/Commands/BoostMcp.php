<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\Boost\MCP\Server;

class BoostMcp extends BaseCommand
{
    protected $group = 'boost';

    protected $name = 'boost:mcp';

    protected $description = 'Start the CI4 Boost MCP server for AI agent communication.';

    public function run(array $params)
    {
        $server = new Server();
        $server->run();
    }
}
