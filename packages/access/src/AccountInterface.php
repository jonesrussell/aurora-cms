<?php

declare(strict_types=1);

namespace Aurora\Access;

/**
 * Represents a user account for access checking purposes.
 */
interface AccountInterface
{
    /**
     * Returns the account ID.
     */
    public function id(): int|string;

    /**
     * Check whether the account has a given permission.
     */
    public function hasPermission(string $permission): bool;

    /**
     * Returns the role IDs assigned to this account.
     *
     * @return string[]
     */
    public function getRoles(): array;

    /**
     * Whether this is an authenticated (non-anonymous) account.
     */
    public function isAuthenticated(): bool;
}
