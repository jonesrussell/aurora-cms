<?php

declare(strict_types=1);

namespace Waaseyaa\Config;

final readonly class ConfigImportResult
{
    public function __construct(
        /** @var string[] */
        public array $created = [],
        /** @var string[] */
        public array $updated = [],
        /** @var string[] */
        public array $deleted = [],
        /** @var string[] */
        public array $errors = [],
    ) {}

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }
}
