<?php

declare(strict_types=1);

namespace Waaseyaa\Queue;

use Symfony\Component\Messenger\MessageBusInterface;

final class MessageBusQueue implements QueueInterface
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {}

    public function dispatch(object $message): void
    {
        $this->messageBus->dispatch($message);
    }
}
