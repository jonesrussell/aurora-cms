<?php

declare(strict_types=1);

namespace Aurora\AI\Schema\Tests\Unit\Mcp;

use Aurora\AI\Schema\Mcp\McpToolExecutor;
use Aurora\Entity\ContentEntityBase;
use Aurora\Entity\EntityInterface;
use Aurora\Entity\EntityTypeManagerInterface;
use Aurora\Entity\Storage\EntityQueryInterface;
use Aurora\Entity\Storage\EntityStorageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(McpToolExecutor::class)]
final class McpToolExecutorTest extends TestCase
{
    private EntityTypeManagerInterface $entityTypeManager;
    private EntityStorageInterface $storage;
    private EntityQueryInterface $query;

    protected function setUp(): void
    {
        $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
        $this->storage = $this->createMock(EntityStorageInterface::class);
        $this->query = $this->createMock(EntityQueryInterface::class);

        $this->entityTypeManager
            ->method('hasDefinition')
            ->willReturnCallback(fn (string $id) => $id === 'node' || $id === 'user');

        $this->entityTypeManager
            ->method('getStorage')
            ->willReturn($this->storage);

        $this->storage
            ->method('getQuery')
            ->willReturn($this->query);
    }

    #[Test]
    public function createEntitySuccessfully(): void
    {
        $entity = $this->createTestEntity(['nid' => 1, 'title' => 'Test']);

        $this->storage
            ->method('create')
            ->with(['title' => 'Test', 'type' => 'article'])
            ->willReturn($entity);

        $this->storage
            ->expects($this->once())
            ->method('save')
            ->with($entity);

        $executor = new McpToolExecutor($this->entityTypeManager);
        $result = $executor->execute('create_node', [
            'attributes' => ['title' => 'Test', 'type' => 'article'],
        ]);

        $this->assertArrayNotHasKey('isError', $result);
        $this->assertCount(1, $result['content']);
        $this->assertSame('text', $result['content'][0]['type']);

        $data = \json_decode($result['content'][0]['text'], true);
        $this->assertSame('create', $data['operation']);
        $this->assertSame('node', $data['entity_type']);
        $this->assertSame(1, $data['id']);
    }

    #[Test]
    public function readEntitySuccessfully(): void
    {
        $entity = $this->createTestEntity(['nid' => 42, 'title' => 'Hello']);

        $this->storage
            ->method('load')
            ->with(42)
            ->willReturn($entity);

        $executor = new McpToolExecutor($this->entityTypeManager);
        $result = $executor->execute('read_node', ['id' => 42]);

        $this->assertArrayNotHasKey('isError', $result);

        $data = \json_decode($result['content'][0]['text'], true);
        $this->assertSame('read', $data['operation']);
        $this->assertSame('node', $data['entity_type']);
        $this->assertSame(42, $data['id']);
    }

    #[Test]
    public function readEntityNotFound(): void
    {
        $this->storage
            ->method('load')
            ->with(999)
            ->willReturn(null);

        $executor = new McpToolExecutor($this->entityTypeManager);
        $result = $executor->execute('read_node', ['id' => 999]);

        $this->assertTrue($result['isError']);

        $data = \json_decode($result['content'][0]['text'], true);
        $this->assertStringContainsString('not found', $data['error']);
    }

    #[Test]
    public function updateEntitySuccessfully(): void
    {
        $entity = new class(
            values: ['nid' => 10, 'title' => 'Old'],
            entityTypeId: 'node',
            entityKeys: ['id' => 'nid', 'uuid' => 'uuid', 'label' => 'title'],
        ) extends ContentEntityBase {};

        $this->storage
            ->method('load')
            ->with(10)
            ->willReturn($entity);

        $this->storage
            ->expects($this->once())
            ->method('save')
            ->with($entity);

        $executor = new McpToolExecutor($this->entityTypeManager);
        $result = $executor->execute('update_node', [
            'id' => 10,
            'attributes' => ['title' => 'Updated'],
        ]);

        $this->assertArrayNotHasKey('isError', $result);

        $data = \json_decode($result['content'][0]['text'], true);
        $this->assertSame('update', $data['operation']);
        $this->assertSame(10, $data['id']);
        // The entity should have the updated value.
        $this->assertSame('Updated', $data['data']['title']);
    }

