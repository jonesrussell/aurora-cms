<?php

declare(strict_types=1);

namespace Waaseyaa\AI\Schema\Tests\Unit\Mcp;

use Waaseyaa\AI\Schema\Mcp\McpToolDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(McpToolDefinition::class)]
final class McpToolDefinitionTest extends TestCase
{
    #[Test]
    public function constructorSetsProperties(): void
    {
        $inputSchema = [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
            ],
            'required' => ['id'],
        ];

        $tool = new McpToolDefinition(
            name: 'read_node',
            description: 'Read a content entity by ID.',
            inputSchema: $inputSchema,
        );

        $this->assertSame('read_node', $tool->name);
        $this->assertSame('Read a content entity by ID.', $tool->description);
        $this->assertSame($inputSchema, $tool->inputSchema);
    }

    #[Test]
    public function toArrayReturnsCorrectFormat(): void
    {
        $inputSchema = [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => ['integer', 'string']],
            ],
            'required' => ['id'],
        ];

        $tool = new McpToolDefinition(
            name: 'delete_node',
            description: 'Delete a content entity.',
            inputSchema: $inputSchema,
        );

        $array = $tool->toArray();

        $this->assertSame('delete_node', $array['name']);
        $this->assertSame('Delete a content entity.', $array['description']);
        $this->assertSame($inputSchema, $array['inputSchema']);
        $this->assertCount(3, $array);
    }

    #[Test]
    public function isReadonly(): void
    {
        $tool = new McpToolDefinition(
            name: 'test',
            description: 'Test tool.',
            inputSchema: [],
        );

        $reflection = new \ReflectionClass($tool);
        $this->assertTrue($reflection->isReadOnly());
    }
}
