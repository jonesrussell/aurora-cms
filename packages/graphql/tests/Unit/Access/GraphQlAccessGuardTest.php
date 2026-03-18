<?php

declare(strict_types=1);

namespace Waaseyaa\GraphQL\Tests\Unit\Access;

use GraphQL\Error\UserError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Access\AccessPolicyInterface;
use Waaseyaa\Access\AccessResult;
use Waaseyaa\Access\AccountInterface;
use Waaseyaa\Access\EntityAccessHandler;
use Waaseyaa\Access\FieldAccessPolicyInterface;
use Waaseyaa\Api\Tests\Fixtures\TestEntity;
use Waaseyaa\Entity\EntityInterface;
use Waaseyaa\GraphQL\Access\GraphQlAccessGuard;

#[CoversClass(GraphQlAccessGuard::class)]
final class GraphQlAccessGuardTest extends TestCase
{
    private AccountInterface $account;

    protected function setUp(): void
    {
        $this->account = $this->createStub(AccountInterface::class);
        $this->account->method('id')->willReturn(1);
        $this->account->method('hasPermission')->willReturn(true);
        $this->account->method('getRoles')->willReturn(['authenticated']);
        $this->account->method('isAuthenticated')->willReturn(true);
    }

    private function makeEntity(string $title = 'Test'): TestEntity
    {
        return new TestEntity(values: ['id' => 1, 'title' => $title], entityTypeId: 'article');
    }

    private function allowAllPolicy(): AccessPolicyInterface&FieldAccessPolicyInterface
    {
        return new class implements AccessPolicyInterface, FieldAccessPolicyInterface {
            public function access(EntityInterface $entity, string $operation, AccountInterface $account): AccessResult
            {
                return AccessResult::allowed();
            }

            public function createAccess(string $entityTypeId, string $bundle, AccountInterface $account): AccessResult
            {
                return AccessResult::allowed();
            }

            public function appliesTo(string $entityTypeId): bool
            {
                return true;
            }

            public function fieldAccess(EntityInterface $entity, string $fieldName, string $operation, AccountInterface $account): AccessResult
            {
                return AccessResult::neutral();
            }
        };
    }

    private function denyAllPolicy(): AccessPolicyInterface&FieldAccessPolicyInterface
    {
        return new class implements AccessPolicyInterface, FieldAccessPolicyInterface {
            public function access(EntityInterface $entity, string $operation, AccountInterface $account): AccessResult
            {
                return AccessResult::forbidden('denied');
            }

            public function createAccess(string $entityTypeId, string $bundle, AccountInterface $account): AccessResult
            {
                return AccessResult::forbidden('denied');
            }

            public function appliesTo(string $entityTypeId): bool
            {
                return true;
            }

            public function fieldAccess(EntityInterface $entity, string $fieldName, string $operation, AccountInterface $account): AccessResult
            {
                return AccessResult::forbidden('all fields denied');
            }
        };
    }

    // ── canView ──────────────────────────────────────────────────

    #[Test]
    public function canViewReturnsTrueWhenAllowed(): void
    {
        $guard = new GraphQlAccessGuard(
            new EntityAccessHandler([$this->allowAllPolicy()]),
            $this->account,
        );

        self::assertTrue($guard->canView($this->makeEntity()));
    }

    #[Test]
    public function canViewReturnsFalseWhenNoPolicyGrants(): void
    {
        // No policies = Neutral = not isAllowed()
        $guard = new GraphQlAccessGuard(
            new EntityAccessHandler([]),
            $this->account,
        );

        self::assertFalse($guard->canView($this->makeEntity()));
    }

    #[Test]
    public function canViewReturnsFalseWhenForbidden(): void
    {
        $guard = new GraphQlAccessGuard(
            new EntityAccessHandler([$this->denyAllPolicy()]),
            $this->account,
        );

        self::assertFalse($guard->canView($this->makeEntity()));
    }

    // ── assertCreateAccess ───────────────────────────────────────

