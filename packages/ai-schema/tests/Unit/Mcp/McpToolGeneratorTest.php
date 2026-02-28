<?php

declare(strict_types=1);

namespace Aurora\AI\Schema\Tests\Unit\Mcp;

use Aurora\AI\Schema\Mcp\McpToolDefinition;
use Aurora\AI\Schema\Mcp\McpToolGenerator;
use Aurora\Entity\EntityType;
use Aurora\Entity\EntityTypeManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(McpToolGenerator::class)]
final class McpToolGeneratorTest extends TestCase
{
    private EntityTypeManagerInterface $entityTypeManager;

    protected function setUp(): void
    {
        $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    }

    #[Test]
    public function generateForEntityTypeProducesFiveTools(): void
    {
        $entityType = new EntityType(
            id: 'node',
            label: 'Content',
            class: \stdClass::class,
        );

        $this->entityTypeManager
            ->method('getDefinition')
            ->with('node')
            ->willReturn($entityType);

        $generator = new McpToolGenerator($this->entityTypeManager);
        $tools = $generator->generateForEntityType('node');

        $this->assertCount(5, $tools);
        $this->assertContainsOnlyInstancesOf(McpToolDefinition::class, $tools);
    }

    #[Test]
    public function toolNamesFollowConvention(): void
    {
        $entityType = new EntityType(
            id: 'node',
            label: 'Content',
            class: \stdClass::class,
        );

        $this->entityTypeManager
            ->method('getDefinition')
            ->with('node')
            ->willReturn($entityType);

        $generator = new McpToolGenerator($this->entityTypeManager);
        $tools = $generator->generateForEntityType('node');

        $names = \array_map(fn (McpToolDefinition $t) => $t->name, $tools);

        $this->assertContains('create_node', $names);
        $this->assertContains('read_node', $names);
        $this->assertContains('update_node', $names);
        $this->assertContains('delete_node', $names);
        $this->assertContains('query_node', $names);
    }

    #[Test]
    public function toolNamesWorkWithUnderscoreEntityTypes(): void
    {
        $entityType = new EntityType(
            id: 'taxonomy_term',
            label: 'Taxonomy Term',
            class: \stdClass::class,
        );

        $this->entityTypeManager
            ->method('getDefinition')
            ->with('taxonomy_term')
            ->willReturn($entityType);

        $generator = new McpToolGenerator($this->entityTypeManager);
        $tools = $generator->generateForEntityType('taxonomy_term');

        $names = \array_map(fn (McpToolDefinition $t) => $t->name, $tools);

        $this->assertContains('create_taxonomy_term', $names);
        $this->assertContains('read_taxonomy_term', $names);
        $this->assertContains('update_taxonomy_term', $names);
        $this->assertContains('delete_taxonomy_term', $names);
        $this->assertContains('query_taxonomy_term', $names);
    }

    #[Test]
    public function createToolHasCorrectInputSchema(): void
    {
        $entityType = new EntityType(
            id: 'node',
            label: 'Content',
            class: \stdClass::class,
        );

        $this->entityTypeManager
            ->method('getDefinition')
            ->with('node')
            ->willReturn($entityType);

        $generator = new McpToolGenerator($this->entityTypeManager);
        $tools = $generator->generateForEntityType('node');

        $createTool = $this->findToolByName($tools, 'create_node');
        $this->assertNotNull($createTool);

        $schema = $createTool->inputSchema;
        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('attributes', $schema['properties']);
        $this->assertSame(['attributes'], $schema['required']);
    }

    #[Test]
    public function readToolHasCorrectInputSchema(): void
    {
        $entityType = new EntityType(
            id: 'node',
            label: 'Content',
            class: \stdClass::class,
        );

        $this->entityTypeManager
            ->method('getDefinition')
            ->with('node')
            ->willReturn($entityType);

        $generator = new McpToolGenerator($this->entityTypeManager);
        $tools = $generator->generateForEntityType('node');

        $readTool = $this->findToolByName($tools, 'read_node');
        $this->assertNotNull($readTool);

        $schema = $readTool->inputSchema;
        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('id', $schema['properties']);
        $this->assertSame(['integer', 'string'], $schema['properties']['id']['type']);
        $this->assertSame(['id'], $schema['required']);
    }

