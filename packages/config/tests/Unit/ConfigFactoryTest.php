<?php

declare(strict_types=1);

namespace Waaseyaa\Config\Tests\Unit;

use Waaseyaa\Config\Config;
use Waaseyaa\Config\ConfigFactory;
use Waaseyaa\Config\ConfigFactoryInterface;
use Waaseyaa\Config\Event\ConfigEvent;
use Waaseyaa\Config\Event\ConfigEvents;
use Waaseyaa\Config\EventAwareStorage;
use Waaseyaa\Config\Exception\ImmutableConfigException;
use Waaseyaa\Config\Storage\MemoryStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

#[CoversClass(ConfigFactory::class)]
#[CoversClass(EventAwareStorage::class)]
final class ConfigFactoryTest extends TestCase
{
    private MemoryStorage $storage;
    private EventDispatcher $eventDispatcher;
    private ConfigFactory $factory;

    protected function setUp(): void
    {
        $this->storage = new MemoryStorage();
        $this->eventDispatcher = new EventDispatcher();
        $this->factory = new ConfigFactory($this->storage, $this->eventDispatcher);
    }

    public function testImplementsConfigFactoryInterface(): void
    {
        $this->assertInstanceOf(ConfigFactoryInterface::class, $this->factory);
    }

    public function testGetReturnsImmutableConfig(): void
    {
        $this->storage->write('system.site', ['name' => 'Test']);

        $config = $this->factory->get('system.site');

        $this->assertSame('system.site', $config->getName());
        $this->assertSame('Test', $config->get('name'));
        $this->assertFalse($config->isNew());

        $this->expectException(ImmutableConfigException::class);
        $config->set('name', 'new');
    }

    public function testGetCachesConfig(): void
    {
        $this->storage->write('test', ['key' => 'value']);

        $config1 = $this->factory->get('test');
        $config2 = $this->factory->get('test');

        $this->assertSame($config1, $config2);
    }

    public function testGetReturnsNewConfigForMissing(): void
    {
        $config = $this->factory->get('nonexistent');

        $this->assertTrue($config->isNew());
        $this->assertSame([], $config->getRawData());
    }

    public function testGetEditableReturnsMutableConfig(): void
    {
        $this->storage->write('system.site', ['name' => 'Test']);

        $config = $this->factory->getEditable('system.site');

        $this->assertSame('Test', $config->get('name'));
        $this->assertFalse($config->isNew());

        // Should not throw.
        $config->set('name', 'Updated');
        $this->assertSame('Updated', $config->get('name'));
    }

    public function testGetEditableIsNotCached(): void
    {
        $this->storage->write('test', ['key' => 'value']);

        $config1 = $this->factory->getEditable('test');
        $config2 = $this->factory->getEditable('test');

        $this->assertNotSame($config1, $config2);
    }

    public function testGetEditableSaveWritesToStorage(): void
    {
        $this->storage->write('system.site', ['name' => 'Old']);

        $config = $this->factory->getEditable('system.site');
        $config->set('name', 'New')->save();

        $this->assertSame(['name' => 'New'], $this->storage->read('system.site'));
    }

    public function testSaveDispatchesPreAndPostSaveEvents(): void
    {
        $this->storage->write('test', ['key' => 'value']);

        $preSaveFired = false;
        $postSaveFired = false;

        $this->eventDispatcher->addListener(ConfigEvents::PRE_SAVE->value, function (ConfigEvent $event) use (&$preSaveFired): void {
            $preSaveFired = true;
            $this->assertSame('test', $event->getConfigName());
        });

        $this->eventDispatcher->addListener(ConfigEvents::POST_SAVE->value, function (ConfigEvent $event) use (&$postSaveFired): void {
            $postSaveFired = true;
            $this->assertSame('test', $event->getConfigName());
        });

        $config = $this->factory->getEditable('test');
        $config->set('key', 'updated')->save();

        $this->assertTrue($preSaveFired, 'PRE_SAVE event was not dispatched');
        $this->assertTrue($postSaveFired, 'POST_SAVE event was not dispatched');
    }

