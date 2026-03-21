<?php

declare(strict_types=1);

namespace Waaseyaa\Search;

interface SearchIndexerInterface
{
    /**
     * Index a single item. Upserts — replaces existing document with same ID.
     */
    public function index(SearchIndexableInterface $item): void;

    /**
     * Remove a single document by ID.
     */
    public function remove(string $documentId): void;

    /**
     * Remove all documents from the index.
     */
    public function removeAll(): void;

    /**
     * Current schema version. Changes when the indexable contract evolves.
     */
    public function getSchemaVersion(): string;
}
