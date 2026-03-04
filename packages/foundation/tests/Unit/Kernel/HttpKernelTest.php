<?php

declare(strict_types=1);

namespace Waaseyaa\Foundation\Tests\Unit\Kernel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Foundation\Kernel\AbstractKernel;
use Waaseyaa\Foundation\Kernel\HttpKernel;

#[CoversClass(HttpKernel::class)]
final class HttpKernelTest extends TestCase
{
    #[Test]
    public function is_an_abstract_kernel(): void
    {
        $this->assertTrue(is_subclass_of(HttpKernel::class, AbstractKernel::class));
    }

    #[Test]
    public function handle_is_never_return_type(): void
    {
        $ref = new \ReflectionMethod(HttpKernel::class, 'handle');

        $this->assertSame('never', $ref->getReturnType()?->getName());
    }

    #[Test]
    public function provides_project_root(): void
    {
        $kernel = new HttpKernel('/tmp/test-project');

        $this->assertSame('/tmp/test-project', $kernel->getProjectRoot());
    }


}
