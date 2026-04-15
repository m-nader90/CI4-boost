---
name: boost-development
description: Develop, extend, and maintain the CI4 Boost package - an MCP server and AI agent integration framework for CodeIgniter 4 applications.
---

# CI4 Boost Development Skill

This skill provides comprehensive guidance for developing, extending, and maintaining the CI4 Boost package. Use this when adding new MCP tools, creating agent integrations, writing guidelines, or extending the package's core functionality.

## Architecture Overview

CI4 Boost follows a modular architecture with clear separation of concerns:

### Core Components

| Layer | Directory | Purpose |
|-------|-----------|---------|
| Manager | `src/BoostManager.php` | Singleton orchestrating all Boost functionality |
| MCP Server | `src/MCP/Server.php` | JSON-RPC server implementing MCP protocol |
| Tools | `src/MCP/Tools/*.php` | Individual tool implementations |
| Agents | `src/Install/Agents/*.php` | AI agent-specific configuration generators |
| Guidelines | `src/Guidelines/Manager.php` | Markdown guideline collector and publisher |
| Skills | `src/Skills/Manager.php` | Skill module collector and publisher |
| Commands | `src/Commands/*.php` | Spark CLI commands |
| Config | `src/Config/Boost.php` | Package configuration |

### Key Patterns

- **Singleton Pattern**: `BoostManager::instance()` provides global access
- **Strategy Pattern**: Each agent implements `Agent` abstract class
- **Registry Pattern**: `ToolRegistry` manages all MCP tools
- **Publisher Pattern**: Guidelines and Skills managers collect from multiple sources

## Creating MCP Tools

### Tool Interface Contract

Every tool must implement `CodeIgniter\Boost\MCP\Tools\ToolInterface`:

```php
interface ToolInterface
{
    public function name(): string;           // Unique tool identifier (snake_case)
    public function description(): string;    // Human-readable description for AI
    public function parameters(): array;      // JSON Schema for input
    public function definition(): array;      // Full MCP tool definition
    public function execute(array $arguments = []): array;  // Tool execution
}
```

### Creating a New Tool

1. Create file in `src/MCP/Tools/YourTool.php`
2. Implement all interface methods
3. Register in `ToolRegistry::registerDefaults()`

Example minimal tool structure:

```php
<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\MCP\Tools;

use CodeIgniter\Boost\MCP\Tools\ToolInterface;

class YourTool implements ToolInterface
{
    public function name(): string
    {
        return 'your_tool_name';
    }

    public function description(): string
    {
        return 'Description that AI agents will see when listing tools.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'param1' => [
                    'type' => 'string',
                    'description' => 'Description of parameter.',
                ],
            ],
            'required' => ['param1'],
        ];
    }

    public function definition(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'inputSchema' => $this->parameters(),
        ];
    }

    public function execute(array $arguments = []): array
    {
        $param1 = $arguments['param1'] ?? '';

        if ($param1 === '') {
            return [
                'content' => [
                    ['type' => 'text', 'text' => 'param1 is required.'],
                ],
                'isError' => true,
            ];
        }

        $result = 'Your tool result here';

        return [
            'content' => [
                ['type' => 'text', 'text' => $result],
            ],
        ];
    }
}
```

### Tool Response Format

All tools must return an array with:

```php
[
    'content' => [
        ['type' => 'text', 'text' => 'Result string'],
    ],
    'isError' => true,  // Optional, defaults to false
]
```

### Registering Tools

Add new tools to `ToolRegistry.php` in the `registerDefaults()` method:

```php
$this->register(new YourTool());
```

## Creating Agent Integrations

### Agent Abstract Class

All agents extend `CodeIgniter\Boost\Install\Agents\Agent`:

```php
abstract class Agent
{
    abstract public function name(): string;         // Machine name (kebab-case)
    abstract public function label(): string;         // Display name
    abstract public function description(): string;  // Agent description

    public function supportsGuidelines(): bool { return false; }
    public function supportsSkills(): bool { return false; }
    public function supportsMcp(): bool { return false; }

    public function publishGuidelines(string $targetPath): void {}
    public function publishSkills(string $targetPath): void {}
    public function publishMcpConfig(string $targetPath, string $command): void {}
}
```

