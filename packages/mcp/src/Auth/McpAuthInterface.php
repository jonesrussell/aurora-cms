<?php

declare(strict_types=1);

namespace Waaseyaa\Mcp\Auth;

use Waaseyaa\Access\AccountInterface;

interface McpAuthInterface
{
    /**
     * Authenticate from the Authorization header value.
     *
     * @param string|null $authorizationHeader The raw Authorization header value.
     * @return AccountInterface|null The authenticated account, or null if auth fails.
     */
    public function authenticate(?string $authorizationHeader): ?AccountInterface;
}
