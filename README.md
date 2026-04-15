# CI4 Boost

<p align="center">
CI4 Boost accelerates AI-assisted development by providing the essential guidelines and agent skills that help AI agents write high-quality, CodeIgniter 4-specific code.
</p>

## Introduction

CI4 Boost is a CodeIgniter 4 package that provides an **MCP (Model Context Protocol) server** for AI coding agents (Kilo Code, Claude Code, Cursor, Claude Desktop, GitHub Copilot). It gives AI agents the context they need to generate correct, idiomatic CI4 code by providing:

- **MCP Server** with tools for inspecting your application (database schema, models, logs, config)
- **AI Guidelines** covering CI4 conventions and best practices
- **Agent Skills** for on-demand knowledge about specific CI4 domains
- **Documentation Search** for querying CI4 docs from within AI agents

## Installation

```bash
composer require codeigniter4/boost --dev
```

Install the MCP server and coding guidelines:

```bash
php spark boost:install
```

## Features

### MCP Server

The MCP server provides tools for AI agents:

| Tool | Description |
|------|-------------|
| `application_info` | Read PHP & CI4 versions, database info, installed packages, models |
| `database_connections` | Inspect available database connections |
| `database_query` | Execute a SELECT query against the database |
| `database_schema` | Read the database schema (tables, columns, foreign keys) |
| `get_url` | Convert relative URIs to absolute URLs |
| `last_error` | Read the last error from log files |
| `read_logs` | Read the last N log entries |
| `search_docs` | Query CI4 documentation |

### AI Guidelines

Pre-built guidelines for CodeIgniter 4 topics:

- Core framework conventions & directory structure
- Controllers (RESTful patterns, request/response handling)
- Models (CRUD, query builder, validation)
- Entities (casting, mutators, accessors)
- Routing (routes, resource routes, groups, filters)
- Views (layouts, sections, view cells)
- Database (migrations, seeding, query builder)
- Validation (rules, custom rules, error messages)
- Helpers (url, form, html, text, date helpers)
- Middleware/Filters (FilterInterface, global filters)
- Libraries (service injection, custom libraries)
- CLI Commands (spark commands)

### Agent Skills

On-demand knowledge modules activated when relevant:

- **controller-development** - RESTful controllers, filters, responses
- **model-development** - Models, entities, relationships, CRUD
- **view-development** - Layouts, sections, forms, view cells
- **database-development** - Migrations, seeding, query builder
- **validation-development** - Rules, custom rules, error messages
- **routing-development** - Routes, groups, resources, filters

## Agent Setup

### Kilo Code

Kilo Code setup is fully automatic when you run `php spark boost:install` and select Kilo Code. The installer generates:

- `AGENTS.md` - CI4 guidelines loaded automatically by Kilo Code
- `.kilo/kilomcp.json` - MCP server configuration
- `.kilo/command/boost-update.md` - Slash command: `/boost-update`
- `.kilo/command/boost-install.md` - Slash command: `/boost-install`
- `.kilo/command/ci-migrate.md` - Slash command: `/ci-migrate`
- `.kilo/command/ci-seeder.md` - Slash command: `/ci-seeder`
- `.kilo/agent/default.md` - Default agent instructions for CI4

After installation, restart Kilo Code to pick up the new configuration.

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

### GitHub Copilot (VS Code)
1. Open command palette
2. Search "MCP Settings" and press Enter
3. Check `ci4-boost` and click Apply

## Usage

### Diagnosing Installation

```bash
php spark boost:doctor
```

This checks PHP version, CI4 version, guidelines, skills, agent configs, MCP config, and Kilo Code configuration.

### Keeping Resources Updated

```bash
php spark boost:update
```

To discover new packages with Boost resources:

```bash
php spark boost:update --discover
```

### Adding Custom Guidelines

Add `.md` files to your application's `.ai/guidelines/` directory. They will be included automatically.

### Adding Custom Skills

Add a `SKILL.md` file to `.ai/skills/{skill-name}/`. Follow the Agent Skills format with YAML frontmatter.

### Overriding Built-in Resources

Create files with matching paths in `.ai/guidelines/` or `.ai/skills/` to override Boost's built-in resources.

## Third-Party Package Support

Package authors can ship Boost resources:

- **Guidelines**: Add `resources/boost/guidelines/core.md` to your package
- **Skills**: Add `resources/boost/skills/{skill-name}/SKILL.md` to your package

These will be automatically discovered when users run `php spark boost:install`.

## MCP Server Registration

Manual MCP registration:

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
use CodeIgniter\Boost\Config\Boost as BoostConfig;

class CustomAgent extends Agent
{
    public function name(): string { return 'custom'; }
    public function label(): string { return 'Custom Agent'; }
    public function description(): string { return 'My custom AI agent.'; }
    public function supportsGuidelines(): bool { return true; }
    public function supportsMcp(): bool { return true; }
}

// In AppServiceProvider::boot()
BoostManager::registerAgent('custom', CustomAgent::class);
```

## Spark Commands

| Command | Description |
|---------|-------------|
| `php spark boost:install` | Install AI guidelines, skills, and MCP configuration |
| `php spark boost:update` | Update guidelines and skills to latest versions |
| `php spark boost:mcp` | Start the MCP server (stdio transport) |
| `php spark boost:doctor` | Diagnose installation and verify configuration |

## Requirements

- PHP 8.1+
- CodeIgniter 4.4+

## License

MIT License
