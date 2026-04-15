<?php

declare(strict_types=1);

namespace CodeIgniter\Boost\MCP;

class Server
{
    protected ToolRegistry $tools;

    protected bool $initialized = false;

    protected string $serverVersion = '1.0.0';

    protected string $protocolVersion = '2024-11-05';

    public function __construct(?ToolRegistry $tools = null)
    {
        $this->tools = $tools ?? new ToolRegistry();
    }

    public function run(): void
    {
        $stdin = fopen('php://stdin', 'r');
        $stdout = fopen('php://stdout', 'w');

        if ($stdin === false || $stdout === false) {
            return;
        }

        stream_set_blocking($stdin, false);

        $buffer = '';

        while (! feof($stdin)) {
            $chunk = fread($stdin, 8192);

            if ($chunk === false || $chunk === '') {
                usleep(10000);
                continue;
            }

            $buffer .= $chunk;

            while ($this->extractMessage($buffer, $message, $buffer)) {
                $this->processMessage($message, $stdout);
            }
        }
    }

    protected function extractMessage(string &$buffer, ?array &$message, string &$remaining): bool
    {
        $pattern = '/Content-Length:\s*(\d+)\s*\r\n\r\n/s';

        if (! preg_match($pattern, $buffer, $matches, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        $contentLength = (int) $matches[1][0];
        $headerEnd = $matches[0][1] + strlen($matches[0][0]);

        if (strlen($buffer) < $headerEnd + $contentLength) {
            return false;
        }

        $json = substr($buffer, $headerEnd, $contentLength);
        $remaining = substr($buffer, $headerEnd + $contentLength);

        $decoded = json_decode($json, true);

        if ($decoded === null) {
            return false;
        }

        $message = $decoded;

        return true;
    }

    protected function processMessage(array $message, $stdout): void
    {
        $method = $message['method'] ?? null;
        $id = $message['id'] ?? null;
        $params = $message['params'] ?? [];

        switch ($method) {
            case 'initialize':
                $this->handleInitialize($id, $params, $stdout);
                break;

            case 'notifications/initialized':
                break;

            case 'tools/list':
                $this->handleToolsList($id, $params, $stdout);
                break;

            case 'tools/call':
                $this->handleToolCall($id, $params, $stdout);
                break;

            case 'ping':
                $this->sendResponse($stdout, $id, []);
                break;

            default:
                if ($id !== null) {
                    $this->sendError($stdout, $id, -32601, "Method not found: {$method}");
                }
        }
    }

    protected function handleInitialize($id, array $params, $stdout): void
    {
        $this->initialized = true;

        $clientName = $params['clientInfo']['name'] ?? 'unknown';

        $result = [
            'protocolVersion' => $this->protocolVersion,
            'capabilities' => [
                'tools' => [
                    'listChanged' => false,
                ],
            ],
            'serverInfo' => [
                'name' => 'ci4-boost',
                'version' => $this->serverVersion,
            ],
        ];

        $this->sendResponse($stdout, $id, $result);
    }

    protected function handleToolsList($id, array $params, $stdout): void
    {
        $result = [
            'tools' => $this->tools->listForProtocol(),
        ];

        $this->sendResponse($stdout, $id, $result);
    }

    protected function handleToolCall($id, array $params, $stdout): void
    {
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        $result = $this->tools->call($toolName, $arguments);

        $this->sendResponse($stdout, $id, $result);
    }

    protected function sendResponse($stdout, $id, array $result): void
    {
        $response = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];

        $this->writeMessage($stdout, $response);
    }

    protected function sendError($stdout, $id, int $code, string $message): void
    {
        $response = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        $this->writeMessage($stdout, $response);
    }

    protected function writeMessage($stdout, array $message): void
    {
        $json = json_encode($message, JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return;
        }

        $header = "Content-Length: " . strlen($json) . "\r\n\r\n";

        fwrite($stdout, $header . $json);
        fflush($stdout);
    }
}
