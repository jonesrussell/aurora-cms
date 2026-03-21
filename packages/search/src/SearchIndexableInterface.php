<?php

declare(strict_types=1);

namespace Waaseyaa\Search;

interface SearchIndexableInterface
{
    /**
     * Unique document ID across all entity types (e.g., "node:42", "user:7").
     */
    public function getSearchDocumentId(): string;

    /**
     * Searchable text fields — keys are field names, values are text content.
     *
     * @return array<string, string> e.g. ['title' => '...', 'body' => '...']
     */
    public function toSearchDocument(): array;

    /**
     * Structured metadata for filtering and faceting.
     *
     * @return array<string, mixed> e.g. ['entity_type' => 'node', 'topics' => ['php']]
     */
    public function toSearchMetadata(): array;
}
