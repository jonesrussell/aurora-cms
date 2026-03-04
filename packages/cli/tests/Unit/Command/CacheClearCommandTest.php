<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Tests\Unit\Command;

use Waaseyaa\Cache\CacheBackendInterface;
use Waaseyaa\Cache\CacheFactoryInterface;
use Waaseyaa\Cache\TagAwareCacheInterface;
use Waaseyaa\CLI\Command\CacheClearCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(CacheClearCommand::class)]
class CacheClearCommandTest extends TestCase
{
    #[Test]
    public function it_clears_all_default_bins(): void
    {
        $mockBackend = $this->createMock(CacheBackendInterface::class);
        $mockBackend->expects($this->exactly(4))->method('deleteAll');

        $mockFactory = $this->createMock(CacheFactoryInterface::class);
        $mockFactory->method('get')->willReturn($mockBackend);

        $app = new Application();
        $app->add(new CacheClearCommand($mockFactory));
        $command = $app->find('cache:clear');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('All cache bins cleared.', $tester->getDisplay());
    }

    #[Test]
    public function it_clears_a_specific_bin(): void
    {
        $mockBackend = $this->createMock(CacheBackendInterface::class);
        $mockBackend->expects($this->once())->method('deleteAll');

        $mockFactory = $this->createMock(CacheFactoryInterface::class);
        $mockFactory->expects($this->once())
            ->method('get')
            ->with('render')
            ->willReturn($mockBackend);

        $app = new Application();
        $app->add(new CacheClearCommand($mockFactory));
        $command = $app->find('cache:clear');
        $tester = new CommandTester($command);
        $tester->execute(['--bin' => 'render']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Cache bin "render" cleared.', $tester->getDisplay());
    }

    #[Test]
    public function it_invalidates_by_tags_for_tag_aware_bins(): void
    {
        $mockBackend = $this->createMock(TagAwareCacheInterface::class);
        $mockBackend->expects($this->exactly(4))
            ->method('invalidateByTags')
            ->with(['render']);

        $mockFactory = $this->createMock(CacheFactoryInterface::class);
        $mockFactory->method('get')->willReturn($mockBackend);

        $app = new Application();
        $app->add(new CacheClearCommand($mockFactory));
        $command = $app->find('cache:clear');
        $tester = new CommandTester($command);
        $tester->execute(['--tags' => 'render']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('invalidated by tags: render', $tester->getDisplay());
    }
}
