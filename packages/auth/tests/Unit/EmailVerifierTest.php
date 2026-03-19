<?php

declare(strict_types=1);

namespace Waaseyaa\Auth\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Auth\EmailVerifier;

#[CoversClass(EmailVerifier::class)]
final class EmailVerifierTest extends TestCase
{
    private EmailVerifier $verifier;

    protected function setUp(): void
    {
        $this->verifier = new EmailVerifier(
            secret: 'test-verifier-secret',
            urlLifetimeSeconds: 3600,
        );
    }

    public function testGenerateUrlReturnsSignedUrl(): void
    {
        $url = $this->verifier->generateUrl(
            baseUrl: 'https://app.test/verify-email',
            userId: 'user-123',
            email: 'alice@test.com',
        );

        $this->assertStringStartsWith('https://app.test/verify-email?', $url);
        $this->assertStringContainsString('id=user-123', $url);
        $this->assertStringContainsString('signature=', $url);
        $this->assertStringContainsString('expires=', $url);
    }

    public function testVerifyReturnsTrueForValidUrl(): void
    {
        $url = $this->verifier->generateUrl(
            baseUrl: 'https://app.test/verify-email',
            userId: 'user-123',
            email: 'alice@test.com',
        );

        $params = $this->parseUrlParams($url);

        $result = $this->verifier->verify(
            userId: $params['id'],
            email: 'alice@test.com',
            expires: (int) $params['expires'],
            hash: $params['hash'],
            signature: $params['signature'],
        );

        $this->assertTrue($result);
    }

    public function testVerifyReturnsFalseForWrongEmail(): void
    {
        $url = $this->verifier->generateUrl(
            baseUrl: 'https://app.test/verify-email',
            userId: 'user-123',
            email: 'alice@test.com',
        );

        $params = $this->parseUrlParams($url);

        $result = $this->verifier->verify(
            userId: $params['id'],
            email: 'wrong@test.com',
            expires: (int) $params['expires'],
            hash: $params['hash'],
            signature: $params['signature'],
        );

        $this->assertFalse($result);
    }

    public function testVerifyReturnsFalseForExpiredUrl(): void
    {
        $verifier = new EmailVerifier(
            secret: 'test-verifier-secret',
            urlLifetimeSeconds: -1,
        );

        $url = $verifier->generateUrl(
            baseUrl: 'https://app.test/verify-email',
            userId: 'user-123',
            email: 'alice@test.com',
        );

        $params = $this->parseUrlParams($url);

        $result = $verifier->verify(
            userId: $params['id'],
            email: 'alice@test.com',
            expires: (int) $params['expires'],
            hash: $params['hash'],
            signature: $params['signature'],
        );

        $this->assertFalse($result);
    }

    public function testVerifyReturnsFalseForTamperedSignature(): void
    {
        $url = $this->verifier->generateUrl(
            baseUrl: 'https://app.test/verify-email',
            userId: 'user-123',
            email: 'alice@test.com',
        );

        $params = $this->parseUrlParams($url);

        $result = $this->verifier->verify(
            userId: $params['id'],
            email: 'alice@test.com',
            expires: (int) $params['expires'],
            hash: $params['hash'],
            signature: $params['signature'] . 'tampered',
        );

        $this->assertFalse($result);
    }

    public function testHashObscuresEmail(): void
    {
        $url = $this->verifier->generateUrl(
            baseUrl: 'https://app.test/verify-email',
            userId: 'user-123',
            email: 'alice@test.com',
        );

        $this->assertStringNotContainsString('alice@test.com', $url);
        $this->assertStringContainsString('hash=', $url);
    }

    /**
     * @return array<string, string>
     */
    private function parseUrlParams(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY) ?? '';
        parse_str($query, $params);

        return $params;
    }
}
