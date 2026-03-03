<?php

declare(strict_types=1);

namespace Waaseyaa\User;

use Waaseyaa\Access\AccountInterface;

/**
 * Dev-only admin account with all permissions.
 *
 * Used as a fallback when running under PHP's built-in server (cli-server)
 * and no session is active. MUST NOT be used in production.
 */
final class DevAdminAccount implements AccountInterface
{
    public function id(): int
    {
        return 1;
    }

    public function hasPermission(string $permission): bool
    {
        return true;
    }

    /** @return string[] */
    public function getRoles(): array
    {
        return ['administrator'];
    }

    public function isAuthenticated(): bool
    {
        return true;
    }
}
