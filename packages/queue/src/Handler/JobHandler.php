<?php

declare(strict_types=1);

namespace Aurora\Queue\Handler;

use Aurora\Queue\Job;

/**
 * Bridges HandlerInterface to Job::handle().
 *
 * This default handler is automatically prepended by SyncQueue so that any
 * Job subclass dispatched without a dedicated handler adapter will have its
 * handle() method called correctly, with the attempt counter incremented.
 */
final class JobHandler implements HandlerInterface
{
    public function supports(object $message): bool
    {
        return $message instanceof Job;
    }

    public function handle(object $message): void
    {
        /** @var Job $message */
        $message->incrementAttempts();
        $message->handle();
    }
}
