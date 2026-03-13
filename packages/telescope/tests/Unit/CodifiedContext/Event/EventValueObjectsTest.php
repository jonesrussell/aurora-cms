<?php

declare(strict_types=1);

namespace Waaseyaa\Telescope\Tests\Unit\CodifiedContext\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Telescope\CodifiedContext\Event\ContextHashComputed;
use Waaseyaa\Telescope\CodifiedContext\Event\ContextLoadFailed;
use Waaseyaa\Telescope\CodifiedContext\Event\ContextLoaded;
use Waaseyaa\Telescope\CodifiedContext\Event\DriftCorrected;
use Waaseyaa\Telescope\CodifiedContext\Event\DriftDetected;
use Waaseyaa\Telescope\CodifiedContext\Event\ModelOutputRecorded;
use Waaseyaa\Telescope\CodifiedContext\Event\SessionEnded;
use Waaseyaa\Telescope\CodifiedContext\Event\SessionStarted;
use Waaseyaa\Telescope\CodifiedContext\Event\ValidationCompleted;

#[CoversClass(SessionStarted::class)]
#[CoversClass(SessionEnded::class)]
#[CoversClass(ContextLoaded::class)]
#[CoversClass(ContextHashComputed::class)]
#[CoversClass(ContextLoadFailed::class)]
#[CoversClass(ModelOutputRecorded::class)]
#[CoversClass(DriftDetected::class)]
#[CoversClass(DriftCorrected::class)]
#[CoversClass(ValidationCompleted::class)]
final class EventValueObjectsTest extends TestCase
{
    #[Test]
    public function sessionStartedConstruction(): void
    {
        $event = new SessionStarted(
            sessionId: 'sess-abc',
            repoHash: 'repo-hash-123',
        );

        self::assertSame('sess-abc', $event->sessionId);
        self::assertSame('repo-hash-123', $event->repoHash);
        self::assertSame([], $event->metadata);
        self::assertInstanceOf(\DateTimeImmutable::class, $event->occurredAt);
    }

    #[Test]
    public function sessionStartedWithMetadata(): void
    {
        $at = new \DateTimeImmutable('2026-01-01 00:00:00');
        $event = new SessionStarted(
            sessionId: 'sess-abc',
            repoHash: 'repo-hash-123',
            metadata: ['user' => 'jones'],
            occurredAt: $at,
        );

        self::assertSame(['user' => 'jones'], $event->metadata);
        self::assertSame($at, $event->occurredAt);
    }

    #[Test]
    public function sessionStartedToArray(): void
    {
        $at = new \DateTimeImmutable('2026-01-01 12:00:00');
        $event = new SessionStarted(
            sessionId: 'sess-abc',
            repoHash: 'repo-hash-123',
            metadata: ['key' => 'val'],
            occurredAt: $at,
        );

        $arr = $event->toArray();

        self::assertSame('sess-abc', $arr['session_id']);
        self::assertSame('repo-hash-123', $arr['repo_hash']);
        self::assertSame(['key' => 'val'], $arr['metadata']);
        self::assertArrayHasKey('occurred_at', $arr);
    }

    #[Test]
    public function sessionEndedConstruction(): void
    {
        $event = new SessionEnded(
            sessionId: 'sess-abc',
            durationMs: 1234.5,
            eventCount: 10,
        );

        self::assertSame('sess-abc', $event->sessionId);
        self::assertSame(1234.5, $event->durationMs);
        self::assertSame(10, $event->eventCount);
        self::assertInstanceOf(\DateTimeImmutable::class, $event->occurredAt);
    }

    #[Test]
    public function sessionEndedToArray(): void
    {
        $event = new SessionEnded(sessionId: 's', durationMs: 500.0, eventCount: 5);
        $arr = $event->toArray();

        self::assertSame('s', $arr['session_id']);
        self::assertSame(500.0, $arr['duration_ms']);
        self::assertSame(5, $arr['event_count']);
    }

    #[Test]
    public function contextLoadedConstruction(): void
    {
        $event = new ContextLoaded(
            sessionId: 'sess-abc',
            contextHash: 'hash-xyz',
            filePaths: ['/foo/bar.md'],
            totalBytes: 1024,
        );

        self::assertSame('sess-abc', $event->sessionId);
        self::assertSame('hash-xyz', $event->contextHash);
        self::assertSame(['/foo/bar.md'], $event->filePaths);
        self::assertSame(1024, $event->totalBytes);
    }

    #[Test]
    public function contextLoadedToArray(): void
    {
        $event = new ContextLoaded(
            sessionId: 's',
            contextHash: 'h',
            filePaths: ['/a'],
            totalBytes: 100,
        );
        $arr = $event->toArray();

        self::assertSame('s', $arr['session_id']);
        self::assertSame('h', $arr['context_hash']);
        self::assertSame(['/a'], $arr['file_paths']);
        self::assertSame(100, $arr['total_bytes']);
    }

