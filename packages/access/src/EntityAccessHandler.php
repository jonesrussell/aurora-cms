<?php

declare(strict_types=1);

namespace Aurora\Access;

use Aurora\Entity\EntityInterface;

/**
 * Checks entity access by running all registered AccessPolicy plugins.
 *
 * Policies are filtered by entity type, then executed. Results are combined
 * using OR logic (any Allowed grants access), but Forbidden always wins.
 * If no policy grants access, the result is Neutral (effectively denied).
 */
class EntityAccessHandler
{
    /**
     * @var AccessPolicyInterface[]
     */
    private array $policies;

    /**
     * @param AccessPolicyInterface[] $policies
     */
    public function __construct(array $policies = [])
    {
        $this->policies = $policies;
    }

    /**
     * Add an access policy.
     */
    public function addPolicy(AccessPolicyInterface $policy): void
    {
        $this->policies[] = $policy;
    }

    /**
     * Check access for an existing entity.
     *
     * @param EntityInterface  $entity    The entity being accessed.
     * @param string           $operation The operation: 'view', 'update', or 'delete'.
     * @param AccountInterface $account   The account requesting access.
     */
    public function check(EntityInterface $entity, string $operation, AccountInterface $account): AccessResult
    {
        $result = AccessResult::neutral('No policy provided an opinion.');
        $entityTypeId = $entity->getEntityTypeId();

        foreach ($this->policies as $policy) {
            if (!$policy->appliesTo($entityTypeId)) {
                continue;
            }

            $policyResult = $policy->access($entity, $operation, $account);
            $result = $result->orIf($policyResult);

            // Short-circuit on Forbidden — nothing can override it.
            if ($result->isForbidden()) {
                return $result;
            }
        }

        return $result;
    }

    /**
     * Check access for creating a new entity.
     *
     * @param string           $entityTypeId The entity type ID.
     * @param string           $bundle       The bundle.
     * @param AccountInterface $account      The account requesting access.
     */
    public function checkCreateAccess(string $entityTypeId, string $bundle, AccountInterface $account): AccessResult
    {
        $result = AccessResult::neutral('No policy provided an opinion.');

        foreach ($this->policies as $policy) {
            if (!$policy->appliesTo($entityTypeId)) {
                continue;
            }

            $policyResult = $policy->createAccess($entityTypeId, $bundle, $account);
            $result = $result->orIf($policyResult);

            // Short-circuit on Forbidden — nothing can override it.
            if ($result->isForbidden()) {
                return $result;
            }
        }

        return $result;
    }
}
