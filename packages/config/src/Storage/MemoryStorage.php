<?php

declare(strict_types=1);

namespace Aurora\Config\Storage;

use Aurora\Config\StorageInterface;

final class MemoryStorage implements StorageInterface
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $data = [];

    public function __construct(
        private readonly string $collection = '',
    ) {}

    public function exists(string $name): bool
    {
        return isset($this->data[$name]);
    }

    public function read(string $name): array|false
    {
        return $this->data[$name] ?? false;
    }

    public function readMultiple(array $names): array
    {
        $result = [];
        foreach ($names as $name) {
            $data = $this->read($name);
            if ($data !== false) {
                $result[$name] = $data;
            }
        }

        return $result;
    }

    public function write(string $name, array $data): bool
    {
        $this->data[$name] = $data;

        return true;
    }

    public function delete(string $name): bool
    {
        if (!$this->exists($name)) {
            return false;
        }

        unset($this->data[$name]);

        return true;
    }

    public function rename(string $name, string $newName): bool
    {
        if (!$this->exists($name)) {
            return false;
        }

        $this->data[$newName] = $this->data[$name];
        unset($this->data[$name]);

        return true;
    }

    public function listAll(string $prefix = ''): array
    {
        $names = array_keys($this->data);

        if ($prefix === '') {
            sort($names);

            return $names;
        }

        $filtered = array_filter($names, static fn(string $name): bool => str_starts_with($name, $prefix));
        $filtered = array_values($filtered);
        sort($filtered);

        return $filtered;
    }

    public function deleteAll(string $prefix = ''): bool
    {
        if ($prefix === '') {
            $this->data = [];

            return true;
        }

        foreach (array_keys($this->data) as $name) {
            if (str_starts_with($name, $prefix)) {
                unset($this->data[$name]);
            }
        }

        return true;
    }

    public function createCollection(string $collection): static
    {
        return new self($collection);
    }

    public function getCollectionName(): string
    {
        return $this->collection;
    }

    public function getAllCollectionNames(): array
    {
        return [];
    }
}