    public function testDeleteDispatchesPreAndPostDeleteEvents(): void
    {
        $this->storage->write('test', ['key' => 'value']);

        $preDeleteFired = false;
        $postDeleteFired = false;

        $this->eventDispatcher->addListener(ConfigEvents::PRE_DELETE->value, function () use (&$preDeleteFired): void {
            $preDeleteFired = true;
        });

        $this->eventDispatcher->addListener(ConfigEvents::POST_DELETE->value, function () use (&$postDeleteFired): void {
            $postDeleteFired = true;
        });

        $config = $this->factory->getEditable('test');
        $config->delete();

        $this->assertTrue($preDeleteFired, 'PRE_DELETE event was not dispatched');
        $this->assertTrue($postDeleteFired, 'POST_DELETE event was not dispatched');
    }

    public function testPreSaveEventCanModifyData(): void
    {
        $this->storage->write('test', ['key' => 'original']);

        $this->eventDispatcher->addListener(ConfigEvents::PRE_SAVE->value, function (ConfigEvent $event): void {
            $data = $event->getData();
            $data['injected'] = 'by_listener';
            $event->setData($data);
        });

        $config = $this->factory->getEditable('test');
        $config->set('key', 'updated')->save();

        $stored = $this->storage->read('test');
        $this->assertSame('by_listener', $stored['injected']);
    }

    public function testSaveInvalidatesGetCache(): void
    {
        $this->storage->write('test', ['key' => 'original']);

        // Load into cache.
        $immutable = $this->factory->get('test');
        $this->assertSame('original', $immutable->get('key'));

        // Update via editable.
        $editable = $this->factory->getEditable('test');
        $editable->set('key', 'updated')->save();

        // Cache should be invalidated, fresh read should return updated data.
        $fresh = $this->factory->get('test');
        $this->assertNotSame($immutable, $fresh);
        $this->assertSame('updated', $fresh->get('key'));
    }

    public function testLoadMultiple(): void
    {
        $this->storage->write('config.a', ['a' => 1]);
        $this->storage->write('config.b', ['b' => 2]);

        $configs = $this->factory->loadMultiple(['config.a', 'config.b']);

        $this->assertCount(2, $configs);
        $this->assertSame(1, $configs['config.a']->get('a'));
        $this->assertSame(2, $configs['config.b']->get('b'));
    }

    public function testLoadMultipleReturnsImmutableConfigs(): void
    {
        $this->storage->write('test', ['key' => 'value']);

        $configs = $this->factory->loadMultiple(['test']);

        $this->expectException(ImmutableConfigException::class);
        $configs['test']->set('key', 'new');
    }

    public function testRename(): void
    {
        $this->storage->write('old.config', ['key' => 'value']);

        $result = $this->factory->rename('old.config', 'new.config');

        $this->assertSame($this->factory, $result);
        $this->assertFalse($this->storage->exists('old.config'));
        $this->assertTrue($this->storage->exists('new.config'));
        $this->assertSame(['key' => 'value'], $this->storage->read('new.config'));
    }

    public function testRenameInvalidatesCache(): void
    {
        $this->storage->write('old', ['key' => 'value']);

        // Load into cache.
        $this->factory->get('old');

        $this->factory->rename('old', 'new');

        // Old name should not be cached.
        $oldConfig = $this->factory->get('old');
        $this->assertTrue($oldConfig->isNew());

        // New name should read fresh from storage.
        $newConfig = $this->factory->get('new');
        $this->assertSame('value', $newConfig->get('key'));
    }

    public function testListAll(): void
    {
        $this->storage->write('system.site', []);
        $this->storage->write('system.mail', []);
        $this->storage->write('views.view.frontpage', []);

        $all = $this->factory->listAll();
        $this->assertSame(['system.mail', 'system.site', 'views.view.frontpage'], $all);
    }

    public function testListAllWithPrefix(): void
    {
        $this->storage->write('system.site', []);
        $this->storage->write('system.mail', []);
        $this->storage->write('views.view.frontpage', []);

        $filtered = $this->factory->listAll('system.');
        $this->assertSame(['system.mail', 'system.site'], $filtered);
    }
}
