<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Ingestion;

final class CrawlSourceConnector implements SourceConnectorInterface
{
    public function __construct(private readonly SourceAdapterNormalizer $normalizer = new SourceAdapterNormalizer()) {}

    /**
     * @param list<array<string, mixed>> $records
     * @return array{rows:list<array<string, mixed>>,diagnostics:list<array<string, mixed>>}
     */
    public function connect(array $records): array
    {
        $rows = [];
        $diagnostics = [];

        foreach ($records as $index => $record) {
            $canonicalLink = (string) ($record['canonical_link'] ?? '');
            $sourceUri = (string) ($record['source_uri'] ?? '');
            $rawSourceUri = $canonicalLink !== '' ? $canonicalLink : $sourceUri;

            if ($canonicalLink === '') {
                $diagnostics[] = [
                    'code' => 'connector.crawl.missing_link',
                    'message' => 'Crawl connector missing canonical_link; using source_uri fallback when available.',
                    'location' => '/records/' . $index . '/canonical_link',
                    'item_index' => $index,
                    'context' => [
                        'source_uri' => $sourceUri,
                    ],
                ];
            }

            if ($rawSourceUri === '') {
                $diagnostics[] = [
                    'code' => 'connector.missing_required_field',
                    'message' => 'Missing required connector field: "source_uri".',
                    'location' => '/records/' . $index . '/source_uri',
                    'item_index' => $index,
                    'context' => [
                        'field' => 'source_uri',
                    ],
                ];
                continue;
            }

            $recordMetadata = is_array($record['metadata'] ?? null) ? $record['metadata'] : [];
            $payload = [
                'raw_source_uri' => $rawSourceUri,
                'adapter_type' => 'crawl',
                'ownership' => (string) ($record['ownership'] ?? 'third_party'),
                'synthetic_flag' => (bool) ($record['synthetic_flag'] ?? false),
                'batch_id' => (string) ($record['batch_id'] ?? $record['source_id'] ?? ''),
                'ingested_at' => (string) ($record['ingested_at'] ?? $record['timestamp'] ?? ''),
                'parser_version' => $record['parser_version'] ?? null,
                'metadata' => [
                    'adapter_extra' => is_array($recordMetadata['adapter_extra'] ?? null) ? $recordMetadata['adapter_extra'] : [],
                ],
            ];

            $normalized = $this->normalizer->normalize($payload);
            $rows[] = array_merge($normalized['provenance'], $normalized['metadata']);
            $diagnostics = array_merge($diagnostics, $normalized['diagnostics']);
        }

        usort(
            $rows,
            static fn(array $a, array $b): int => strcmp((string) ($a['source_id'] ?? ''), (string) ($b['source_id'] ?? '')),
        );

        return ['rows' => $rows, 'diagnostics' => $diagnostics];
    }
}
