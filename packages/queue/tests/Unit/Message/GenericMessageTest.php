<?php

declare(strict_types=1);

namespace Waaseyaa\Queue\Tests\Unit\Message;

use Waaseyaa\Queue\Message\GenericMessage;
use PHPUnit\Framework\TestCase;

final class GenericMessageTest extends TestCase
{
    public function testConstructWithAllProperties(): void
    {
        $message = new GenericMessage(
            type: 'email.send',
            payload: ['to' => 'user@example.com', 'subject' => 'Welcome'],
        );

        $this->assertSame('email.send', $message->type);
        $this->assertSame(['to' => 'user@example.com', 'subject' => 'Welcome'], $message->payload);
    }

    public function testConstructWithDefaults(): void
    {
        $message = new GenericMessage(
            type: 'cache.clear',
        );

        $this->assertSame('cache.clear', $message->type);
        $this->assertSame([], $message->payload);
    }
}
