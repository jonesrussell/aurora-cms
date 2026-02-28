<?php

declare(strict_types=1);

namespace Aurora\AI\Schema\Mcp;

/**
 * Value object representing an MCP (Model Context Protocol) tool definition.
 *
 * Each tool has a name, human-readable description, and a JSON Schema
 * describing its input parameters. This matches the MCP specification
 * for tool registration.
 */
final readonly class McpToolDefinition
{
    /**
     * @param string $name Tool name in snake_case (e.g. "create_node").
     * @param string $description Human-readable description of what the tool does.
     * @param array<string, mixed> $inputSchema JSON Schema for input parameters.
     */
    public function __construct(
        public string $name,
        public string $description,
        public array $inputSchema,
    ) {}

    /**
     * Serialize to a plain array matching MCP tool registration format.
     *
     * @return array{name: string, description: string, inputSchema: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'inputSchema' => $this->inputSchema,
        ];
    }
}
