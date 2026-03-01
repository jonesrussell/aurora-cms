<?php

declare(strict_types=1);

namespace Aurora\Queue;

use Aurora\Queue\Handler\HandlerInterface;
use Aurora\Queue\Handler\JobHandler;

final class SyncQueue implements QueueInterface
{
    /** @var HandlerInterface[] */
    private readonly array $handlers;

    private readonly AttributeGuard $guard;

    /**
     * @param HandlerInterface[] $handlers
     *   Additional handlers. A JobHandler is always prepended so that any
     *   Job subclass dispatched without a dedicated adapter has its handle()
     *   method invoked automatically.
     * @param AttributeGuard|null $guard
     *   Attribute enforcement guard. A default instance is created when null.
     */
    public function __construct(array $handlers = [], ?AttributeGuard $guard = null)
    {
        $this->handlers = [new JobHandler(), ...$handlers];
        $this->guard = $guard ?? new AttributeGuard();
    }

    public function dispatch(object $message): void
    {
        if (!$this->guard->allows($message)) {
            return;
        }

        foreach ($this->handlers as $handler) {
            if ($handler->supports($message)) {
                $handler->handle($message);
            }
        }
    }
}
