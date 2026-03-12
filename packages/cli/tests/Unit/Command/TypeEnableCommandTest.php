<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Waaseyaa\CLI\Command\TypeEnableCommand;
use Waaseyaa\Entity\EntityType;
use Waaseyaa\Entity\EntityTypeLifecycleManager;
use Waaseyaa\Entity\EntityTypeManager;

#[CoversClass(TypeEnableCommand::class)]
final class TypeEnableCommandTest extends TestCase
{
    private string $tempDir;
    private EntityTypeLifecycleManager $lifecycle;
    private EntityTypeManager $entityTypeManager;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/waaseyaa_cli_enable_test_' . uniqid();
        mkdir($this->tempDir . '/storage/framework', 0755, true);

        $this->lifecycle = new EntityTypeLifecycleManager($this->tempDir);

        $this->entityTypeManager = new EntityTypeManager(new EventDispatcher());
        $this->entityTypeManager->registerEntityType(new EntityType(
            id: 'note',
            label: 'Note',
            class: \stdClass::class,
            keys: ['id' => 'id'],
        ));
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tempDir . '/storage/framework/*') ?: []);
        @rmdir($this->tempDir . '/storage/framework');
        @rmdir($this->tempDir . '/storage');
        @rmdir($this->tempDir);
    }

    #[Test]
    public function enablesDisabledTypeAndRecordsAuditEntry(): void
    {
        $this->lifecycle->disable('note', 'setup', null);

        $tester = $this->runCommand(['type' => 'note']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertFalse($this->lifecycle->isDisabled('note', null));

        $log = $this->lifecycle->readAuditLog('note');
        $this->assertCount(2, $log);
        $this->assertSame('enabled', $log[1]['action']);
    }

    #[Test]
    public function enablesTypeForSpecificTenant(): void
    {
        $this->lifecycle->disable('note', 'setup', 'acme');

        $tester = $this->runCommand(['type' => 'note', '--tenant' => 'acme']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertFalse($this->lifecycle->isDisabled('note', 'acme'));

        $log = $this->lifecycle->readAuditLog('note', 'acme');
        $this->assertNotEmpty($log);
        $lastEntry = end($log);
        $this->assertSame('acme', $lastEntry['tenant_id']);
    }

    #[Test]
    public function alreadyEnabledIsIdempotent(): void
    {
        $tester = $this->runCommand(['type' => 'note']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('already enabled', $tester->getDisplay());
    }

    #[Test]
    public function failsForUnknownType(): void
    {
        $tester = $this->runCommand(['type' => 'nonexistent']);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Unknown entity type', $tester->getDisplay());
    }

    #[Test]
    public function customActorRecordedInAuditLog(): void
    {
        $this->lifecycle->disable('note', 'setup', null);

        $tester = $this->runCommand(['type' => 'note', '--actor' => 'migration-bot']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        $log = $this->lifecycle->readAuditLog('note');
        $enableEntry = end($log);
        $this->assertSame('migration-bot', $enableEntry['actor_id']);
    }

    /**
     * @param array<string, mixed> $input
     */
    private function runCommand(array $input): CommandTester
    {
        $app = new Application();
        $app->add(new TypeEnableCommand(
            $this->entityTypeManager,
            $this->lifecycle,
        ));

        $command = $app->find('type:enable');
        $tester = new CommandTester($command);
        $tester->execute($input);

        return $tester;
    }
}
