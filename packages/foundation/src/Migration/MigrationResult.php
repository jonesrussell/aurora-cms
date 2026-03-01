<?php

declare(strict_types=1);

namespace Waaseyaa\Foundation\Migration;

final readonly class MigrationResult
{
    public function __construct(
        public int $count,
        public array $migrations = [],
    ) {}
}
