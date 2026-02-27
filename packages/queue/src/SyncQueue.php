<?php

declare(strict_types=1);

namespace Aurora\Queue;

use Aurora\Queue\Handler\HandlerInterface;

final class SyncQueue implements QueueInterface
{
    /** @var HandlerInterface[] */
    private readonly array $handlers;

    /** @param HandlerInterface[] $handlers */
    public function __construct(array $handlers = [])
    {
        $this->handlers = $handlers;
    }

    public function dispatch(object $message): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($message)) {
                $handler->handle($message);
            }
        }
    }
}
