<?php

declare(strict_types=1);

namespace Waaseyaa\Auth;

final class EmailVerifier
{
    public function __construct(
        private readonly string $secret,
        private readonly int $urlLifetimeSeconds = 3600,
    ) {
    }

    /**
     * Generate a signed verification URL.
     */
    public function generateUrl(string $baseUrl, string $userId, string $email): string
    {
        $expires = time() + $this->urlLifetimeSeconds;
        $hash = $this->hashEmail($email);
        $signature = $this->sign($userId, $email, $expires);

        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl . $separator . http_build_query([
            'id' => $userId,
            'hash' => $hash,
            'expires' => $expires,
            'signature' => $signature,
        ]);
    }

    /**
     * Verify a signed email verification URL.
     */
    public function verify(
        string $userId,
        string $email,
        int $expires,
        string $hash,
        string $signature,
    ): bool {
        if ($expires <= time()) {
            return false;
        }

        if (!hash_equals($this->hashEmail($email), $hash)) {
            return false;
        }

        $expectedSignature = $this->sign($userId, $email, $expires);

        return hash_equals($expectedSignature, $signature);
    }

    private function hashEmail(string $email): string
    {
        return hash_hmac('sha256', $email, $this->secret);
    }

    private function sign(string $userId, string $email, int $expires): string
    {
        $payload = implode(':', [$userId, $email, (string) $expires]);

        return hash_hmac('sha256', $payload, $this->secret);
    }
}
