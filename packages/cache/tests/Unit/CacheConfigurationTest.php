<?php

declare(strict_types=1);

namespace Waaseyaa\Cache\Tests\Unit;

use Waaseyaa\Cache\Backend\MemoryBackend;
use Waaseyaa\Cache\Backend\NullBackend;
use Waaseyaa\Cache\CacheConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CacheConfiguration::class)]
final class CacheConfigurationTest extends TestCase
{
    #[Test]
    public function default_backend_is_memory_backend(): void
    {
        $config = new CacheConfiguration();

        $this->assertSame(MemoryBackend::class, $config->getDefaultBackend());
    }

    #[Test]
    public function custom_default_backend(): void
    {
        $config = new CacheConfiguration(NullBackend::class);

        $this->assertSame(NullBackend::class, $config->getDefaultBackend());
    }

    #[Test]
    public function unmapped_bin_returns_default(): void
    {
        $config = new CacheConfiguration();

        $this->assertSame(MemoryBackend::class, $config->getBackendForBin('unknown_bin'));
    }

    #[Test]
    public function mapped_bin_returns_specific_backend(): void
    {
        $config = new CacheConfiguration(MemoryBackend::class, [
            'cache_config' => NullBackend::class,
        ]);

        $this->assertSame(NullBackend::class, $config->getBackendForBin('cache_config'));
    }

    #[Test]
    public function set_backend_for_bin_at_runtime(): void
    {
        $config = new CacheConfiguration();

        $config->setBackendForBin('cache_entity', NullBackend::class);

        $this->assertSame(NullBackend::class, $config->getBackendForBin('cache_entity'));
    }

    #[Test]
    public function get_bin_mapping_returns_all_mappings(): void
    {
        $config = new CacheConfiguration(MemoryBackend::class, [
            'cache_config' => NullBackend::class,
            'cache_entity' => NullBackend::class,
        ]);

        $mapping = $config->getBinMapping();

        $this->assertCount(2, $mapping);
        $this->assertSame(NullBackend::class, $mapping['cache_config']);
        $this->assertSame(NullBackend::class, $mapping['cache_entity']);
    }

    #[Test]
    public function constructor_mappings_override_default(): void
    {
        $config = new CacheConfiguration(MemoryBackend::class, [
            'cache_render' => NullBackend::class,
        ]);

        // Mapped bin returns specific backend.
        $this->assertSame(NullBackend::class, $config->getBackendForBin('cache_render'));
        // Other bins return default.
        $this->assertSame(MemoryBackend::class, $config->getBackendForBin('cache_default'));
    }

    // ---------------------------------------------------------------------------
    // Factory callable support
    // ---------------------------------------------------------------------------

    #[Test]
    public function get_factory_for_bin_returns_null_when_none_registered(): void
    {
        $config = new CacheConfiguration();

        $this->assertNull($config->getFactoryForBin('cache_db'));
    }

    #[Test]
    public function set_factory_for_bin_and_retrieve_it(): void
    {
        $config = new CacheConfiguration();
        $factory = fn() => new NullBackend();

        $config->setFactoryForBin('cache_db', $factory);

        $this->assertSame($factory, $config->getFactoryForBin('cache_db'));
    }

    #[Test]
    public function bin_factory_takes_precedence_over_default_factory(): void
    {
        $config = new CacheConfiguration();
        $defaultFactory = fn() => new MemoryBackend();
        $binFactory = fn() => new NullBackend();

        $config->setDefaultFactory($defaultFactory);
        $config->setFactoryForBin('special', $binFactory);

        $this->assertSame($binFactory, $config->getFactoryForBin('special'));
        $this->assertSame($defaultFactory, $config->getFactoryForBin('other'));
    }

    #[Test]
    public function default_factory_used_when_no_bin_factory_registered(): void
    {
        $config = new CacheConfiguration();
        $defaultFactory = fn() => new MemoryBackend();

        $config->setDefaultFactory($defaultFactory);

        $this->assertSame($defaultFactory, $config->getFactoryForBin('any_bin'));
    }

    #[Test]
    public function bin_factories_via_constructor_parameter(): void
    {
        $factory = fn() => new NullBackend();
        $config = new CacheConfiguration(
            binFactories: ['cache_null' => $factory],
        );

        $this->assertSame($factory, $config->getFactoryForBin('cache_null'));
        $this->assertNull($config->getFactoryForBin('other'));
    }

    #[Test]
    public function default_factory_via_constructor_parameter(): void
    {
        $defaultFactory = fn() => new NullBackend();
        $config = new CacheConfiguration(
            defaultFactory: $defaultFactory,
        );

        $this->assertSame($defaultFactory, $config->getFactoryForBin('any_bin'));
    }
}
