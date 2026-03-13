<?php

declare(strict_types=1);

namespace Waaseyaa\Telescope\Tests\Unit\CodifiedContext\Schema;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Telescope\CodifiedContext\Schema\SessionEventSchema;
use Waaseyaa\Telescope\CodifiedContext\Schema\ValidationReportSchema;

#[CoversClass(SessionEventSchema::class)]
#[CoversClass(ValidationReportSchema::class)]
final class SchemaValidatorsTest extends TestCase
{
    // --- SessionEventSchema ---

    #[Test]
    public function sessionEventSchemaValidWithAllRequiredFields(): void
    {
        $data = [
            'session_id' => 'sess-abc',
            'event_type' => 'session.started',
            'occurred_at' => '2026-01-01T00:00:00Z',
            'payload' => [],
        ];

        self::assertTrue(SessionEventSchema::validate($data));
    }

    #[Test]
    public function sessionEventSchemaInvalidMissingSessionId(): void
    {
        $data = [
            'event_type' => 'session.started',
            'occurred_at' => '2026-01-01T00:00:00Z',
            'payload' => [],
        ];

        self::assertFalse(SessionEventSchema::validate($data));
    }

    #[Test]
    public function sessionEventSchemaInvalidMissingEventType(): void
    {
        $data = [
            'session_id' => 'sess-abc',
            'occurred_at' => '2026-01-01T00:00:00Z',
            'payload' => [],
        ];

        self::assertFalse(SessionEventSchema::validate($data));
    }

    #[Test]
    public function sessionEventSchemaInvalidMissingOccurredAt(): void
    {
        $data = [
            'session_id' => 'sess-abc',
            'event_type' => 'session.started',
            'payload' => [],
        ];

        self::assertFalse(SessionEventSchema::validate($data));
    }

    #[Test]
    public function sessionEventSchemaInvalidMissingPayload(): void
    {
        $data = [
            'session_id' => 'sess-abc',
            'event_type' => 'session.started',
            'occurred_at' => '2026-01-01T00:00:00Z',
        ];

        self::assertFalse(SessionEventSchema::validate($data));
    }

    #[Test]
    public function sessionEventSchemaInvalidSessionIdNotString(): void
    {
        $data = [
            'session_id' => 123,
            'event_type' => 'session.started',
            'occurred_at' => '2026-01-01T00:00:00Z',
            'payload' => [],
        ];

        self::assertFalse(SessionEventSchema::validate($data));
    }

    #[Test]
    public function sessionEventSchemaInvalidPayloadNotArray(): void
    {
        $data = [
            'session_id' => 'sess-abc',
            'event_type' => 'session.started',
            'occurred_at' => '2026-01-01T00:00:00Z',
            'payload' => 'not-an-array',
        ];

        self::assertFalse(SessionEventSchema::validate($data));
    }

    // --- ValidationReportSchema ---

    #[Test]
    public function validationReportSchemaValidWithAllRequiredFields(): void
    {
        $data = [
            'session_id' => 'sess-abc',
            'drift_score' => 25,
            'issues' => [],
            'recommendation' => 'All good',
            'validated_at' => '2026-01-01T00:00:00Z',
        ];

        self::assertTrue(ValidationReportSchema::validate($data));
    }

    #[Test]
    public function validationReportSchemaInvalidMissingSessionId(): void
    {
        $data = [
            'drift_score' => 25,
            'issues' => [],
            'recommendation' => 'All good',
            'validated_at' => '2026-01-01T00:00:00Z',
        ];

        self::assertFalse(ValidationReportSchema::validate($data));
    }

    #[Test]
    public function validationReportSchemaInvalidDriftScoreNotInt(): void
    {
        $data = [
            'session_id' => 'sess-abc',
            'drift_score' => 'high',
            'issues' => [],
            'recommendation' => 'All good',
            'validated_at' => '2026-01-01T00:00:00Z',
        ];

        self::assertFalse(ValidationReportSchema::validate($data));
    }

    #[Test]
    public function validationReportSchemaInvalidIssuesNotArray(): void
    {
        $data = [
            'session_id' => 'sess-abc',
            'drift_score' => 25,
            'issues' => 'none',
            'recommendation' => 'All good',
            'validated_at' => '2026-01-01T00:00:00Z',
        ];

        self::assertFalse(ValidationReportSchema::validate($data));
    }

    #[Test]
    public function validationReportSchemaInvalidMissingRecommendation(): void
    {
        $data = [
            'session_id' => 'sess-abc',
            'drift_score' => 25,
            'issues' => [],
            'validated_at' => '2026-01-01T00:00:00Z',
        ];

        self::assertFalse(ValidationReportSchema::validate($data));
    }

    #[Test]
    public function validationReportSchemaInvalidMissingValidatedAt(): void
    {
        $data = [
            'session_id' => 'sess-abc',
            'drift_score' => 25,
            'issues' => [],
            'recommendation' => 'All good',
        ];

        self::assertFalse(ValidationReportSchema::validate($data));
    }
}
