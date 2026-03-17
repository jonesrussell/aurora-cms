<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Tests\Unit\Command;

use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Waaseyaa\CLI\Command\MigrateStatusCommand;
use Waaseyaa\Foundation\Migration\Migration;
use Waaseyaa\Foundation\Migration\MigrationRepository;
use Waaseyaa\Foundation\Migration\Migrator;
use Waaseyaa\Foundation\Migration\SchemaBuilder;

#[CoversClass(MigrateStatusCommand::class)]
final class MigrateStatusCommandTest extends TestCase
{
    #[Test]
    public function showsPendingAndCompletedMigrations(): void
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $repository = new MigrationRepository($connection);
        $repository->createTable();
        $migrator = new Migrator($connection, $repository);

        $ran = new class extends Migration {
            public function up(SchemaBuilder $schema): void {}
        };
        $pending = new class extends Migration {
            public function up(SchemaBuilder $schema): void {}
        };

        $migrations = ['app' => [
            'app:20260317_first' => $ran,
            'app:20260318_second' => $pending,
        ]];

        // Run only the first migration
        $migrator->run(['app' => ['app:20260317_first' => $ran]]);

        $command = new MigrateStatusCommand($migrator, fn () => $migrations);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $display = $tester->getDisplay();
        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('app:20260317_first', $display);
        $this->assertStringContainsString('Ran', $display);
        $this->assertStringContainsString('app:20260318_second', $display);
        $this->assertStringContainsString('Pending', $display);
    }
}
