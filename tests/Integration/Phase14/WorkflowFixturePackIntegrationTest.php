<?php

declare(strict_types=1);

namespace Waaseyaa\Tests\Integration\Phase14;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Access\AccountInterface;
use Waaseyaa\Tests\Support\WorkflowFixturePack;
use Waaseyaa\Workflows\EditorialTransitionAccessResolver;
use Waaseyaa\Workflows\EditorialWorkflowStateMachine;

#[CoversNothing]
final class WorkflowFixturePackIntegrationTest extends TestCase
{
    #[Test]
    public function fixtureCorpusIsDeterministicAcrossCalls(): void
    {
        $firstHash = $this->fixtureHash();
        $secondHash = $this->fixtureHash();

        $this->assertSame($firstHash, $secondHash);
    }

    #[Test]
    public function transitionAccessScenariosCoverRoleAndPermissionPaths(): void
    {
        $stateMachine = new EditorialWorkflowStateMachine();
        $resolver = new EditorialTransitionAccessResolver($stateMachine);

        foreach (WorkflowFixturePack::transitionAccessScenarios() as $scenario) {
            $account = new FixtureScenarioAccount($scenario['permissions'], $scenario['roles']);
            $access = $resolver->canTransition(
                $scenario['bundle'],
                $scenario['from'],
                $scenario['to'],
                $account,
            );

            $this->assertSame(
                $scenario['expected_allowed'],
                $access->isAllowed(),
                sprintf('Scenario failed: %s', $scenario['name']),
            );
        }
    }

    #[Test]
    public function invalidTransitionScenariosRemainRejected(): void
    {
        $stateMachine = new EditorialWorkflowStateMachine();

        foreach (WorkflowFixturePack::invalidTransitionScenarios() as $scenario) {
            try {
                $stateMachine->assertTransitionAllowed($scenario['from'], $scenario['to']);
                $this->fail(sprintf('Expected runtime exception for scenario: %s', $scenario['name']));
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('Invalid workflow transition', $exception->getMessage());
            }
        }
    }

    private function fixtureHash(): string
    {
        $serialized = json_encode([
            'timestamp' => WorkflowFixturePack::FIXED_TIMESTAMP,
            'ssr_nodes' => WorkflowFixturePack::editorialNodesForSsr(),
            'ssr_aliases' => WorkflowFixturePack::pathAliasesForSsr(),
            'ai_mcp_nodes' => WorkflowFixturePack::aiMcpNodes(),
            'transition_access' => WorkflowFixturePack::transitionAccessScenarios(),
            'invalid_transitions' => WorkflowFixturePack::invalidTransitionScenarios(),
        ], JSON_THROW_ON_ERROR);

        return sha1($serialized);
    }
}

final class FixtureScenarioAccount implements AccountInterface
{
    /**
     * @param list<string> $permissions
     * @param list<string> $roles
     */
    public function __construct(
        private readonly array $permissions,
        private readonly array $roles,
    ) {}

    public function id(): int|string
    {
        return 1;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function isAuthenticated(): bool
    {
        return true;
    }
}
