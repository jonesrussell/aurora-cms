<?php

declare(strict_types=1);

namespace Aurora\Plugin\Definition;

final readonly class PluginDefinition
{
    public function __construct(
        public string $id,
        public string $label,
        public string $class,
        public string $description = '',
        public string $package = '',
        public array $metadata = [],
    ) {}
}
