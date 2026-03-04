<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Waaseyaa\CLI\Command\FixtureGenerateCommand;

#[CoversClass(FixtureGenerateCommand::class)]
final class FixtureGenerateCommandTest extends TestCase
{
    #[Test]
    public function itGeneratesDeterministicFanoutScenario(): void
    {
        $app = new Application();
        $app->add(new FixtureGenerateCommand());
        $command = $app->find('fixture:generate');

        $tester = new CommandTester($command);
        $tester->execute([
            '--template' => 'fanout',
            '--count' => '5',
            '--prefix' => 'perf',
            '--bundle' => 'teaching',
            '--timestamp' => '1735689600',
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(5, $decoded['nodes']);
        $this->assertCount(4, $decoded['relationships']);
        $this->assertArrayHasKey('perf_001', $decoded['nodes']);
        $this->assertSame('perf_001_to_perf_002_related', $decoded['relationships'][0]['key']);
    }

    #[Test]
    public function itRejectsUnknownTemplate(): void
    {
        $app = new Application();
        $app->add(new FixtureGenerateCommand());
        $command = $app->find('fixture:generate');

        $tester = new CommandTester($command);
        $tester->execute([
            '--template' => 'unknown',
            '--prefix' => 'perf',
            '--bundle' => 'teaching',
        ]);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString('Unknown --template', $tester->getDisplay());
    }
}
