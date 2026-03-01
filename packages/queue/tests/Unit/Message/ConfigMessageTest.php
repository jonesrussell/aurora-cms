<?php

declare(strict_types=1);

namespace Waaseyaa\Queue\Tests\Unit\Message;

use Waaseyaa\Queue\Message\ConfigMessage;
use PHPUnit\Framework\TestCase;

final class ConfigMessageTest extends TestCase
{
    public function testConstructWithAllProperties(): void
    {
        $message = new ConfigMessage(
            configName: 'system.site',
            operation: 'save',
            data: ['name' => 'My Site'],
        );

        $this->assertSame('system.site', $message->configName);
        $this->assertSame('save', $message->operation);
        $this->assertSame(['name' => 'My Site'], $message->data);
    }

    public function testConstructWithDefaults(): void
    {
        $message = new ConfigMessage(
            configName: 'system.performance',
            operation: 'delete',
        );

        $this->assertSame('system.performance', $message->configName);
        $this->assertSame('delete', $message->operation);
        $this->assertSame([], $message->data);
    }

    public function testConstructWithRenameOperation(): void
    {
        $message = new ConfigMessage(
            configName: 'old.config.name',
            operation: 'rename',
            data: ['new_name' => 'new.config.name'],
        );

        $this->assertSame('old.config.name', $message->configName);
        $this->assertSame('rename', $message->operation);
        $this->assertSame(['new_name' => 'new.config.name'], $message->data);
    }
}
