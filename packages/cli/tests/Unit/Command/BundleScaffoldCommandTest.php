<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Waaseyaa\CLI\Command\BundleScaffoldCommand;

#[CoversClass(BundleScaffoldCommand::class)]
final class BundleScaffoldCommandTest extends TestCase
{
    #[Test]
    public function itGeneratesDeterministicBundleScaffoldJson(): void
    {
        $app = new Application();
        $app->add(new BundleScaffoldCommand());
        $command = $app->find('scaffold:bundle');

        $tester = new CommandTester($command);
        $tester->execute([
            '--id' => 'teaching',
            '--label' => 'Teaching',
            '--field' => ['body:text:0', 'title:string:1'],
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('teaching', $decoded['bundle']['id']);
        $this->assertSame('body', $decoded['bundle']['fields'][0]['name']);
        $this->assertSame('title', $decoded['bundle']['fields'][1]['name']);
    }

    #[Test]
    public function itReturnsInvalidForMalformedFieldOption(): void
    {
        $app = new Application();
        $app->add(new BundleScaffoldCommand());
        $command = $app->find('scaffold:bundle');

        $tester = new CommandTester($command);
        $tester->execute([
            '--id' => 'teaching',
            '--label' => 'Teaching',
            '--field' => ['broken-field'],
        ]);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString('Invalid --field format', $tester->getDisplay());
    }
}
