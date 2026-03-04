<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Tests\Unit\Command\Perf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Waaseyaa\CLI\Command\Perf\PerformanceBaselineCommand;

#[CoversClass(PerformanceBaselineCommand::class)]
final class PerformanceBaselineCommandTest extends TestCase
{
    #[Test]
    public function itBuildsDeterministicBaselinePayload(): void
    {
        $app = new Application();
        $app->add(new PerformanceBaselineCommand());
        $command = $app->find('perf:baseline');

        $tester = new CommandTester($command);
        $tester->execute([
            '--snapshot-hash' => 'abc123',
            '--threshold' => ['semantic_search:120', 'warm:500'],
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('abc123', $decoded['snapshot_hash']);
        $this->assertArrayHasKey('semantic_search', $decoded['thresholds_ms']);
        $this->assertArrayHasKey('warm', $decoded['thresholds_ms']);
    }

    #[Test]
    public function itRejectsMissingThresholds(): void
    {
        $app = new Application();
        $app->add(new PerformanceBaselineCommand());
        $command = $app->find('perf:baseline');

        $tester = new CommandTester($command);
        $tester->execute([
            '--snapshot-hash' => 'abc123',
        ]);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString('At least one valid --threshold', $tester->getDisplay());
    }
}
