<?php

declare(strict_types=1);

namespace Aurora\Config\Tests\Unit;

use Aurora\Config\ConfigImportResult;
use Aurora\Config\ConfigManager;
use Aurora\Config\ConfigManagerInterface;
use Aurora\Config\Event\ConfigEvent;
use Aurora\Config\Event\ConfigEvents;
use Aurora\Config\Storage\MemoryStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

#[CoversClass(ConfigManager::class)]
final class ConfigManagerTest extends TestCase
{
    private MemoryStorage $activeStorage;
    private MemoryStorage $syncStorage;
    private EventDispatcher $eventDispatcher;
    private ConfigManager $manager;

    protected function setUp(): void
    {
        $this->activeStorage = new MemoryStorage();
        $this->syncStorage = new MemoryStorage();
        $this->eventDispatcher = new EventDispatcher();
        $this->manager = new ConfigManager(
            $this->activeStorage,
            $this->syncStorage,
            $this->eventDispatcher,
        );
    }

    public function testImplementsConfigManagerInterface(): void
    {
        $this->assertInstanceOf(ConfigManagerInterface::class, $this->manager);
    }

    public function testGetActiveStorage(): void
    {
        $this->assertSame($this->activeStorage, $this->manager->getActiveStorage());
    }

    public function testGetSyncStorage(): void
    {
        $this->assertSame($this->syncStorage, $this->manager->getSyncStorage());
    }

    public function testImportCreatesNewConfigs(): void
    {
        $this->syncStorage->write('system.site', ['name' => 'Test']);
        $this->syncStorage->write('system.mail', ['transport' => 'smtp']);

        $result = $this->manager->import();

        $this->assertInstanceOf(ConfigImportResult::class, $result);
        $this->assertSame(['system.mail', 'system.site'], $this->sorted($result->created));
        $this->assertSame([], $result->updated);
        $this->assertSame([], $result->deleted);
        $this->assertFalse($result->hasErrors());

        $this->assertSame(['name' => 'Test'], $this->activeStorage->read('system.site'));
        $this->assertSame(['transport' => 'smtp'], $this->activeStorage->read('system.mail'));
    }

    public function testImportUpdatesChangedConfigs(): void
    {
        $this->activeStorage->write('system.site', ['name' => 'Old']);
        $this->syncStorage->write('system.site', ['name' => 'New']);

        $result = $this->manager->import();

        $this->assertSame([], $result->created);
        $this->assertSame(['system.site'], $result->updated);
        $this->assertSame([], $result->deleted);
        $this->assertSame(['name' => 'New'], $this->activeStorage->read('system.site'));
    }

    public function testImportDeletesRemovedConfigs(): void
    {
        $this->activeStorage->write('system.site', ['name' => 'Test']);
        $this->activeStorage->write('old.config', ['legacy' => true]);
        $this->syncStorage->write('system.site', ['name' => 'Test']);

        $result = $this->manager->import();

        $this->assertSame([], $result->created);
        $this->assertSame([], $result->updated);
        $this->assertSame(['old.config'], $result->deleted);
        $this->assertFalse($this->activeStorage->exists('old.config'));
    }

    public function testImportSkipsUnchangedConfigs(): void
    {
        $data = ['name' => 'Test', 'slogan' => 'Hello'];
        $this->activeStorage->write('system.site', $data);
        $this->syncStorage->write('system.site', $data);

        $result = $this->manager->import();

        $this->assertSame([], $result->created);
        $this->assertSame([], $result->updated);
        $this->assertSame([], $result->deleted);
    }

    public function testImportHandlesAllOperationsTogether(): void
    {
        $this->activeStorage->write('keep.same', ['v' => 1]);
        $this->activeStorage->write('to.update', ['v' => 'old']);
        $this->activeStorage->write('to.delete', ['v' => true]);

        $this->syncStorage->write('keep.same', ['v' => 1]);
        $this->syncStorage->write('to.update', ['v' => 'new']);
        $this->syncStorage->write('to.create', ['v' => 'fresh']);

        $result = $this->manager->import();

        $this->assertSame(['to.create'], $result->created);
        $this->assertSame(['to.update'], $result->updated);
        $this->assertSame(['to.delete'], $result->deleted);
    }

    public function testImportDispatchesImportEvent(): void
    {
        $eventFired = false;

        $this->eventDispatcher->addListener(ConfigEvents::IMPORT->value, function (ConfigEvent $event) use (&$eventFired): void {
            $eventFired = true;
            $data = $event->getData();
            $this->assertArrayHasKey('result', $data);
            $this->assertInstanceOf(ConfigImportResult::class, $data['result']);
        });

        $this->syncStorage->write('test', ['key' => 'value']);
        $this->manager->import();

        $this->assertTrue($eventFired, 'IMPORT event was not dispatched');
    }

    public function testExportCopiesActiveToSync(): void
    {
        $this->activeStorage->write('system.site', ['name' => 'Test']);
        $this->activeStorage->write('system.mail', ['transport' => 'smtp']);

        $this->manager->export();

        $this->assertSame(['name' => 'Test'], $this->syncStorage->read('system.site'));
        $this->assertSame(['transport' => 'smtp'], $this->syncStorage->read('system.mail'));
    }

    public function testExportClearsSyncFirst(): void
    {
        $this->syncStorage->write('old.config', ['legacy' => true]);
        $this->activeStorage->write('system.site', ['name' => 'Test']);

        $this->manager->export();

        $this->assertFalse($this->syncStorage->exists('old.config'));
        $this->assertTrue($this->syncStorage->exists('system.site'));
    }

    public function testExportWithEmptyActive(): void
    {
        $this->syncStorage->write('old', ['v' => 1]);

        $this->manager->export();

        $this->assertSame([], $this->syncStorage->listAll());
    }

    public function testDiffShowsChanges(): void
    {
        $this->activeStorage->write('test', ['key' => 'active_value']);
        $this->syncStorage->write('test', ['key' => 'sync_value']);

        $diff = $this->manager->diff('test');

        $this->assertSame(['key' => 'active_value'], $diff['active']);
        $this->assertSame(['key' => 'sync_value'], $diff['sync']);
        $this->assertTrue($diff['has_changes']);
    }

    public function testDiffShowsNoChanges(): void
    {
        $data = ['key' => 'same'];
        $this->activeStorage->write('test', $data);
        $this->syncStorage->write('test', $data);

        $diff = $this->manager->diff('test');

        $this->assertSame($data, $diff['active']);
        $this->assertSame($data, $diff['sync']);
        $this->assertFalse($diff['has_changes']);
    }

    public function testDiffWithMissingActive(): void
    {
        $this->syncStorage->write('test', ['key' => 'value']);

        $diff = $this->manager->diff('test');

        $this->assertNull($diff['active']);
        $this->assertSame(['key' => 'value'], $diff['sync']);
        $this->assertTrue($diff['has_changes']);
    }

    public function testDiffWithMissingSync(): void
    {
        $this->activeStorage->write('test', ['key' => 'value']);

        $diff = $this->manager->diff('test');

        $this->assertSame(['key' => 'value'], $diff['active']);
        $this->assertNull($diff['sync']);
        $this->assertTrue($diff['has_changes']);
    }

    public function testDiffWithBothMissing(): void
    {
        $diff = $this->manager->diff('nonexistent');

        $this->assertNull($diff['active']);
        $this->assertNull($diff['sync']);
        // Both null === both null.
        $this->assertFalse($diff['has_changes']);
    }

    /**
     * @param string[] $values
     * @return string[]
     */
    private function sorted(array $values): array
    {
        sort($values);

        return $values;
    }
}
