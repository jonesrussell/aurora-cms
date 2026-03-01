<?php

declare(strict_types=1);

namespace Waaseyaa\Foundation\Event\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Broadcast
{
    public function __construct(
        public readonly string $channel,
    ) {}
}
