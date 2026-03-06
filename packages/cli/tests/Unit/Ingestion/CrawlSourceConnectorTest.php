<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Tests\Unit\Ingestion;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\CLI\Ingestion\CrawlSourceConnector;

#[CoversClass(CrawlSourceConnector::class)]
final class CrawlSourceConnectorTest extends TestCase
{
    #[Test]
    public function it_prefers_canonical_link_and_emits_missing_link_diagnostic(): void
    {
        $connector = new CrawlSourceConnector();
        $result = $connector->connect([
            [
                'source_uri' => 'https://example.com/fallback/',
                'canonical_link' => '',
                'batch_id' => 'crawl-batch-1',
                'ingested_at' => '2026-03-06T00:00:00Z',
            ],
            [
                'source_uri' => 'https://example.com/ignored',
                'canonical_link' => 'HTTPS://Example.com/canonical/?z=2&a=1#frag',
                'batch_id' => 'crawl-batch-2',
                'ingested_at' => '2026-03-06T00:01:00Z',
            ],
        ]);

        $this->assertCount(2, $result['rows']);
        $this->assertSame('connector.crawl.missing_link', $result['diagnostics'][0]['code']);
        $this->assertSame('adapter.normalized_uri', $result['diagnostics'][1]['code']);
        $this->assertSame('https://example.com/canonical?a=1&z=2', $result['rows'][0]['source_uri']);
    }

    #[Test]
    public function it_emits_missing_required_field_when_no_links_are_available(): void
    {
        $connector = new CrawlSourceConnector();
        $result = $connector->connect([
            ['canonical_link' => ''],
        ]);

        $this->assertSame([], $result['rows']);
        $this->assertSame('connector.crawl.missing_link', $result['diagnostics'][0]['code']);
        $this->assertSame('connector.missing_required_field', $result['diagnostics'][1]['code']);
    }
}
