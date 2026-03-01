<?php

declare(strict_types=1);

namespace Waaseyaa\AI\Schema\Tests\Unit;

use Waaseyaa\AI\Schema\EntityJsonSchemaGenerator;
use Waaseyaa\AI\Schema\Mcp\McpToolDefinition;
use Waaseyaa\AI\Schema\Mcp\McpToolGenerator;
use Waaseyaa\AI\Schema\SchemaRegistry;
use Waaseyaa\Entity\EntityType;
use Waaseyaa\Entity\EntityTypeManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SchemaRegistry::class)]
final class SchemaRegistryTest extends TestCase
{
    private EntityTypeManagerInterface $entityTypeManager;
    private SchemaRegistry $registry;

    protected function setUp(): void
    {
        $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

        $nodeType = new EntityType(
            id: 'node',
            label: 'Content',
            class: \stdClass::class,
            keys: ['id' => 'nid', 'uuid' => 'uuid', 'label' => 'title'],
        );

        $userType = new EntityType(
            id: 'user',
            label: 'User',
            class: \stdClass::class,
            keys: ['id' => 'uid', 'uuid' => 'uuid', 'label' => 'name'],
        );

        $this->entityTypeManager
            ->method('getDefinitions')
            ->willReturn(['node' => $nodeType, 'user' => $userType]);

        $this->entityTypeManager
            ->method('getDefinition')
            ->willReturnCallback(fn (string $id) => match ($id) {
                'node' => $nodeType,
                'user' => $userType,
                default => throw new \InvalidArgumentException("Entity type \"{$id}\" not found."),
            });

        $schemaGenerator = new EntityJsonSchemaGenerator($this->entityTypeManager);
        $toolGenerator = new McpToolGenerator($this->entityTypeManager);

        $this->registry = new SchemaRegistry($schemaGenerator, $toolGenerator);
    }

    #[Test]
    public function getSchemaReturnsCorrectSchema(): void
    {
        $schema = $this->registry->getSchema('node');

        $this->assertSame('Content', $schema['title']);
        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('nid', $schema['properties']);
    }

    #[Test]
    public function getAllSchemasReturnsAllSchemas(): void
    {
        $schemas = $this->registry->getAllSchemas();

        $this->assertCount(2, $schemas);
        $this->assertArrayHasKey('node', $schemas);
        $this->assertArrayHasKey('user', $schemas);
        $this->assertSame('Content', $schemas['node']['title']);
        $this->assertSame('User', $schemas['user']['title']);
    }

    #[Test]
    public function getToolsReturnsAllTools(): void
    {
        $tools = $this->registry->getTools();

        // 5 tools per entity type * 2 types = 10 tools.
        $this->assertCount(10, $tools);
        $this->assertContainsOnlyInstancesOf(McpToolDefinition::class, $tools);
    }

    #[Test]
    public function getToolReturnsSpecificTool(): void
    {
        $tool = $this->registry->getTool('create_node');

        $this->assertNotNull($tool);
        $this->assertSame('create_node', $tool->name);
        $this->assertStringContainsString('Content', $tool->description);
    }

    #[Test]
    public function getToolReturnsNullForUnknownTool(): void
    {
        $tool = $this->registry->getTool('nonexistent_tool');

        $this->assertNull($tool);
    }

    #[Test]
    public function getToolFindsToolsForDifferentEntityTypes(): void
    {
        $nodeTool = $this->registry->getTool('read_node');
        $userTool = $this->registry->getTool('delete_user');

        $this->assertNotNull($nodeTool);
        $this->assertNotNull($userTool);
        $this->assertSame('read_node', $nodeTool->name);
        $this->assertSame('delete_user', $userTool->name);
    }
}
