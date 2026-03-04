<?php

declare(strict_types=1);

namespace Waaseyaa\AI\Vector;

use Waaseyaa\Entity\Event\EntityEvent;
use Waaseyaa\Queue\Message\GenericMessage;
use Waaseyaa\Queue\QueueInterface;

final class EntityEmbeddingListener
{
    public function __construct(
        private readonly ?QueueInterface $queue = null,
        private readonly ?EmbeddingStorageInterface $storage = null,
        private readonly ?EmbeddingProviderInterface $embeddingProvider = null,
    ) {}

    public function onPostSave(EntityEvent $event): void
    {
        $entityId = $event->entity->id();
        if ($entityId === null || $entityId === '') {
            return;
        }

        $entityType = $event->entity->getEntityTypeId();
        $entityIdString = (string) $entityId;

        if (!$this->isIndexable($event)) {
            if ($this->storage !== null) {
                $this->storage->delete($entityType, $entityIdString);
            }
            return;
        }

        if ($this->storage !== null && $this->embeddingProvider !== null) {
            try {
                $vector = $this->embeddingProvider->embed($this->buildEmbeddingText($event));
                $this->storage->store($entityType, $entityIdString, $vector);
            } catch (\Throwable $exception) {
                error_log(sprintf(
                    '[Waaseyaa] Embedding update failed for %s:%s: %s',
                    $entityType,
                    $entityIdString,
                    $exception->getMessage(),
                ));
            }
        }

        if ($this->queue === null) {
            return;
        }

        $this->queue->dispatch(new GenericMessage(
            type: 'ai_vector.embed_entity',
            payload: [
                'entity_type' => $entityType,
                'entity_id' => $entityIdString,
                'langcode' => $event->entity->language(),
            ],
        ));
    }

    private function isIndexable(EntityEvent $event): bool
    {
        if ($event->entity->getEntityTypeId() !== 'node') {
            return true;
        }

        $values = $event->entity->toArray();
        $state = $this->normalizeWorkflowState($values['workflow_state'] ?? null, $values['status'] ?? 0);

        return $state === 'published';
    }

    private function buildEmbeddingText(EntityEvent $event): string
    {
        $values = $event->entity->toArray();
        $parts = [];

        foreach (['title', 'name', 'body', 'description'] as $field) {
            $value = $values[$field] ?? null;
            if (\is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed !== '') {
                    $parts[] = $trimmed;
                }
            }
        }

        $label = trim($event->entity->label());
        if ($label !== '') {
            array_unshift($parts, $label);
        }

        $parts = array_values(array_unique($parts));
        if ($parts === []) {
            return sprintf(
                '%s %s',
                $event->entity->getEntityTypeId(),
                (string) ($event->entity->id() ?? ''),
            );
        }

        return implode("\n\n", $parts);
    }

    private function normalizeWorkflowState(mixed $workflowState, mixed $status): string
    {
        if (is_string($workflowState) && trim($workflowState) !== '') {
            return strtolower(trim($workflowState));
        }
        if (is_bool($status)) {
            return $status ? 'published' : 'draft';
        }
        if (is_numeric($status)) {
            return ((int) $status) === 1 ? 'published' : 'draft';
        }
        if (is_string($status)) {
            $normalized = strtolower(trim($status));
            if (in_array($normalized, ['1', 'true', 'published'], true)) {
                return 'published';
            }
        }

        return 'draft';
    }
}
