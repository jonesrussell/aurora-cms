<?php

declare(strict_types=1);

namespace Waaseyaa\Queue;

interface QueueInterface
{
    public function dispatch(object $message): void;
}
