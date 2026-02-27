<?php

declare(strict_types=1);

namespace Aurora\Queue\Message;

final readonly class GenericMessage
{
    public function __construct(
        public string $type,
        public array $payload = [],
    ) {}
}
