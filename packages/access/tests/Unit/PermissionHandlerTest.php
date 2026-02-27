<?php

declare(strict_types=1);

namespace Aurora\Access\Tests\Unit;

use Aurora\Access\PermissionHandler;
use Aurora\Access\PermissionHandlerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Aurora\Access\PermissionHandler
 */
class PermissionHandlerTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $handler = new PermissionHandler();

        $this->assertInstanceOf(PermissionHandlerInterface::class, $handler);
    }

    public function testEmptyByDefault(): void
    {
        $handler = new PermissionHandler();

        $this->assertSame([], $handler->getPermissions());
    }

    public function testRegisterPermission(): void
    {
        $handler = new PermissionHandler();
        $handler->registerPermission('create article', 'Create Article', 'Allows creating articles.');

        $permissions = $handler->getPermissions();

        $this->assertCount(1, $permissions);
        $this->assertArrayHasKey('create article', $permissions);
        $this->assertSame('Create Article', $permissions['create article']['title']);
        $this->assertSame('Allows creating articles.', $permissions['create article']['description']);
    }

    public function testRegisterMultiplePermissions(): void
    {
        $handler = new PermissionHandler();
        $handler->registerPermission('create article', 'Create Article');
        $handler->registerPermission('edit own article', 'Edit Own Article');
        $handler->registerPermission('administer users', 'Administer Users');

        $permissions = $handler->getPermissions();

        $this->assertCount(3, $permissions);
        $this->assertArrayHasKey('create article', $permissions);
        $this->assertArrayHasKey('edit own article', $permissions);
        $this->assertArrayHasKey('administer users', $permissions);
    }

    public function testDescriptionDefaultsToEmptyString(): void
    {
        $handler = new PermissionHandler();
        $handler->registerPermission('view content', 'View Content');

        $permissions = $handler->getPermissions();

        $this->assertSame('', $permissions['view content']['description']);
    }

    public function testHasPermissionReturnsTrueWhenRegistered(): void
    {
        $handler = new PermissionHandler();
        $handler->registerPermission('create article', 'Create Article');

        $this->assertTrue($handler->hasPermission('create article'));
    }

    public function testHasPermissionReturnsFalseWhenNotRegistered(): void
    {
        $handler = new PermissionHandler();

        $this->assertFalse($handler->hasPermission('nonexistent'));
    }

    public function testOverwritePermission(): void
    {
        $handler = new PermissionHandler();
        $handler->registerPermission('create article', 'Create Article', 'Original');
        $handler->registerPermission('create article', 'Create Article (updated)', 'Updated');

        $permissions = $handler->getPermissions();

        $this->assertCount(1, $permissions);
        $this->assertSame('Create Article (updated)', $permissions['create article']['title']);
        $this->assertSame('Updated', $permissions['create article']['description']);
    }
}
