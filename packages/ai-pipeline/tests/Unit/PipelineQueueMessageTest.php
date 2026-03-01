<?php

declare(strict_types=1);

namespace Waaseyaa\AI\Pipeline\Tests\Unit;

use Waaseyaa\AI\Pipeline\PipelineQueueMessage;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Waaseyaa\AI\Pipeline\PipelineQueueMessage
 */
final class PipelineQueueMessageTest extends TestCase
{
    public function testConstruction(): void
    {
        $message = new PipelineQueueMessage(
            pipelineId: 'content_pipeline',
            input: ['text' => 'hello world'],
            createdAt: 1700000000,
        );

        $this->assertSame('content_pipeline', $message->pipelineId);
        $this->assertSame(['text' => 'hello world'], $message->input);
        $this->assertSame(1700000000, $message->createdAt);
    }

    public function testConstructionDefaults(): void
    {
        $message = new PipelineQueueMessage(pipelineId: 'my_pipeline');

        $this->assertSame('my_pipeline', $message->pipelineId);
        $this->assertSame([], $message->input);
        $this->assertSame(0, $message->createdAt);
    }

    public function testIsReadonly(): void
    {
        $message = new PipelineQueueMessage(pipelineId: 'test');

        $reflection = new \ReflectionClass($message);
        $this->assertTrue($reflection->isReadOnly());
    }
}
