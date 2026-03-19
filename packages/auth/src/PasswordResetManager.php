<?php

declare(strict_types=1);

namespace Waaseyaa\Auth;

final class PasswordResetManager
{
    public function __construct(
        private readonly string $secret,
        private readonly int $tokenLifetimeSeconds = 3600,
    ) {}

    /**
     * Create a password reset token for the given user.
     *
     * Token format: base64(json({userId, email, expiresAt, signature}))
     */
    public function createToken(string $userId, string $email): string
    {
        $expiresAt = time() + $this->tokenLifetimeSeconds;
        $signature = $this->sign($userId, $email, $expiresAt);

        $payload = json_encode([
            'uid' => $userId,
            'email' => $email,
            'exp' => $expiresAt,
            'sig' => $signature,
        ], JSON_THROW_ON_ERROR);

        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    }

    /**
     * Validate a password reset token against the expected user and email.
     */
    public function validateToken(string $token, string $userId, string $email): bool
    {
        $data = $this->decode($token);
        if ($data === null) {
            return false;
        }

        if ($data['uid'] !== $userId || $data['email'] !== $email) {
            return false;
        }

        if ($data['exp'] <= time()) {
            return false;
        }

        $expectedSignature = $this->sign($data['uid'], $data['email'], $data['exp']);

        return hash_equals($expectedSignature, $data['sig']);
    }

    /**
     * Extract the email from a token without full validation.
     */
    public function extractEmail(string $token): ?string
    {
        $data = $this->decode($token);

        return $data['email'] ?? null;
    }

    private function sign(string $userId, string $email, int $expiresAt): string
    {
        $payload = implode(':', [$userId, $email, (string) $expiresAt]);

        return hash_hmac('sha256', $payload, $this->secret);
    }

    /**
     * @return array{uid: string, email: string, exp: int, sig: string}|null
     */
    private function decode(string $token): ?array
    {
        $json = base64_decode(strtr($token, '-_', '+/'), true);
        if ($json === false) {
            return null;
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!is_array($data)
            || !isset($data['uid'], $data['email'], $data['exp'], $data['sig'])
            || !is_string($data['uid'])
            || !is_string($data['email'])
            || !is_int($data['exp'])
            || !is_string($data['sig'])
        ) {
            return null;
        }

        return $data;
    }
}
