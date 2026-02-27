<?php

declare(strict_types=1);

namespace Aurora\Queue\Tests\Unit\Message;

use Aurora\Queue\Message\EntityMessage;
use PHPUnit\Framework\TestCase;

final class EntityMessageTest extends TestCase
{
    public function testConstructWithAllProperties(): void
    {
        $message = new EntityMessage(
            entityTypeId: 'node',
            entityId: 42,
            operation: 'insert',
            data: ['title' => 'Hello World'],
        );

        $this->assertSame('node', $message->entityTypeId);
        $this->assertSame(42, $message->entityId);
        $this->assertSame('insert', $message->operation);
        $this->assertSame(['title' => 'Hello World'], $message->data);
    }

    public function testConstructWithStringEntityId(): void
    {
        $message = new EntityMessage(
            entityTypeId: 'taxonomy_term',
            entityId: 'abc-123',
            operation: 'update',
        );

        $this->assertSame('taxonomy_term', $message->entityTypeId);
        $this->assertSame('abc-123', $message->entityId);
        $this->assertSame('update', $message->operation);
        $this->assertSame([], $message->data);
    }

    public function testConstructWithDefaults(): void
    {
        $message = new EntityMessage(
            entityTypeId: 'user',
            entityId: 1,
            operation: 'delete',
        );

        $this->assertSame([], $message->data);
    }
}
