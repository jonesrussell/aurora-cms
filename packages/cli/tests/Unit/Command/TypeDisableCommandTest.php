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
use Waaseyaa\CLI\Command\TypeDisableCommand;
use Waaseyaa\Entity\EntityType;
use Waaseyaa\Entity\EntityTypeLifecycleManager;
use Waaseyaa\Entity\EntityTypeManager;

#[CoversClass(TypeDisableCommand::class)]
final class TypeDisableCommandTest extends TestCase
{
    private string $tempDir;
    private EntityTypeLifecycleManager $lifecycle;
    private EntityTypeManager $entityTypeManager;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/waaseyaa_cli_type_disable_test_' . uniqid();
        mkdir($this->tempDir . '/storage/framework', 0755, true);

        $this->lifecycle = new EntityTypeLifecycleManager($this->tempDir);

        $this->entityTypeManager = new EntityTypeManager(new EventDispatcher());
        $this->entityTypeManager->registerEntityType(new EntityType(
            id: 'note',
            label: 'Note',
            class: \stdClass::class,
            keys: ['id' => 'id'],
        ));
        $this->entityTypeManager->registerEntityType(new EntityType(
            id: 'article',
            label: 'Article',
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
    public function disablesTypeAndRecordsAuditEntry(): void
    {
        $tester = $this->runCommand(['type' => 'note', '--yes' => true]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertTrue($this->lifecycle->isDisabled('note'));

        $log = $this->lifecycle->readAuditLog('note');
        $this->assertCount(1, $log);
        $this->assertSame('disabled', $log[0]['action']);
    }

    #[Test]
    public function disablesTypeForSpecificTenant(): void
    {
        $tester = $this->runCommand(['type' => 'note', '--tenant' => 'acme', '--yes' => true]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertTrue($this->lifecycle->isDisabled('note', 'acme'));
        $this->assertFalse($this->lifecycle->isDisabled('note'));

        $log = $this->lifecycle->readAuditLog('note', 'acme');
        $this->assertCount(1, $log);
        $this->assertSame('acme', $log[0]['tenant_id']);
    }

    #[Test]
    public function failsForUnknownType(): void
    {
        $tester = $this->runCommand(['type' => 'nonexistent', '--yes' => true]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Unknown entity type', $tester->getDisplay());
    }

    #[Test]
    public function alreadyDisabledIsIdempotent(): void
    {
        $this->lifecycle->disable('note', 'setup');

        $tester = $this->runCommand(['type' => 'note', '--yes' => true]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('already disabled', $tester->getDisplay());
    }

    #[Test]
    public function guardrailBlocksDisablingLastEnabledType(): void
    {
        $this->lifecycle->disable('article', 'setup');

        $tester = $this->runCommand(['type' => 'note', '--yes' => true]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('DEFAULT_TYPE_DISABLED', $tester->getDisplay());
        $this->assertFalse($this->lifecycle->isDisabled('note'));
    }

    #[Test]
    public function forceOverridesGuardrail(): void
    {
        $this->lifecycle->disable('article', 'setup');

        $tester = $this->runCommand(['type' => 'note', '--yes' => true, '--force' => true]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertTrue($this->lifecycle->isDisabled('note'));
        $this->assertStringContainsString('DEFAULT_TYPE_DISABLED', $tester->getDisplay());
    }

    #[Test]
    public function customActorRecordedInAuditLog(): void
    {
        $tester = $this->runCommand(['type' => 'note', '--actor' => 'admin-42', '--yes' => true]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        $log = $this->lifecycle->readAuditLog('note');
        $this->assertCount(1, $log);
        $this->assertSame('admin-42', $log[0]['actor_id']);
    }

    /**
     * @param array<string, mixed> $input
     */
    private function runCommand(array $input): CommandTester
    {
        $app = new Application();
        $app->add(new TypeDisableCommand(
            $this->entityTypeManager,
            $this->lifecycle,
        ));

        $command = $app->find('type:disable');
        $tester = new CommandTester($command);
        $tester->execute($input);

        return $tester;
    }
}
