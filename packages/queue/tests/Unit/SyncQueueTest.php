<?php

declare(strict_types=1);

namespace Aurora\Queue\Tests\Unit;

use Aurora\Queue\Handler\HandlerInterface;
use Aurora\Queue\Message\EntityMessage;
use Aurora\Queue\Message\GenericMessage;
use Aurora\Queue\QueueInterface;
use Aurora\Queue\SyncQueue;
use PHPUnit\Framework\TestCase;

final class SyncQueueTest extends TestCase
{
    public function testImplementsQueueInterface(): void
    {
        $queue = new SyncQueue();

        $this->assertInstanceOf(QueueInterface::class, $queue);
    }

    public function testDispatchInvokesMatchingHandler(): void
    {
        $handled = [];

        $handler = $this->createMock(HandlerInterface::class);
        $handler->method('supports')->willReturn(true);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function (object $message) use (&$handled): void {
                $handled[] = $message;
            });

        $queue = new SyncQueue([$handler]);
        $message = new GenericMessage(type: 'test');

        $queue->dispatch($message);

        $this->assertCount(1, $handled);
        $this->assertSame($message, $handled[0]);
    }

    public function testDispatchSkipsNonMatchingHandlers(): void
    {
        $supportingHandler = $this->createMock(HandlerInterface::class);
        $supportingHandler->method('supports')->willReturn(true);
        $supportingHandler->expects($this->once())->method('handle');

        $nonSupportingHandler = $this->createMock(HandlerInterface::class);
        $nonSupportingHandler->method('supports')->willReturn(false);
        $nonSupportingHandler->expects($this->never())->method('handle');

        $queue = new SyncQueue([$nonSupportingHandler, $supportingHandler]);

        $queue->dispatch(new GenericMessage(type: 'test'));
    }

    public function testDispatchInvokesMultipleMatchingHandlers(): void
    {
        $handler1 = $this->createMock(HandlerInterface::class);
        $handler1->method('supports')->willReturn(true);
        $handler1->expects($this->once())->method('handle');

        $handler2 = $this->createMock(HandlerInterface::class);
        $handler2->method('supports')->willReturn(true);
        $handler2->expects($this->once())->method('handle');

        $queue = new SyncQueue([$handler1, $handler2]);

        $queue->dispatch(new EntityMessage(
            entityTypeId: 'node',
            entityId: 1,
            operation: 'insert',
        ));
    }

    public function testDispatchWithNoHandlers(): void
    {
        $queue = new SyncQueue();

        // Should not throw any exception.
        $queue->dispatch(new GenericMessage(type: 'orphan'));

        $this->assertTrue(true);
    }

    public function testHandlerFiltersByMessageType(): void
    {
        $entityHandler = $this->createMock(HandlerInterface::class);
        $entityHandler->method('supports')
            ->willReturnCallback(fn(object $msg): bool => $msg instanceof EntityMessage);
        $entityHandler->expects($this->once())->method('handle');

        $genericHandler = $this->createMock(HandlerInterface::class);
        $genericHandler->method('supports')
            ->willReturnCallback(fn(object $msg): bool => $msg instanceof GenericMessage);
        $genericHandler->expects($this->never())->method('handle');

        $queue = new SyncQueue([$entityHandler, $genericHandler]);

        $queue->dispatch(new EntityMessage(
            entityTypeId: 'node',
            entityId: 5,
            operation: 'update',
        ));
    }
}
