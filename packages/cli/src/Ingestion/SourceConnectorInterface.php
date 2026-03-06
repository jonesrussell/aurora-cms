<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Ingestion;

interface SourceConnectorInterface
{
    /**
     * @param list<array<string, mixed>> $records
     * @return array{rows:list<array<string, mixed>>,diagnostics:list<array<string, mixed>>}
     */
    public function connect(array $records): array;
}
