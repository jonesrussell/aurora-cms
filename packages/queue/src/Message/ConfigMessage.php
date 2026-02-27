<?php

declare(strict_types=1);

namespace Aurora\Queue\Message;

final readonly class ConfigMessage
{
    public function __construct(
        public string $configName,
        public string $operation,
        public array $data = [],
    ) {}
}
