<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Ingestion;

final class ApiSourceConnector implements SourceConnectorInterface
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
            if (!is_array($record) || (($record['source_uri'] ?? '') === '')) {
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

            if ((bool) ($record['timeout'] ?? false) === true) {
                $diagnostics[] = [
                    'code' => 'connector.api.timeout',
                    'message' => 'API connector timed out while normalizing source row.',
                    'location' => '/records/' . $index,
                    'item_index' => $index,
                    'context' => [
                        'source_uri' => (string) $record['source_uri'],
                    ],
                ];
            }

            $recordMetadata = is_array($record['metadata'] ?? null) ? $record['metadata'] : [];
            $payload = [
                'raw_source_uri' => (string) $record['source_uri'],
                'adapter_type' => 'api',
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
