<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Tests\Unit\Ingestion;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\CLI\Ingestion\DatasetSourceConnector;
use Waaseyaa\CLI\Ingestion\SourceAdapterNormalizer;

#[CoversClass(DatasetSourceConnector::class)]
final class DatasetSourceConnectorTest extends TestCase
{
    #[Test]
    public function it_normalizes_rows_and_sorts_by_source_id(): void
    {
        $connector = new DatasetSourceConnector();
        $payload = [
            [
                'source_uri' => 'HTTP://Example.COM:80///path//B/?b=2&a=1#frag',
                'ownership' => 'first_party',
                'synthetic_flag' => false,
                'batch_id' => 'batch-a',
                'ingested_at' => '2026-03-05T17:00:00Z',
                'parser_version' => '1.0',
            ],
            [
                'source_uri' => 'https://example.com/path/a/?a=1&b=2',
                'ownership' => 'first_party',
                'synthetic_flag' => false,
                'batch_id' => 'batch-b',
                'ingested_at' => '2026-03-05T17:01:00Z',
                'parser_version' => '1.0',
            ],
        ];

        $result = $connector->connect($payload);

        $expectedRows = [];
        $expectedDiagnostics = [];
        foreach ($payload as $record) {
            $normalized = $this->normalizeRecord($record);
            $expectedRows[] = array_merge($normalized['provenance'], $normalized['metadata']);
            $expectedDiagnostics = array_merge($expectedDiagnostics, $normalized['diagnostics']);
        }

        usort(
            $expectedRows,
            static fn(array $a, array $b): int => strcmp((string) ($a['source_id'] ?? ''), (string) ($b['source_id'] ?? '')),
        );

        $this->assertSame($expectedRows, $result['rows']);
        $this->assertSame($expectedDiagnostics, $result['diagnostics']);
    }

    #[Test]
    public function it_collects_connector_diagnostics_when_metadata_mutation_occurs(): void
    {
        $connector = new DatasetSourceConnector();
        $payload = [
            [
                'source_uri' => 'HTTP://Example.COM:80/', 
                'ownership' => 'third_party',
                'synthetic_flag' => true,
                'batch_id' => 'batch-meta',
                'ingested_at' => '2026-03-05T18:00:00Z',
                'parser_version' => null,
                'metadata' => ['adapter_extra' => ['provider_id' => 'p']],
            ],
        ];

        $result = $connector->connect($payload);
        $this->assertNotEmpty($result['diagnostics']);
        $this->assertSame(['provider_id' => 'p'], $result['rows'][0]['adapter_extra']);
    }

    /**
     * @param array<string, mixed> $record
     * @return array{provenance:array<string, mixed>, diagnostics:list<array<string, mixed>>, metadata:array<string, mixed>}
     */
    private function normalizeRecord(array $record): array
    {
        $normalizer = new SourceAdapterNormalizer();
        return $normalizer->normalize([
            'raw_source_uri' => (string) ($record['source_uri'] ?? ''),
            'adapter_type' => 'dataset',
            'ownership' => (string) ($record['ownership'] ?? 'first_party'),
            'synthetic_flag' => (bool) ($record['synthetic_flag'] ?? false),
            'batch_id' => (string) ($record['batch_id'] ?? ''),
            'ingested_at' => (string) ($record['ingested_at'] ?? ''),
            'parser_version' => $record['parser_version'] ?? null,
            'metadata' => [
                'adapter_extra' => is_array($record['metadata']['adapter_extra'] ?? null) ? $record['metadata']['adapter_extra'] : [],
            ],
        ]);
    }
}
