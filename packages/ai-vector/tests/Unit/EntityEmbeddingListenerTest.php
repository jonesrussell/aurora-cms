<?php

declare(strict_types=1);

namespace Waaseyaa\AI\Vector\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\AI\Vector\EntityEmbeddingListener;
use Waaseyaa\Entity\EntityInterface;
use Waaseyaa\Entity\Event\EntityEvent;
use Waaseyaa\Queue\Message\GenericMessage;
use Waaseyaa\Queue\QueueInterface;

#[CoversClass(EntityEmbeddingListener::class)]
final class EntityEmbeddingListenerTest extends TestCase
{
    #[Test]
    public function dispatchesEmbeddingMessageOnPostSave(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (object $message): bool {
                if (!$message instanceof GenericMessage) {
                    return false;
                }

                return $message->type === 'ai_vector.embed_entity'
                    && ($message->payload['entity_type'] ?? null) === 'node'
                    && ($message->payload['entity_id'] ?? null) === '42'
                    && ($message->payload['langcode'] ?? null) === 'en';
            }));

        $listener = new EntityEmbeddingListener($queue);
        $listener->onPostSave(new EntityEvent(new TestEmbeddingEntity(42, 'node')));
    }

    #[Test]
    public function skipsDispatchWhenEntityIdIsMissing(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->never())->method('dispatch');

        $listener = new EntityEmbeddingListener($queue);
        $listener->onPostSave(new EntityEvent(new TestEmbeddingEntity(null, 'node')));
    }
}

final readonly class TestEmbeddingEntity implements EntityInterface
{
    public function __construct(
        private int|string|null $id,
        private string $entityTypeId,
    ) {}

    public function id(): int|string|null { return $this->id; }
    public function uuid(): string { return 'uuid'; }
    public function label(): string { return 'Label'; }
    public function getEntityTypeId(): string { return $this->entityTypeId; }
    public function bundle(): string { return 'default'; }
    public function isNew(): bool { return false; }
    public function toArray(): array { return []; }
    public function language(): string { return 'en'; }
}
