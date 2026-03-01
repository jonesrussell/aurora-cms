<?php

declare(strict_types=1);

namespace Waaseyaa\Foundation\Event;

interface EventStoreInterface
{
    public function append(DomainEvent $event): void;
}
