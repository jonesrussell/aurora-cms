<?php

declare(strict_types=1);

namespace Waaseyaa\Config\Tests\Unit\Storage;

use Waaseyaa\Config\Storage\MemoryStorage;
use Waaseyaa\Config\StorageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MemoryStorage::class)]
final class MemoryStorageTest extends TestCase
{
    private MemoryStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new MemoryStorage();
    }

    public function testImplementsStorageInterface(): void
    {
        $this->assertInstanceOf(StorageInterface::class, $this->storage);
    }

    public function testExistsReturnsFalseForMissing(): void
    {
        $this->assertFalse($this->storage->exists('nonexistent'));
    }

    public function testWriteAndRead(): void
    {
        $data = ['key' => 'value', 'nested' => ['foo' => 'bar']];

        $this->assertTrue($this->storage->write('test.config', $data));
        $this->assertTrue($this->storage->exists('test.config'));
        $this->assertSame($data, $this->storage->read('test.config'));
    }

    public function testReadReturnsFalseForMissing(): void
    {
        $this->assertFalse($this->storage->read('nonexistent'));
    }

    public function testReadMultiple(): void
    {
        $this->storage->write('config.a', ['a' => 1]);
        $this->storage->write('config.b', ['b' => 2]);
        $this->storage->write('config.c', ['c' => 3]);

        $result = $this->storage->readMultiple(['config.a', 'config.c', 'config.missing']);

        $this->assertCount(2, $result);
        $this->assertSame(['a' => 1], $result['config.a']);
        $this->assertSame(['c' => 3], $result['config.c']);
        $this->assertArrayNotHasKey('config.missing', $result);
    }

    public function testDeleteExisting(): void
    {
        $this->storage->write('test', ['value' => true]);

        $this->assertTrue($this->storage->delete('test'));
        $this->assertFalse($this->storage->exists('test'));
    }

    public function testDeleteNonExisting(): void
    {
        $this->assertFalse($this->storage->delete('nonexistent'));
    }

    public function testRename(): void
    {
        $this->storage->write('old.name', ['data' => 'test']);

        $this->assertTrue($this->storage->rename('old.name', 'new.name'));
        $this->assertFalse($this->storage->exists('old.name'));
        $this->assertTrue($this->storage->exists('new.name'));
        $this->assertSame(['data' => 'test'], $this->storage->read('new.name'));
    }

    public function testRenameNonExisting(): void
    {
        $this->assertFalse($this->storage->rename('nonexistent', 'new'));
    }

    public function testListAll(): void
    {
        $this->storage->write('system.site', []);
        $this->storage->write('system.mail', []);
        $this->storage->write('views.view.frontpage', []);

        $all = $this->storage->listAll();
        $this->assertSame(['system.mail', 'system.site', 'views.view.frontpage'], $all);
    }

    public function testListAllWithPrefix(): void
    {
        $this->storage->write('system.site', []);
        $this->storage->write('system.mail', []);
        $this->storage->write('views.view.frontpage', []);

        $filtered = $this->storage->listAll('system.');
        $this->assertSame(['system.mail', 'system.site'], $filtered);
    }

    public function testListAllEmpty(): void
    {
        $this->assertSame([], $this->storage->listAll());
    }

    public function testDeleteAll(): void
    {
        $this->storage->write('a', ['v' => 1]);
        $this->storage->write('b', ['v' => 2]);

        $this->assertTrue($this->storage->deleteAll());
        $this->assertSame([], $this->storage->listAll());
    }

    public function testDeleteAllWithPrefix(): void
    {
        $this->storage->write('system.site', []);
        $this->storage->write('system.mail', []);
        $this->storage->write('views.view.frontpage', []);

        $this->assertTrue($this->storage->deleteAll('system.'));

        $this->assertFalse($this->storage->exists('system.site'));
        $this->assertFalse($this->storage->exists('system.mail'));
        $this->assertTrue($this->storage->exists('views.view.frontpage'));
    }

    public function testCreateCollectionReturnsNewInstance(): void
    {
        $collection = $this->storage->createCollection('language.fr');

        $this->assertInstanceOf(MemoryStorage::class, $collection);
        $this->assertNotSame($this->storage, $collection);
        $this->assertSame('language.fr', $collection->getCollectionName());
    }

    public function testDefaultCollectionNameIsEmpty(): void
    {
        $this->assertSame('', $this->storage->getCollectionName());
    }

    public function testGetAllCollectionNamesReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->storage->getAllCollectionNames());
    }

    public function testCollectionsAreIsolated(): void
    {
        $collection = $this->storage->createCollection('lang.fr');
        $collection->write('test', ['fr' => true]);

        $this->assertFalse($this->storage->exists('test'));
        $this->assertTrue($collection->exists('test'));
    }
}
