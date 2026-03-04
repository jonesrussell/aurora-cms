<?php

declare(strict_types=1);

namespace Waaseyaa\AI\Vector;

use Waaseyaa\Entity\Event\EntityEvent;
use Waaseyaa\Queue\Message\GenericMessage;
use Waaseyaa\Queue\QueueInterface;

final class EntityEmbeddingListener
{
    public function __construct(
        private readonly QueueInterface $queue,
    ) {}

    public function onPostSave(EntityEvent $event): void
    {
        $entityId = $event->entity->id();
        if ($entityId === null || $entityId === '') {
            return;
        }

        $this->queue->dispatch(new GenericMessage(
            type: 'ai_vector.embed_entity',
            payload: [
                'entity_type' => $event->entity->getEntityTypeId(),
                'entity_id' => (string) $entityId,
                'langcode' => $event->entity->language(),
            ],
        ));
    }
}
