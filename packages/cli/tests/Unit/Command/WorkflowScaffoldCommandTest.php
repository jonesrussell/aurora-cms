<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Waaseyaa\CLI\Command\WorkflowScaffoldCommand;

#[CoversClass(WorkflowScaffoldCommand::class)]
final class WorkflowScaffoldCommandTest extends TestCase
{
    #[Test]
    public function itGeneratesDeterministicWorkflowScaffoldJson(): void
    {
        $app = new Application();
        $app->add(new WorkflowScaffoldCommand());
        $command = $app->find('scaffold:workflow');

        $tester = new CommandTester($command);
        $tester->execute([
            '--id' => 'article_editorial',
            '--bundle' => 'article',
            '--state' => ['published', 'draft', 'review'],
            '--transition' => [
                'publish:review:published:publish article content',
                'submit_review:draft:review:submit article for review',
            ],
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('article_editorial', $decoded['workflow']['id']);
        $this->assertSame(['draft', 'published', 'review'], $decoded['workflow']['states']);
        $this->assertSame('publish', $decoded['workflow']['transitions'][0]['id']);
        $this->assertSame('submit_review', $decoded['workflow']['transitions'][1]['id']);
    }

    #[Test]
    public function itReturnsInvalidForMalformedTransition(): void
    {
        $app = new Application();
        $app->add(new WorkflowScaffoldCommand());
        $command = $app->find('scaffold:workflow');

        $tester = new CommandTester($command);
        $tester->execute([
            '--id' => 'article_editorial',
            '--bundle' => 'article',
            '--transition' => ['broken'],
        ]);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString('Invalid --transition format', $tester->getDisplay());
    }
}
