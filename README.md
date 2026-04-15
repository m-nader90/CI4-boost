# CI4 Boost

<p align="center">
<strong>Accelerate AI-assisted CodeIgniter 4 development</strong><br>
MCP server, AI guidelines, and agent skills for Kilo Code, Claude Code, Cursor, and more.
</p>

<p align="center">
<a href="https://github.com/m-nader90/CI4-boost"><img src="https://img.shields.io/badge/GitHub-m--nader90%2FCI4--boost-blue" alt="GitHub"></a>
<a href="https://packagist.org/packages/m-nader90/ci4-boost"><img src="https://img.shields.io/packagist/v/m-nader90/ci4-boost" alt="Packagist Version"></a>
<a href="https://codeigniter.com"><img src="https://img.shields.io/badge/CodeIgniter-4.4%2B-orange" alt="CodeIgniter 4"></a>
<a href="https://www.php.net"><img src="https://img.shields.io/badge/PHP-8.1%2B-purple" alt="PHP Version"></a>
<a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/License-MIT-green" alt="License"></a>
</p>

---

## Introduction

CI4 Boost is a CodeIgniter 4 package that provides an **MCP (Model Context Protocol) server** for AI coding agents. It gives AI agents the context they need to generate correct, idiomatic CI4 code by providing:

- **MCP Server** with 8 tools for inspecting your application (database schema, models, logs, config)
- **12 AI Guidelines** covering CI4 conventions and best practices
- **8 Agent Skills** for on-demand knowledge about specific domains
- **5 Agent Integrations** with auto-generated configurations
- **Documentation Search** for querying CI4 docs from within AI agents

## Installation

```bash
cd your-ci4-project
composer require m-nader90/ci4-boost:@dev --dev
```

Install the MCP server and coding guidelines:

```bash
php spark boost:install
```

Select your AI agents when prompted.

## Features

### MCP Server Tools

| Tool | Description |
|------|-------------|
| `application_info` | Read PHP & CI4 versions, database info, installed packages, models |
| `database_connections` | Inspect available database connections |
| `database_query` | Execute SELECT queries (SQL injection protected) |
| `database_schema` | Read database schema (tables, columns, foreign keys) |
| `get_url` | Convert relative URIs to absolute URLs |
| `last_error` | Read the last error from log files |
| `read_logs` | Read the last N log entries |
| `search_docs` | Search CI4 documentation |

### AI Guidelines (12 topics)

Pre-built guidelines for CodeIgniter 4 topics:

- **Core** - Framework conventions & directory structure
- **Controllers** - RESTful patterns, request/response handling
- **Models** - CRUD, query builder, validation
- **Entities** - Casting, mutators, accessors
- **Routing** - Routes, resource routes, groups, filters
- **Views** - Layouts, sections, view cells
- **Database** - Migrations, seeding, query builder
- **Validation** - Rules, custom rules, error messages
- **Helpers** - URL, form, HTML, text, date helpers
- **Middleware/Filters** - FilterInterface, global filters
- **Libraries** - Service injection, custom libraries
- **CLI Commands** - Spark commands

### Agent Skills (8 modules)

On-demand knowledge modules activated when relevant:

| Skill | Purpose |
|-------|---------|
| `controller-development` | RESTful controllers, filters, responses |
| `model-development` | Models, entities, relationships, CRUD |
| `view-development` | Layouts, sections, forms, view cells |
| `database-development` | Migrations, seeding, query builder |
| `validation-development` | Rules, custom rules, error messages |
| `routing-development` | Routes, groups, resources, filters |
| `boost-development` | Developing and extending CI4 Boost |
| `ui-ux-design` | Comprehensive UI/UX design skills framework |

## Agent Setup

### Kilo Code (Recommended)

Kilo Code setup is fully automatic when you run `php spark boost:install` and select Kilo Code. The installer generates:

```
your-project/
├── AGENTS.md                    # CI4 guidelines
├── .kilo/
│   ├── kilomcp.json            # MCP server config
│   ├── command/
│   │   ├── boost-update.md     # /boost-update
│   │   ├── boost-install.md    # /boost-install
│   │   ├── ci-migrate.md       # /ci-migrate
│   │   └── ci-seeder.md        # /ci-seeder
│   └── agent/
│       └── default.md          # Agent instructions
```

After installation, restart Kilo Code to activate.

### Claude Code
```bash
claude mcp add -s local -t stdio ci4-boost php spark boost:mcp
```