### Creating a New Agent

1. Create file in `src/Install/Agents/YourAgent.php`
2. Extend `Agent` class
3. Implement all abstract methods
4. Override support methods as needed
5. Register in `Config/Boost.php`

Example agent:

```php
<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\Install\Agents;

class YourAgent extends Agent
{
    public function name(): string
    {
        return 'your-agent';
    }

    public function label(): string
    {
        return 'Your Agent';
    }

    public function description(): string
    {
        return 'Description of the AI agent this integration supports.';
    }

    public function supportsGuidelines(): bool
    {
        return true;
    }

    public function supportsMcp(): bool
    {
        return true;
    }

    public function publishGuidelines(string $targetPath): void
    {
        helper('filesystem');
        $filepath = $targetPath . '/YOUR_AGENT.md';
        $content = $this->buildGuidelinesContent();
        write_file($filepath, $content);
    }

    public function publishMcpConfig(string $targetPath, string $command): void
    {
        helper('filesystem');
        $configFile = $targetPath . '/.mcp.json';
        $config = [
            'mcpServers' => [
                'ci4-boost' => [
                    'command' => $command[0],
                    'args' => array_slice($command, 1),
                ],
            ],
        ];
        write_file($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function buildGuidelinesContent(): string
    {
        $guidelinesPath = boost_resource_path('guidelines');
        $content = "# CI4 Boost Guidelines\n\n";
        // ... build content from guidelines files
        return $content;
    }
}
```

### Registering Agents

Add to `Config/Boost.php`:

```php
public array $agents = [
    // ... existing agents
    'your-agent' => \CodeIgniter\Boost\Install\Agents\YourAgent::class,
];
```

## MCP Protocol Implementation

### Protocol Version

Current: `2024-11-05` (defined in `Server.php`)

### Transport

- Uses stdio (standard input/output)
- JSON-RPC 2.0 message format
- Content-Length header for message framing

### Message Format

Request:
```
Content-Length: {length}\r\n\r\n{json_body}
```

Response:
```json
{
    "jsonrpc": "2.0",
    "id": {request_id},
    "result": {result}
}
```

### Supported Methods

| Method | Handler | Description |
|--------|---------|-------------|
| `initialize` | `handleInitialize()` | Protocol handshake |
| `tools/list` | `handleToolsList()` | List available tools |
| `tools/call` | `handleToolCall()` | Execute a tool |
| `ping` | `sendResponse()` | Health check |

### Adding New Methods

Add to `processMessage()` switch statement in `Server.php`:

```php
case 'your/method':
    $this->handleYourMethod($id, $params, $stdout);
    break;
```

## Guidelines System

### Directory Structure

```
resources/guidelines/
├── codeigniter/
│   └── core.md
├── controllers.md
├── models.md
├── views.md
├── database.md
├── validation.md
├── routing.md
├── helpers.md
├── middleware-filters.md
├── libraries.md
├── entities.md
└── cli-commands.md
```

### Guideline File Format

Markdown files with clear structure:

```markdown
# Topic Name

Brief introduction paragraph.

## Subtopic

Description and explanation.

## Code Examples

Example code without unnecessary comments:
```php
$concise = 'code example';
```

## Best Practices

- Bullet point list
- Of best practices
```

### Custom Guidelines

Users can override by placing matching files in `.ai/guidelines/`:

```
.ai/guidelines/controllers.md    # Overrides resources/guidelines/controllers.md
```

## Skills System

### Skill Structure

```
resources/skills/{skill-name}/
├── SKILL.md                     # Required: skill definition
├── templates/                   # Optional: code templates
└── examples/                    # Optional: example files
```

### SKILL.md Format

