<?php

declare(strict_types=1);

namespace Aurora\Config;

interface StorageInterface
{
    public function exists(string $name): bool;

    /** @return array<string, mixed>|false */
    public function read(string $name): array|false;

    /** @return array<string, array<string, mixed>> */
    public function readMultiple(array $names): array;

    public function write(string $name, array $data): bool;

    public function delete(string $name): bool;

    public function rename(string $name, string $newName): bool;

    /** @return string[] */
    public function listAll(string $prefix = ''): array;

    public function deleteAll(string $prefix = ''): bool;

    public function createCollection(string $collection): static;

    public function getCollectionName(): string;

    /** @return string[] */
    public function getAllCollectionNames(): array;
}
