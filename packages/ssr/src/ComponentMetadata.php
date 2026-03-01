<?php

declare(strict_types=1);

namespace Waaseyaa\SSR;

final readonly class ComponentMetadata
{
    public function __construct(
        public string $name,
        public string $template,
        public string $className,
    ) {}
}