### Cursor
1. Open command palette (`Cmd+Shift+P` or `Ctrl+Shift+P`)
2. Press Enter on "/open MCP Settings"
3. Toggle on `ci4-boost`

### Claude Desktop
```bash
claude mcp add -s project -t stdio ci4-boost php spark boost:mcp
```

### VS Code Copilot
1. Open command palette
2. Search "MCP Settings" and press Enter
3. Check `ci4-boost` and click Apply

## Spark Commands

| Command | Description |
|---------|-------------|
| `php spark boost:install` | Install AI guidelines, skills, and MCP configuration |
| `php spark boost:update` | Update guidelines and skills to latest versions |
| `php spark boost:mcp` | Start the MCP server (stdio transport) |
| `php spark boost:doctor` | Diagnose installation and verify configuration |

## Usage

### Diagnose Installation
```bash
php spark boost:doctor
```

Checks PHP version, CI4 version, guidelines, skills, agent configs, and MCP configuration.

### Update Resources
```bash
php spark boost:update
```

To discover new packages with Boost resources:
```bash
php spark boost:update --discover
```

### Add Custom Guidelines

Add `.md` files to `.ai/guidelines/`:

```
.ai/guidelines/
└── custom-topic.md    # Your custom guidelines
```

### Add Custom Skills

Add a `SKILL.md` file to `.ai/skills/{skill-name}/`:

```
.ai/skills/my-feature/
└── SKILL.md          # Your custom skill
```

Example SKILL.md:
```markdown
---
name: my-feature
description: When to use this skill
---

# My Feature

Instructions for the AI agent...
```

### Override Built-in Resources

Create files with matching paths in `.ai/guidelines/` or `.ai/skills/` to override built-in resources.

## Third-Party Package Support

Package authors can ship Boost resources:

```
your-package/
└── resources/
    └── boost/
        ├── guidelines/
        │   └── topic.md
        └── skills/
            └── skill-name/
                └── SKILL.md
```

These are automatically discovered when users run `php spark boost:install`.

## MCP Server Configuration

Manual MCP registration in `.mcp.json`:

```json
{
    "mcpServers": {
        "ci4-boost": {
            "command": "php",
            "args": ["spark", "boost:mcp"]
        }
    }
}
```

For Kilo Code (`.kilo/kilomcp.json`):

```json
{
    "mcpServers": {
        "ci4-boost": {
            "command": "php",
            "args": ["spark", "boost:mcp"]
        }
    }
}
```

## Extending Boost

Register custom agents:

```php
use CodeIgniter\Boost\BoostManager;
use CodeIgniter\Boost\Install\Agents\Agent;

class CustomAgent extends Agent
{
    public function name(): string { return 'custom'; }
    public function label(): string { return 'Custom Agent'; }
    public function description(): string { return 'My custom AI agent.'; }
    public function supportsGuidelines(): bool { return true; }
    public function supportsMcp(): bool { return true; }
    
    public function publishGuidelines(string $targetPath): void
    {
        // Generate your agent's guidelines
    }
}

// Register in AppServiceProvider::boot()
BoostManager::registerAgent('custom', CustomAgent::class);
```

## Architecture

```
CI4 Boost/
├── src/
│   ├── BoostManager.php           # Core manager
│   ├── MCP/
│   │   ├── Server.php              # JSON-RPC server
│   │   ├── ToolRegistry.php        # Tool management
│   │   └── Tools/ (8 tools)
│   ├── Commands/ (4 spark commands)
│   ├── Install/Agents/ (5 agents)
│   ├── Guidelines/Manager.php
│   └── Skills/Manager.php
├── resources/
│   ├── guidelines/ (12 docs)
│   └── skills/ (8 modules)
└── tests/
```

## Security

The MCP server implements security measures:

- **SQL Injection Prevention**: Only SELECT queries allowed in `database_query`
- **Input Validation**: All tools validate required parameters
- **Safe Defaults**: Limited query results (max 100/1000)
- **Log Access**: Read-only access to application logs

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests: `composer test`
5. Submit a pull request

## Requirements

- PHP 8.1+
- CodeIgniter 4.4+

## License

MIT License

## Links

- **GitHub**: https://github.com/m-nader90/CI4-boost
- **CodeIgniter 4**: https://codeigniter.com
- **MCP Protocol**: https://modelcontextprotocol.io