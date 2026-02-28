<?php

declare(strict_types=1);

namespace Aurora\AI\Schema\Mcp;

use Aurora\Entity\EntityTypeManagerInterface;

/**
 * Generates MCP tool definitions for CRUD operations on entity types.
 *
 * For each registered entity type, five tools are generated:
 * - create_{type}: Create a new entity
 * - read_{type}: Read a single entity by ID
 * - update_{type}: Update an existing entity
 * - delete_{type}: Delete an entity by ID
 * - query_{type}: Query entities with filters, sort, and pagination
 */
final class McpToolGenerator
{
    public function __construct(
        private readonly EntityTypeManagerInterface $entityTypeManager,
    ) {}

    /**
     * Generate all MCP tools for a single entity type.
     *
     * @return McpToolDefinition[] Five tools: create, read, update, delete, query
     */
    public function generateForEntityType(string $entityTypeId): array
    {
        $entityType = $this->entityTypeManager->getDefinition($entityTypeId);
        $label = $entityType->getLabel();

        return [
            $this->createTool($entityTypeId, $label),
            $this->readTool($entityTypeId, $label),
            $this->updateTool($entityTypeId, $label),
            $this->deleteTool($entityTypeId, $label),
            $this->queryTool($entityTypeId, $label),
        ];
    }

    /**
     * Generate all MCP tools for all registered entity types.
     *
     * @return McpToolDefinition[] All tools across all entity types
     */
    public function generateAll(): array
    {
        $tools = [];

        foreach ($this->entityTypeManager->getDefinitions() as $id => $definition) {
            $tools = \array_merge($tools, $this->generateForEntityType($id));
        }

        return $tools;
    }

    private function createTool(string $entityTypeId, string $label): McpToolDefinition
    {
        return new McpToolDefinition(
            name: "create_{$entityTypeId}",
            description: "Create a new {$label} entity.",
            inputSchema: [
                'type' => 'object',
                'properties' => [
                    'attributes' => [
                        'type' => 'object',
                        'description' => "The attribute values for the new {$label}.",
                        'additionalProperties' => true,
                    ],
                ],
                'required' => ['attributes'],
                'additionalProperties' => false,
            ],
        );
    }

    private function readTool(string $entityTypeId, string $label): McpToolDefinition
    {
        return new McpToolDefinition(
            name: "read_{$entityTypeId}",
            description: "Read a single {$label} entity by its ID.",
            inputSchema: [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => ['integer', 'string'],
                        'description' => "The ID of the {$label} to read.",
                    ],
                ],
                'required' => ['id'],
                'additionalProperties' => false,
            ],
        );
    }

    private function updateTool(string $entityTypeId, string $label): McpToolDefinition
    {
        return new McpToolDefinition(
            name: "update_{$entityTypeId}",
            description: "Update an existing {$label} entity.",
            inputSchema: [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => ['integer', 'string'],
                        'description' => "The ID of the {$label} to update.",
                    ],
                    'attributes' => [
                        'type' => 'object',
                        'description' => "The attribute values to update on the {$label}.",
                        'additionalProperties' => true,
                    ],
                ],
                'required' => ['id', 'attributes'],
                'additionalProperties' => false,
            ],
        );
    }

    private function deleteTool(string $entityTypeId, string $label): McpToolDefinition
    {
        return new McpToolDefinition(
            name: "delete_{$entityTypeId}",
            description: "Delete a {$label} entity by its ID.",
            inputSchema: [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => ['integer', 'string'],
                        'description' => "The ID of the {$label} to delete.",
                    ],
                ],
                'required' => ['id'],
                'additionalProperties' => false,
            ],
        );
    }

    private function queryTool(string $entityTypeId, string $label): McpToolDefinition
    {
        return new McpToolDefinition(
            name: "query_{$entityTypeId}",
            description: "Query {$label} entities with optional filters, sorting, and pagination.",
            inputSchema: [
                'type' => 'object',
                'properties' => [
                    'filters' => [
                        'type' => 'array',
                        'description' => 'Conditions to filter results.',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'field' => [
                                    'type' => 'string',
                                    'description' => 'The field name to filter on.',
                                ],
                                'value' => [
                                    'description' => 'The value to compare against.',
                                ],
                                'operator' => [
                                    'type' => 'string',
                                    'description' => 'Comparison operator (=, !=, <, >, <=, >=, IN, CONTAINS, STARTS_WITH, ENDS_WITH).',
                                    'default' => '=',
                                ],
                            ],
                            'required' => ['field', 'value'],
                            'additionalProperties' => false,
                        ],
                    ],
                    'sort' => [
                        'type' => 'string',
                        'description' => 'Field name to sort by. Prefix with "-" for descending order.',
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Maximum number of results to return.',
                        'default' => 50,
                    ],
                    'offset' => [
                        'type' => 'integer',
                        'description' => 'Number of results to skip.',
                        'default' => 0,
                    ],
                ],
                'additionalProperties' => false,
            ],
        );
    }
}
