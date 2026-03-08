<?php

declare(strict_types=1);

namespace Waaseyaa\Foundation\Tests\Unit\Ingestion;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Foundation\Ingestion\Envelope;
use Waaseyaa\Foundation\Ingestion\IngestionError;
use Waaseyaa\Foundation\Ingestion\IngestionErrorCode;
use Waaseyaa\Foundation\Ingestion\IngestionLogEntry;

#[CoversClass(IngestionLogEntry::class)]
final class IngestionLogEntryTest extends TestCase
{
    // ------------------------------------------------------------------
    // Success entries
    // ------------------------------------------------------------------

    #[Test]
    public function successFactoryBuildsAcceptedEntry(): void
    {
        $envelope = $this->makeEnvelope();
        $entry = IngestionLogEntry::success($envelope);

        $this->assertSame('manual', $entry->source);
        $this->assertSame('core.note', $entry->type);
        $this->assertSame('accepted', $entry->status);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $entry->traceId);
        $this->assertSame('2026-03-08T17:00:00+00:00', $entry->timestamp);
        $this->assertSame('tenant-1', $entry->tenantId);
        $this->assertSame([], $entry->errors);
    }

    #[Test]
    public function successToArrayContainsRequiredFields(): void
    {
        $entry = IngestionLogEntry::success($this->makeEnvelope());
        $arr = $entry->toArray();

        $this->assertSame('manual', $arr['source']);
        $this->assertSame('core.note', $arr['type']);
        $this->assertSame('accepted', $arr['status']);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $arr['trace_id']);
        $this->assertSame('2026-03-08T17:00:00+00:00', $arr['timestamp']);
        $this->assertSame('tenant-1', $arr['tenant_id']);
        $this->assertArrayHasKey('logged_at', $arr);
        $this->assertArrayNotHasKey('errors', $arr);
    }

    // ------------------------------------------------------------------
    // Failure entries — envelope
    // ------------------------------------------------------------------

    #[Test]
    public function envelopeFailureBuildsRejectedEntry(): void
    {
        $errors = [new IngestionError(
            code:    IngestionErrorCode::ENVELOPE_FIELD_MISSING,
            message: "Required field 'source' is missing.",
            field:   'source',
            traceId: 'abc-123',
        )];

        $entry = IngestionLogEntry::envelopeFailure(
            traceId: 'abc-123',
            source:  '',
            type:    'core.note',
            errors:  $errors,
        );

        $this->assertSame('rejected', $entry->status);
        $this->assertSame('abc-123', $entry->traceId);
        $this->assertCount(1, $entry->errors);
    }

    #[Test]
    public function envelopeFailureToArrayIncludesErrors(): void
    {
        $errors = [new IngestionError(
            code:    IngestionErrorCode::ENVELOPE_FIELD_MISSING,
            message: "Required field 'source' is missing.",
            field:   'source',
        )];

        $entry = IngestionLogEntry::envelopeFailure('trace-1', '', 'core.note', $errors);
        $arr = $entry->toArray();

        $this->assertSame('rejected', $arr['status']);
        $this->assertArrayHasKey('errors', $arr);
        $this->assertCount(1, $arr['errors']);
        $this->assertSame('ENVELOPE_FIELD_MISSING', $arr['errors'][0]['code']);
    }

    // ------------------------------------------------------------------
    // Failure entries — payload
    // ------------------------------------------------------------------

    #[Test]
    public function payloadFailureBuildsFromEnvelope(): void
    {
        $errors = [new IngestionError(
            code:    IngestionErrorCode::PAYLOAD_FIELD_MISSING,
            message: "Required field 'title' is missing.",
            field:   'title',
            traceId: '550e8400-e29b-41d4-a716-446655440000',
        )];

        $entry = IngestionLogEntry::payloadFailure($this->makeEnvelope(), $errors);

        $this->assertSame('rejected', $entry->status);
        $this->assertSame('manual', $entry->source);
        $this->assertSame('core.note', $entry->type);
        $this->assertSame('tenant-1', $entry->tenantId);
        $this->assertCount(1, $entry->errors);
    }

    // ------------------------------------------------------------------
    // Serialization shape
    // ------------------------------------------------------------------

    #[Test]
    public function toArrayOmitsTenantIdWhenNull(): void
    {
        $envelope = new Envelope(
            source:    'api',
            type:      'core.note',
            payload:   [],
            timestamp: '2026-03-08T17:00:00+00:00',
            traceId:   '550e8400-e29b-41d4-a716-446655440000',
        );

        $entry = IngestionLogEntry::success($envelope);
        $arr = $entry->toArray();

        $this->assertArrayNotHasKey('tenant_id', $arr);
    }

    #[Test]
    public function customLoggedAtIsPreserved(): void
    {
        $entry = new IngestionLogEntry(
            source:    'manual',
            type:      'core.note',
            status:    'accepted',
            traceId:   'trace-1',
            timestamp: '2026-03-08T17:00:00+00:00',
            loggedAt:  '2026-01-01T00:00:00+00:00',
        );

        $this->assertSame('2026-01-01T00:00:00+00:00', $entry->loggedAt);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function makeEnvelope(): Envelope
    {
        return new Envelope(
            source:    'manual',
            type:      'core.note',
            payload:   ['title' => 'Hello'],
            timestamp: '2026-03-08T17:00:00+00:00',
            traceId:   '550e8400-e29b-41d4-a716-446655440000',
            tenantId:  'tenant-1',
        );
    }
}
