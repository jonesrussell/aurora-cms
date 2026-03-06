<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Tests\Unit\Ingestion;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\CLI\Ingestion\FileSourceConnector;

#[CoversClass(FileSourceConnector::class)]
final class FileSourceConnectorTest extends TestCase
{
    #[Test]
    public function it_normalizes_file_rows_and_sorts_by_source_id(): void
    {
        $connector = new FileSourceConnector();
        $result = $connector->connect([
            [
                'source_uri' => 'https://example.com/a/',
                'batch_id' => 'file-batch-1',
                'ingested_at' => '2026-03-06T00:00:00Z',
            ],
            [
                'source_uri' => 'https://example.com/b/',
                'batch_id' => 'file-batch-2',
                'ingested_at' => '2026-03-06T00:01:00Z',
            ],
        ]);

        $this->assertCount(2, $result['rows']);
        $this->assertLessThanOrEqual($result['rows'][1]['source_id'], $result['rows'][0]['source_id']);
        $this->assertCount(2, $result['diagnostics']);
        $this->assertSame('adapter.normalized_uri', $result['diagnostics'][0]['code']);
    }

    #[Test]
    public function it_emits_missing_required_field_for_invalid_rows(): void
    {
        $connector = new FileSourceConnector();
        $result = $connector->connect([
            ['batch_id' => 'missing-uri'],
        ]);

        $this->assertSame([], $result['rows']);
        $this->assertSame('connector.missing_required_field', $result['diagnostics'][0]['code']);
    }
}
