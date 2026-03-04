<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Tests\Unit\Command\Perf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Waaseyaa\CLI\Command\Perf\PerformanceCompareCommand;

#[CoversClass(PerformanceCompareCommand::class)]
final class PerformanceCompareCommandTest extends TestCase
{
    #[Test]
    public function itPassesWhenCurrentMeasurementsMeetBaseline(): void
    {
        $baselinePath = tempnam(sys_get_temp_dir(), 'perf-baseline-');
        $currentPath = tempnam(sys_get_temp_dir(), 'perf-current-');
        $this->assertIsString($baselinePath);
        $this->assertIsString($currentPath);

        file_put_contents($baselinePath, json_encode([
            'snapshot_hash' => 'hash-a',
            'thresholds_ms' => ['warm' => 500.0, 'semantic_search' => 250.0],
        ], JSON_THROW_ON_ERROR));
        file_put_contents($currentPath, json_encode([
            'snapshot_hash' => 'hash-a',
            'durations_ms' => ['warm' => 200.0, 'semantic_search' => 120.0],
        ], JSON_THROW_ON_ERROR));

        $app = new Application();
        $app->add(new PerformanceCompareCommand());
        $command = $app->find('perf:compare');

        $tester = new CommandTester($command);
        $tester->execute([
            '--baseline' => $baselinePath,
            '--current' => $currentPath,
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('status: ok', strtolower($tester->getDisplay()));

        @unlink($baselinePath);
        @unlink($currentPath);
    }

    #[Test]
    public function itFailsWithExplicitDriftMessages(): void
    {
        $baselinePath = tempnam(sys_get_temp_dir(), 'perf-baseline-');
        $currentPath = tempnam(sys_get_temp_dir(), 'perf-current-');
        $this->assertIsString($baselinePath);
        $this->assertIsString($currentPath);

        file_put_contents($baselinePath, json_encode([
            'snapshot_hash' => 'hash-a',
            'thresholds_ms' => ['warm' => 100.0],
        ], JSON_THROW_ON_ERROR));
        file_put_contents($currentPath, json_encode([
            'snapshot_hash' => 'hash-b',
            'durations_ms' => ['warm' => 200.0],
        ], JSON_THROW_ON_ERROR));

        $app = new Application();
        $app->add(new PerformanceCompareCommand());
        $command = $app->find('perf:compare');

        $tester = new CommandTester($command);
        $tester->execute([
            '--baseline' => $baselinePath,
            '--current' => $currentPath,
        ]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('snapshot_hash mismatch', $tester->getDisplay());
        $this->assertStringContainsString('warm drifted', strtolower($tester->getDisplay()));

        @unlink($baselinePath);
        @unlink($currentPath);
    }
}
