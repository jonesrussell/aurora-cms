<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Tests\Unit\Command;

use Waaseyaa\CLI\Command\ConfigExportCommand;
use Waaseyaa\Config\ConfigManagerInterface;
use Waaseyaa\Config\StorageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(ConfigExportCommand::class)]
class ConfigExportCommandTest extends TestCase
{
    #[Test]
    public function it_exports_configuration_and_shows_count(): void
    {
        $mockStorage = $this->createMock(StorageInterface::class);
        $mockStorage->method('listAll')->willReturn(['system.site', 'system.performance', 'user.settings']);

        $mockManager = $this->createMock(ConfigManagerInterface::class);
        $mockManager->expects($this->once())->method('export');
        $mockManager->method('getActiveStorage')->willReturn($mockStorage);

        $app = new Application();
        $app->add(new ConfigExportCommand($mockManager));
        $command = $app->find('config:export');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Configuration exported. Active storage contains 3 items.', $tester->getDisplay());
    }
}