    #[Test]
    public function contextHashComputedDefaultAlgorithm(): void
    {
        $event = new ContextHashComputed(
            sessionId: 'sess-abc',
            contextHash: 'abc123',
        );

        self::assertSame('sha256', $event->algorithm);
    }

    #[Test]
    public function contextHashComputedToArray(): void
    {
        $event = new ContextHashComputed(
            sessionId: 's',
            contextHash: 'h',
            algorithm: 'sha512',
        );
        $arr = $event->toArray();

        self::assertSame('s', $arr['session_id']);
        self::assertSame('h', $arr['context_hash']);
        self::assertSame('sha512', $arr['algorithm']);
    }

    #[Test]
    public function contextLoadFailedWithNullFilePath(): void
    {
        $event = new ContextLoadFailed(
            sessionId: 'sess-abc',
            errorMessage: 'File not found',
            filePath: null,
        );

        self::assertNull($event->filePath);
        self::assertSame('File not found', $event->errorMessage);
    }

    #[Test]
    public function contextLoadFailedToArray(): void
    {
        $event = new ContextLoadFailed(
            sessionId: 's',
            errorMessage: 'err',
            filePath: '/foo.md',
        );
        $arr = $event->toArray();

        self::assertSame('s', $arr['session_id']);
        self::assertSame('err', $arr['error_message']);
        self::assertSame('/foo.md', $arr['file_path']);
    }

    #[Test]
    public function modelOutputRecordedConstruction(): void
    {
        $event = new ModelOutputRecorded(
            sessionId: 'sess-abc',
            outputHash: 'out-hash',
            references: ['ref1', 'ref2'],
            tokenCount: 500,
        );

        self::assertSame('sess-abc', $event->sessionId);
        self::assertSame('out-hash', $event->outputHash);
        self::assertSame(['ref1', 'ref2'], $event->references);
        self::assertSame(500, $event->tokenCount);
    }

    #[Test]
    public function modelOutputRecordedToArray(): void
    {
        $event = new ModelOutputRecorded(
            sessionId: 's',
            outputHash: 'h',
            references: [],
            tokenCount: 100,
        );
        $arr = $event->toArray();

        self::assertSame('s', $arr['session_id']);
        self::assertSame('h', $arr['output_hash']);
        self::assertSame([], $arr['references']);
        self::assertSame(100, $arr['token_count']);
    }

    #[Test]
    public function driftDetectedConstruction(): void
    {
        $event = new DriftDetected(
            sessionId: 'sess-abc',
            driftScore: 75,
            severity: 'high',
            issues: ['stale spec'],
        );

        self::assertSame(75, $event->driftScore);
        self::assertSame('high', $event->severity);
        self::assertSame(['stale spec'], $event->issues);
    }

    #[Test]
    public function driftDetectedToArray(): void
    {
        $event = new DriftDetected(
            sessionId: 's',
            driftScore: 50,
            severity: 'medium',
            issues: ['issue1'],
        );
        $arr = $event->toArray();

        self::assertSame('s', $arr['session_id']);
        self::assertSame(50, $arr['drift_score']);
        self::assertSame('medium', $arr['severity']);
        self::assertSame(['issue1'], $arr['issues']);
    }

    #[Test]
    public function driftCorrectedConstruction(): void
    {
        $event = new DriftCorrected(
            sessionId: 'sess-abc',
            originalScore: 80,
            correctedScore: 10,
            corrections: ['updated spec'],
        );

        self::assertSame(80, $event->originalScore);
        self::assertSame(10, $event->correctedScore);
        self::assertSame(['updated spec'], $event->corrections);
    }

    #[Test]
    public function driftCorrectedToArray(): void
    {
        $event = new DriftCorrected(
            sessionId: 's',
            originalScore: 80,
            correctedScore: 10,
            corrections: ['fix'],
        );
        $arr = $event->toArray();

        self::assertSame('s', $arr['session_id']);
        self::assertSame(80, $arr['original_score']);
        self::assertSame(10, $arr['corrected_score']);
        self::assertSame(['fix'], $arr['corrections']);
    }

    #[Test]
    public function validationCompletedConstruction(): void
    {
        $event = new ValidationCompleted(
            sessionId: 'sess-abc',
            driftScore: 20,
            issues: [],
            recommendation: 'No action needed',
        );

        self::assertSame(20, $event->driftScore);
        self::assertSame([], $event->issues);
        self::assertSame('No action needed', $event->recommendation);
    }

    #[Test]
    public function validationCompletedToArray(): void
    {
        $event = new ValidationCompleted(
            sessionId: 's',
            driftScore: 20,
            issues: ['minor'],
            recommendation: 'Review specs',
        );
        $arr = $event->toArray();

        self::assertSame('s', $arr['session_id']);
        self::assertSame(20, $arr['drift_score']);
        self::assertSame(['minor'], $arr['issues']);
        self::assertSame('Review specs', $arr['recommendation']);
    }
}