    #[Test]
    public function assertCreateAccessPassesWhenAllowed(): void
    {
        $guard = new GraphQlAccessGuard(
            new EntityAccessHandler([$this->allowAllPolicy()]),
            $this->account,
        );

        // Should not throw
        $guard->assertCreateAccess('article');
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function assertCreateAccessThrowsUserErrorWhenDenied(): void
    {
        $guard = new GraphQlAccessGuard(
            new EntityAccessHandler([]),
            $this->account,
        );

        $this->expectException(UserError::class);
        $this->expectExceptionMessage('cannot create');

        $guard->assertCreateAccess('article');
    }

    // ── assertUpdateAccess ───────────────────────────────────────

    #[Test]
    public function assertUpdateAccessPassesWhenAllowed(): void
    {
        $guard = new GraphQlAccessGuard(
            new EntityAccessHandler([$this->allowAllPolicy()]),
            $this->account,
        );

        $guard->assertUpdateAccess($this->makeEntity());
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function assertUpdateAccessThrowsUserErrorWhenDenied(): void
    {
        $guard = new GraphQlAccessGuard(
            new EntityAccessHandler([]),
            $this->account,
        );

        $this->expectException(UserError::class);
        $this->expectExceptionMessage('cannot update');

        $guard->assertUpdateAccess($this->makeEntity());
    }

    // ── assertDeleteAccess ───────────────────────────────────────

    #[Test]
    public function assertDeleteAccessPassesWhenAllowed(): void
    {
        $guard = new GraphQlAccessGuard(
            new EntityAccessHandler([$this->allowAllPolicy()]),
            $this->account,
        );

        $guard->assertDeleteAccess($this->makeEntity());
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function assertDeleteAccessThrowsUserErrorWhenDenied(): void
    {
        $guard = new GraphQlAccessGuard(
            new EntityAccessHandler([]),
            $this->account,
        );

        $this->expectException(UserError::class);
        $this->expectExceptionMessage('cannot delete');

        $guard->assertDeleteAccess($this->makeEntity());
    }

    // ── filterFields ─────────────────────────────────────────────

    #[Test]
    public function filterFieldsDelegatesToHandler(): void
    {
        $guard = new GraphQlAccessGuard(
            new EntityAccessHandler([$this->allowAllPolicy()]),
            $this->account,
        );

        $result = $guard->filterFields($this->makeEntity(), ['id', 'title', 'status'], 'view');

        self::assertSame(['id', 'title', 'status'], $result);
    }

    #[Test]
    public function filterFieldsRemovesForbiddenFields(): void
    {
        $policy = new class implements AccessPolicyInterface, FieldAccessPolicyInterface {
            public function access(EntityInterface $entity, string $operation, AccountInterface $account): AccessResult
            {
                return AccessResult::allowed();
            }

            public function createAccess(string $entityTypeId, string $bundle, AccountInterface $account): AccessResult
            {
                return AccessResult::allowed();
            }

            public function appliesTo(string $entityTypeId): bool
            {
                return true;
            }

            public function fieldAccess(EntityInterface $entity, string $fieldName, string $operation, AccountInterface $account): AccessResult
            {
                return $fieldName === 'status' ? AccessResult::forbidden('restricted') : AccessResult::neutral();
            }
        };

        $guard = new GraphQlAccessGuard(
            new EntityAccessHandler([$policy]),
            $this->account,
        );

        $result = $guard->filterFields($this->makeEntity(), ['id', 'title', 'status'], 'view');

        self::assertSame(['id', 'title'], $result);
        self::assertNotContains('status', $result);
    }

    // ── assertFieldEditAccess ────────────────────────────────────

    #[Test]
    public function assertFieldEditAccessPassesWhenNeutral(): void
    {
        // Open-by-default: Neutral = accessible for field-level
        $guard = new GraphQlAccessGuard(
            new EntityAccessHandler([$this->allowAllPolicy()]),
            $this->account,
        );

        $guard->assertFieldEditAccess($this->makeEntity(), 'title');
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function assertFieldEditAccessPassesWithNoPolicies(): void
    {
        // No field policies = Neutral = not forbidden = passes
        // This confirms open-by-default field-level semantics
        $guard = new GraphQlAccessGuard(
            new EntityAccessHandler([]),
            $this->account,
        );

        $guard->assertFieldEditAccess($this->makeEntity(), 'title');
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function assertFieldEditAccessThrowsWhenForbidden(): void
    {
        $policy = new class implements AccessPolicyInterface, FieldAccessPolicyInterface {
            public function access(EntityInterface $entity, string $operation, AccountInterface $account): AccessResult
            {
                return AccessResult::allowed();
            }

            public function createAccess(string $entityTypeId, string $bundle, AccountInterface $account): AccessResult
            {
                return AccessResult::allowed();
            }

            public function appliesTo(string $entityTypeId): bool
            {
                return true;
            }

            public function fieldAccess(EntityInterface $entity, string $fieldName, string $operation, AccountInterface $account): AccessResult
            {
                return $fieldName === 'secret' && $operation === 'edit'
                    ? AccessResult::forbidden('not editable')
                    : AccessResult::neutral();
            }
        };

        $guard = new GraphQlAccessGuard(
            new EntityAccessHandler([$policy]),
            $this->account,
        );

        $this->expectException(UserError::class);
        $this->expectExceptionMessage("cannot edit field 'secret'");

        $guard->assertFieldEditAccess($this->makeEntity(), 'secret');
    }

    #[Test]
    public function assertFieldEditAccessUsesIsForbiddenNotIsAllowed(): void
    {
        // Neutral result: isAllowed() = false, isForbidden() = false
        // Field-level uses !isForbidden() so Neutral should PASS (open-by-default)
        // This would fail if the implementation mistakenly checked isAllowed()
        $guard = new GraphQlAccessGuard(
            new EntityAccessHandler([]),
            $this->account,
        );

        // No policies means checkFieldAccess returns Neutral
        // assertFieldEditAccess checks isForbidden() which is false => no throw
        $guard->assertFieldEditAccess($this->makeEntity(), 'any_field');
        $this->addToAssertionCount(1);
    }
}
