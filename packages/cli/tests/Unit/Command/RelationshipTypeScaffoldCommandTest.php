<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Waaseyaa\CLI\Command\RelationshipTypeScaffoldCommand;

#[CoversClass(RelationshipTypeScaffoldCommand::class)]
final class RelationshipTypeScaffoldCommandTest extends TestCase
{
    #[Test]
    public function itGeneratesRelationshipTypeScaffoldJson(): void
    {
        $app = new Application();
        $app->add(new RelationshipTypeScaffoldCommand());
        $command = $app->find('scaffold:relationship');

        $tester = new CommandTester($command);
        $tester->execute([
            '--id' => 'supports',
            '--label' => 'Supports',
            '--directionality' => 'bidirectional',
            '--inverse' => 'supported_by',
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('supports', $decoded['relationship_type']['id']);
        $this->assertSame('bidirectional', $decoded['relationship_type']['directionality']);
        $this->assertSame('supported_by', $decoded['relationship_type']['inverse']);
    }

    #[Test]
    public function itRejectsInvalidDirectionality(): void
    {
        $app = new Application();
        $app->add(new RelationshipTypeScaffoldCommand());
        $command = $app->find('scaffold:relationship');

        $tester = new CommandTester($command);
        $tester->execute([
            '--id' => 'supports',
            '--label' => 'Supports',
            '--directionality' => 'sideways',
        ]);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString('--directionality must be', $tester->getDisplay());
    }
}
