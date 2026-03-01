<?php

declare(strict_types=1);

namespace Waaseyaa\Foundation\Event;

interface BroadcasterInterface
{
    public function broadcast(DomainEvent $event): void;
}
