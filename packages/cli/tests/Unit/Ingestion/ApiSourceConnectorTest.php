<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Tests\Unit\Ingestion;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\CLI\Ingestion\ApiSourceConnector;

#[CoversClass(ApiSourceConnector::class)]
final class ApiSourceConnectorTest extends TestCase
{
    #[Test]
    public function it_normalizes_api_rows_and_sorts_by_source_id(): void
    {
        $connector = new ApiSourceConnector();
        $result = $connector->connect([
            [
                'source_uri' => 'HTTPS://Example.com:443/api/items?id=2&id=3&a=1#fragment',
                'batch_id' => 'api-batch-1',
                'ingested_at' => '2026-03-06T00:00:00Z',
                'parser_version' => 'api-1',
            ],
            [
                'source_uri' => 'https://example.com/api/items?a=1&id=2&id=3',
                'batch_id' => 'api-batch-2',
                'ingested_at' => '2026-03-06T00:01:00Z',
                'parser_version' => 'api-1',
                'timeout' => true,
            ],
        ]);

        $this->assertCount(2, $result['rows']);
        $this->assertSame($result['rows'][0]['source_id'], $result['rows'][1]['source_id']);
        $codes = array_values(array_map(
            static fn(array $diagnostic): string => (string) ($diagnostic['code'] ?? ''),
            $result['diagnostics'],
        ));
        $this->assertContains('connector.api.timeout', $codes);
        $this->assertContains('adapter.normalized_uri', $codes);
    }

    #[Test]
    public function it_emits_missing_required_field_for_invalid_rows(): void
    {
        $connector = new ApiSourceConnector();
        $result = $connector->connect([
            ['batch_id' => 'missing-uri'],
        ]);

        $this->assertSame([], $result['rows']);
        $this->assertSame('connector.missing_required_field', $result['diagnostics'][0]['code']);
        $this->assertSame('/records/0/source_uri', $result['diagnostics'][0]['location']);
    }
}
