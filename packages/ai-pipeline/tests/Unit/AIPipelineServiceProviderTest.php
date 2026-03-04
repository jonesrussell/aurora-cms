<?php

declare(strict_types=1);

namespace Waaseyaa\AI\Pipeline\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\AI\Pipeline\AIPipelineServiceProvider;
use Waaseyaa\AI\Pipeline\Pipeline;

#[CoversClass(AIPipelineServiceProvider::class)]
final class AIPipelineServiceProviderTest extends TestCase
{
    #[Test]
    public function registers_pipeline(): void
    {
        $provider = new AIPipelineServiceProvider();
        $provider->register();

        $entityTypes = $provider->getEntityTypes();

        $this->assertCount(1, $entityTypes);
        $this->assertSame('pipeline', $entityTypes[0]->id());
        $this->assertSame(Pipeline::class, $entityTypes[0]->getClass());
    }
}
