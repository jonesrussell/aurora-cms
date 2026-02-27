<?php

declare(strict_types=1);

namespace Aurora\Database;

interface UpdateInterface
{
    public function fields(array $fields): static;

    public function condition(string $field, mixed $value, string $operator = '='): static;

    public function execute(): int;
}
