<?php

declare(strict_types=1);

namespace Waaseyaa\Foundation\Tests\Unit\Kernel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Foundation\Kernel\AbstractKernel;

#[CoversClass(AbstractKernel::class)]
final class AbstractKernelTest extends TestCase
{
    #[Test]
    public function kernel_provides_project_root(): void
    {
        $kernel = new class('/tmp/test-project') extends AbstractKernel {
        };

        $this->assertSame('/tmp/test-project', $kernel->getProjectRoot());
    }

    #[Test]
    public function kernel_boots_core_services(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $kernel = new class($projectRoot) extends AbstractKernel {
            public function publicBoot(): void
            {
                $this->boot();
            }
        };

        $kernel->publicBoot();

        $this->assertNotNull($kernel->getEntityTypeManager());
        $this->assertNotNull($kernel->getDatabase());
        $this->assertNotNull($kernel->getEventDispatcher());
    }

    #[Test]
    public function boot_is_idempotent(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $kernel = new class($projectRoot) extends AbstractKernel {
            public int $bootCount = 0;

            public function publicBoot(): void
            {
                $this->bootCount++;
                $this->boot();
            }
        };

        $kernel->publicBoot();
        $kernel->publicBoot();

        // publicBoot increments each call, but boot() guard prevents double-init
        $this->assertSame(2, $kernel->bootCount);
        // Second boot should not throw "already registered" for entity types
        $this->assertInstanceOf(\Waaseyaa\Entity\EntityTypeManager::class, $kernel->getEntityTypeManager());
    }
}