    #[Test]
    public function updateEntityNotFound(): void
    {
        $this->storage
            ->method('load')
            ->with(999)
            ->willReturn(null);

        $executor = new McpToolExecutor($this->entityTypeManager);
        $result = $executor->execute('update_node', [
            'id' => 999,
            'attributes' => ['title' => 'New'],
        ]);

        $this->assertTrue($result['isError']);

        $data = \json_decode($result['content'][0]['text'], true);
        $this->assertStringContainsString('not found', $data['error']);
    }

    #[Test]
    public function deleteEntitySuccessfully(): void
    {
        $entity = $this->createTestEntity(['nid' => 5, 'title' => 'To Delete']);

        $this->storage
            ->method('load')
            ->with(5)
            ->willReturn($entity);

        $this->storage
            ->expects($this->once())
            ->method('delete')
            ->with([$entity]);

        $executor = new McpToolExecutor($this->entityTypeManager);
        $result = $executor->execute('delete_node', ['id' => 5]);

        $this->assertArrayNotHasKey('isError', $result);

        $data = \json_decode($result['content'][0]['text'], true);
        $this->assertSame('delete', $data['operation']);
        $this->assertSame(5, $data['id']);
    }

    #[Test]
    public function deleteEntityNotFound(): void
    {
        $this->storage
            ->method('load')
            ->with(999)
            ->willReturn(null);

        $executor = new McpToolExecutor($this->entityTypeManager);
        $result = $executor->execute('delete_node', ['id' => 999]);

        $this->assertTrue($result['isError']);
    }

    #[Test]
    public function queryEntitiesSuccessfully(): void
    {
        $entity1 = $this->createTestEntity(['nid' => 1, 'title' => 'First']);
        $entity2 = $this->createTestEntity(['nid' => 2, 'title' => 'Second']);

        $this->query->method('accessCheck')->willReturnSelf();
        $this->query->method('condition')->willReturnSelf();
        $this->query->method('sort')->willReturnSelf();
        $this->query->method('range')->willReturnSelf();
        $this->query->method('execute')->willReturn([1, 2]);

        $this->storage
            ->method('loadMultiple')
            ->with([1, 2])
            ->willReturn([1 => $entity1, 2 => $entity2]);

        $executor = new McpToolExecutor($this->entityTypeManager);
        $result = $executor->execute('query_node', [
            'filters' => [
                ['field' => 'type', 'value' => 'article'],
            ],
            'sort' => '-created',
            'limit' => 10,
            'offset' => 0,
        ]);

        $this->assertArrayNotHasKey('isError', $result);

        $data = \json_decode($result['content'][0]['text'], true);
        $this->assertSame('query', $data['operation']);
        $this->assertSame('node', $data['entity_type']);
        $this->assertSame(2, $data['count']);
        $this->assertCount(2, $data['results']);
    }

    #[Test]
    public function queryEntitiesWithNoResults(): void
    {
        $this->query->method('accessCheck')->willReturnSelf();
        $this->query->method('range')->willReturnSelf();
        $this->query->method('execute')->willReturn([]);

        $executor = new McpToolExecutor($this->entityTypeManager);
        $result = $executor->execute('query_node', []);

        $this->assertArrayNotHasKey('isError', $result);

        $data = \json_decode($result['content'][0]['text'], true);
        $this->assertSame(0, $data['count']);
        $this->assertSame([], $data['results']);
    }

    #[Test]
    public function queryAppliesFiltersAndSorting(): void
    {
        $this->query->method('accessCheck')->willReturnSelf();
        $this->query->method('range')->willReturnSelf();
        $this->query->method('execute')->willReturn([]);

        $this->query
            ->expects($this->once())
            ->method('condition')
            ->with('status', 1, '=');

        $this->query
            ->expects($this->once())
            ->method('sort')
            ->with('created', 'DESC');

        $executor = new McpToolExecutor($this->entityTypeManager);
        $executor->execute('query_node', [
            'filters' => [
                ['field' => 'status', 'value' => 1],
            ],
            'sort' => '-created',
        ]);
    }