```markdown
---
name: skill-name
description: When to use this skill and what it covers.
---

# Skill Title

When to use this skill:
- Condition 1
- Condition 2

## Step-by-Step Process

1. First step
2. Second step

## Code Examples

Practical code examples.
```

### Creating New Skills

1. Create directory: `resources/skills/{skill-name}/`
2. Create `SKILL.md` with YAML frontmatter
3. Add templates and examples as needed

## Spark Commands

### Command Structure

Extend `CodeIgniter\CLI\BaseCommand`:

```php
class YourCommand extends BaseCommand
{
    protected $group = 'boost';
    protected $name = 'boost:your-command';
    protected $description = 'Command description';

    public function run(array $params)
    {
        CLI::write('Output', 'color');
    }
}
```

### Registering Commands

Add to `composer.json`:

```json
"extra": {
    "ci4-boost": {
        "commands": [
            "CodeIgniter\\Boost\\Commands\\YourCommand"
        ]
    }
}
```

### Available Commands

| Command | Purpose |
|---------|---------|
| `boost:install` | Install all Boost resources |
| `boost:update` | Update guidelines and skills |
| `boost:mcp` | Start MCP server |
| `boost:doctor` | Diagnose installation |

## Testing

### Test Structure

```
tests/
├── MCP/
│   ├── ServerTest.php
│   ├── ToolRegistryTest.php
│   └── Tools/
│       └── ToolContractTest.php
├── Install/Agents/
│   └── AgentTest.php
├── Config/
│   └── BoostConfigTest.php
└── Guidelines/
    └── ManagerTest.php
```

### Writing Tests

Extend `PHPUnit\Framework\TestCase`:

```php
class YourTest extends TestCase
{
    public function testSomething(): void
    {
        $result = 'expected';
        $this->assertSame('expected', $result);
    }
}
```

### Running Tests

```bash
composer test
# or
php vendor/bin/phpunit
```

## Code Conventions

### File Structure

```php
<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\Directory;

use CodeIgniter\Boost\SomeClass;

class YourClass
{
    protected string $property;

    public function method(): string
    {
        return 'value';
    }
}
```

### Naming Conventions

- Classes: `PascalCase`
- Methods: `camelCase`
- Properties: `camelCase`
- Constants: `UPPER_SNAKE_CASE`
- Files: `PascalCase.php`

### No Comments Policy

Write self-explanatory code. Only add comments when absolutely necessary for complex logic.

## Third-Party Package Integration

### Package Structure

Third-party packages can ship Boost resources:

```
vendor/package/
└── resources/
    └── boost/
        ├── guidelines/
        │   └── topic.md
        └── skills/
            └── skill-name/
                └── SKILL.md
```

### Discovery Process

1. `BoostManager::installedPackages()` reads composer.json
2. Checks each package for `resources/boost/` directory
3. Merges guidelines and skills into collection

## Troubleshooting

### Common Issues

**MCP Server Not Starting**
- Check PHP version >= 8.1
- Verify spark file exists
- Run `php spark boost:doctor`

**Guidelines Not Publishing**
- Check `resources/guidelines/` exists
- Verify file permissions
- Use `helper('filesystem')` for write operations

**Tools Not Found**
- Register in `ToolRegistry::registerDefaults()`
- Verify tool implements `ToolInterface`
- Check tool name matches registration

### Debug Mode

Run doctor command for diagnostics:

```bash
php spark boost:doctor
```

## Version Compatibility

| Boost Version | PHP | CodeIgniter |
|---------------|-----|-------------|
| 1.x           | 8.1+| 4.4+        |

## Extension Points

1. **Add Tools**: Create in `src/MCP/Tools/`, register in `ToolRegistry`
2. **Add Agents**: Create in `src/Install/Agents/`, register in `Config/Boost`
3. **Add Guidelines**: Create in `resources/guidelines/`
4. **Add Skills**: Create in `resources/skills/{name}/SKILL.md`
5. **Add Commands**: Create in `src/Commands/`, register in `composer.json`