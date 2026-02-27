<?php

declare(strict_types=1);

namespace Aurora\Database;

interface DatabaseInterface
{
    public function select(string $table, string $alias = ''): SelectInterface;

    public function insert(string $table): InsertInterface;

    public function update(string $table): UpdateInterface;

    public function delete(string $table): DeleteInterface;

    public function schema(): SchemaInterface;

    public function transaction(string $name = ''): TransactionInterface;

    /** @return \Traversable */
    public function query(string $sql, array $args = []): \Traversable;
}
