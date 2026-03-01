<?php

declare(strict_types=1);

namespace Waaseyaa\Queue\Handler;

interface HandlerInterface
{
    public function handle(object $message): void;

    public function supports(object $message): bool;
}