    #[Test]
    public function updateToolHasCorrectInputSchema(): void
    {
        $entityType = new EntityType(
            id: 'node',
            label: 'Content',
            class: \stdClass::class,
        );

        $this->entityTypeManager
            ->method('getDefinition')
            ->with('node')
            ->willReturn($entityType);

        $generator = new McpToolGenerator($this->entityTypeManager);
        $tools = $generator->generateForEntityType('node');

        $updateTool = $this->findToolByName($tools, 'update_node');
        $this->assertNotNull($updateTool);

        $schema = $updateTool->inputSchema;
        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('id', $schema['properties']);
        $this->assertArrayHasKey('attributes', $schema['properties']);
        $this->assertContains('id', $schema['required']);
        $this->assertContains('attributes', $schema['required']);
    }

    #[Test]
    public function deleteToolHasCorrectInputSchema(): void
    {
        $entityType = new EntityType(
            id: 'node',
            label: 'Content',
            class: \stdClass::class,
        );

        $this->entityTypeManager
            ->method('getDefinition')
            ->with('node')
            ->willReturn($entityType);

        $generator = new McpToolGenerator($this->entityTypeManager);
        $tools = $generator->generateForEntityType('node');

        $deleteTool = $this->findToolByName($tools, 'delete_node');
        $this->assertNotNull($deleteTool);

        $schema = $deleteTool->inputSchema;
        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('id', $schema['properties']);
        $this->assertSame(['id'], $schema['required']);
    }

    #[Test]
    public function queryToolHasCorrectInputSchema(): void
    {
        $entityType = new EntityType(
            id: 'node',
            label: 'Content',
            class: \stdClass::class,
        );

        $this->entityTypeManager
            ->method('getDefinition')
            ->with('node')
            ->willReturn($entityType);

        $generator = new McpToolGenerator($this->entityTypeManager);
        $tools = $generator->generateForEntityType('node');

        $queryTool = $this->findToolByName($tools, 'query_node');
        $this->assertNotNull($queryTool);

        $schema = $queryTool->inputSchema;
        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('filters', $schema['properties']);
        $this->assertArrayHasKey('sort', $schema['properties']);
        $this->assertArrayHasKey('limit', $schema['properties']);
        $this->assertArrayHasKey('offset', $schema['properties']);
        $this->assertSame('array', $schema['properties']['filters']['type']);
        $this->assertSame('integer', $schema['properties']['limit']['type']);
        $this->assertSame(50, $schema['properties']['limit']['default']);
        $this->assertSame(0, $schema['properties']['offset']['default']);
    }

    #[Test]
    public function toolDescriptionsAreMeaningful(): void
    {
        $entityType = new EntityType(
            id: 'node',
            label: 'Content',
            class: \stdClass::class,
        );

        $this->entityTypeManager
            ->method('getDefinition')
            ->with('node')
            ->willReturn($entityType);

        $generator = new McpToolGenerator($this->entityTypeManager);
        $tools = $generator->generateForEntityType('node');

        foreach ($tools as $tool) {
            $this->assertNotEmpty($tool->description);
            $this->assertStringContainsString('Content', $tool->description);
        }
    }

    #[Test]
    public function generateAllProducesToolsForAllEntityTypes(): void
    {
        $nodeType = new EntityType(
            id: 'node',
            label: 'Content',
            class: \stdClass::class,
        );

        $userType = new EntityType(
            id: 'user',
            label: 'User',
            class: \stdClass::class,
        );

        $this->entityTypeManager
            ->method('getDefinitions')
            ->willReturn(['node' => $nodeType, 'user' => $userType]);

        $this->entityTypeManager
            ->method('getDefinition')
            ->willReturnCallback(fn (string $id) => match ($id) {
                'node' => $nodeType,
                'user' => $userType,
            });

        $generator = new McpToolGenerator($this->entityTypeManager);
        $tools = $generator->generateAll();

        // 5 tools per entity type * 2 entity types = 10 tools.
        $this->assertCount(10, $tools);

        $names = \array_map(fn (McpToolDefinition $t) => $t->name, $tools);
        $this->assertContains('create_node', $names);
        $this->assertContains('read_user', $names);
        $this->assertContains('query_user', $names);
    }

    /**
     * @param McpToolDefinition[] $tools
     */
    private function findToolByName(array $tools, string $name): ?McpToolDefinition
    {
        foreach ($tools as $tool) {
            if ($tool->name === $name) {
                return $tool;
            }
        }

        return null;
    }
}