    #[Test]
    public function unknownToolReturnsError(): void
    {
        $executor = new McpToolExecutor($this->entityTypeManager);
        $result = $executor->execute('unknown_tool', []);

        $this->assertTrue($result['isError']);

        $data = \json_decode($result['content'][0]['text'], true);
        $this->assertStringContainsString('Unknown tool', $data['error']);
    }

    #[Test]
    public function unknownEntityTypeReturnsError(): void
    {
        $executor = new McpToolExecutor($this->entityTypeManager);
        $result = $executor->execute('create_nonexistent', []);

        $this->assertTrue($result['isError']);

        $data = \json_decode($result['content'][0]['text'], true);
        $this->assertStringContainsString('Unknown entity type', $data['error']);
    }

    #[Test]
    public function readWithoutIdReturnsError(): void
    {
        $executor = new McpToolExecutor($this->entityTypeManager);
        $result = $executor->execute('read_node', []);

        $this->assertTrue($result['isError']);

        $data = \json_decode($result['content'][0]['text'], true);
        $this->assertStringContainsString('id', $data['error']);
    }

    #[Test]
    public function updateWithoutIdReturnsError(): void
    {
        $executor = new McpToolExecutor($this->entityTypeManager);
        $result = $executor->execute('update_node', ['attributes' => ['title' => 'X']]);

        $this->assertTrue($result['isError']);

        $data = \json_decode($result['content'][0]['text'], true);
        $this->assertStringContainsString('id', $data['error']);
    }

    #[Test]
    public function updateWithoutAttributesReturnsError(): void
    {
        $executor = new McpToolExecutor($this->entityTypeManager);
        $result = $executor->execute('update_node', ['id' => 1]);

        $this->assertTrue($result['isError']);

        $data = \json_decode($result['content'][0]['text'], true);
        $this->assertStringContainsString('attributes', $data['error']);
    }

    #[Test]
    public function deleteWithoutIdReturnsError(): void
    {
        $executor = new McpToolExecutor($this->entityTypeManager);
        $result = $executor->execute('delete_node', []);

        $this->assertTrue($result['isError']);

        $data = \json_decode($result['content'][0]['text'], true);
        $this->assertStringContainsString('id', $data['error']);
    }

    #[Test]
    public function resultFormatMatchesMcpSpec(): void
    {
        $entity = $this->createTestEntity(['nid' => 1, 'title' => 'Test']);

        $this->storage
            ->method('load')
            ->with(1)
            ->willReturn($entity);

        $executor = new McpToolExecutor($this->entityTypeManager);
        $result = $executor->execute('read_node', ['id' => 1]);

        // Verify MCP result structure.
        $this->assertArrayHasKey('content', $result);
        $this->assertIsArray($result['content']);
        $this->assertCount(1, $result['content']);
        $this->assertSame('text', $result['content'][0]['type']);
        $this->assertIsString($result['content'][0]['text']);

        // The text should be valid JSON.
        $decoded = \json_decode($result['content'][0]['text'], true);
        $this->assertNotNull($decoded);
    }

    #[Test]
    public function errorResultFormatMatchesMcpSpec(): void
    {
        $executor = new McpToolExecutor($this->entityTypeManager);
        $result = $executor->execute('unknown_xyz', []);

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('isError', $result);
        $this->assertTrue($result['isError']);
        $this->assertSame('text', $result['content'][0]['type']);
    }

    private function createTestEntity(array $values): EntityInterface
    {
        $entity = $this->createMock(EntityInterface::class);
        $entity->method('id')->willReturn($values['nid'] ?? $values['id'] ?? null);
        $entity->method('toArray')->willReturn($values);
        $entity->method('uuid')->willReturn($values['uuid'] ?? 'test-uuid');
        $entity->method('label')->willReturn($values['title'] ?? $values['name'] ?? '');
        $entity->method('getEntityTypeId')->willReturn('node');

        return $entity;
    }
}
