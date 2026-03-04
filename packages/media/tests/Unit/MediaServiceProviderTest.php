<?php

declare(strict_types=1);

namespace Waaseyaa\Media\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Media\Media;
use Waaseyaa\Media\MediaServiceProvider;
use Waaseyaa\Media\MediaType;

#[CoversClass(MediaServiceProvider::class)]
final class MediaServiceProviderTest extends TestCase
{
    #[Test]
    public function registers_media_and_media_type(): void
    {
        $provider = new MediaServiceProvider();
        $provider->register();

        $entityTypes = $provider->getEntityTypes();

        $this->assertCount(2, $entityTypes);
        $this->assertSame('media', $entityTypes[0]->id());
        $this->assertSame(Media::class, $entityTypes[0]->getClass());
        $this->assertSame('media_type', $entityTypes[1]->id());
        $this->assertSame(MediaType::class, $entityTypes[1]->getClass());
    }
}
