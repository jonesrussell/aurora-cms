<?php

declare(strict_types=1);

namespace Aurora\Queue;

interface QueueInterface
{
    public function dispatch(object $message): void;
}
