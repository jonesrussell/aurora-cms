<?php

declare(strict_types=1);

namespace Aurora\Config\Ownership;

final readonly class PackageOwnership
{
    public function __construct(
        public string $configName,
        public string $packageName,
        public string $versionConstraint,
        /** @var string[] */
        public array $dependencies = [],
    ) {}
}
