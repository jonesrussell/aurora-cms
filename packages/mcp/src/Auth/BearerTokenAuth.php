<?php

declare(strict_types=1);

namespace Aurora\Mcp\Auth;

use Aurora\Access\AccountInterface;

final readonly class BearerTokenAuth implements McpAuthInterface
{
    /** @param array<string, AccountInterface> $tokens Token string → account mapping. */
    public function __construct(
        private array $tokens,
    ) {}

    public function authenticate(?string $authorizationHeader): ?AccountInterface
    {
        if ($authorizationHeader === null || $authorizationHeader === '') {
            return null;
        }

        if (!\str_starts_with(\strtolower($authorizationHeader), 'bearer ')) {
            return null;
        }

        $token = \substr($authorizationHeader, 7);

        return $this->tokens[$token] ?? null;
    }
}
