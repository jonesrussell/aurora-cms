<?php

declare(strict_types=1);

namespace Waaseyaa\Search\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Waaseyaa\Entity\Event\EntityEvent;
use Waaseyaa\Entity\Event\EntityEvents;
use Waaseyaa\Search\SearchIndexableInterface;
use Waaseyaa\Search\SearchIndexerInterface;

final class SearchIndexSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SearchIndexerInterface $indexer,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            EntityEvents::POST_SAVE->value => 'onPostSave',
            EntityEvents::POST_DELETE->value => 'onPostDelete',
        ];
    }

    public function onPostSave(EntityEvent $event): void
    {
        $entity = $event->entity;

        if (!$entity instanceof SearchIndexableInterface) {
            return;
        }

        try {
            $this->indexer->index($entity);
        } catch (\Throwable $e) {
            error_log("Search indexing failed for {$entity->getSearchDocumentId()}: {$e->getMessage()}");
        }
    }

    public function onPostDelete(EntityEvent $event): void
    {
        $entity = $event->entity;

        if (!$entity instanceof SearchIndexableInterface) {
            return;
        }

        try {
            $this->indexer->remove($entity->getSearchDocumentId());
        } catch (\Throwable $e) {
            error_log("Search index removal failed for {$entity->getSearchDocumentId()}: {$e->getMessage()}");
        }
    }
}
