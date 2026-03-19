<?php

declare(strict_types=1);

namespace Waaseyaa\Auth\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Auth\PasswordResetManager;

#[CoversClass(PasswordResetManager::class)]
final class PasswordResetManagerTest extends TestCase
{
    private PasswordResetManager $manager;

    protected function setUp(): void
    {
        $this->manager = new PasswordResetManager(
            secret: 'test-secret-key-for-hmac',
            tokenLifetimeSeconds: 3600,
        );
    }

    public function testCreateTokenReturnsNonEmptyString(): void
    {
        $token = $this->manager->createToken('user-123', 'alice@test.com');

        $this->assertNotEmpty($token);
    }

    public function testValidateTokenReturnsTrueForValidToken(): void
    {
        $token = $this->manager->createToken('user-123', 'alice@test.com');

        $result = $this->manager->validateToken($token, 'user-123', 'alice@test.com');

        $this->assertTrue($result);
    }

    public function testValidateTokenReturnsFalseForWrongUser(): void
    {
        $token = $this->manager->createToken('user-123', 'alice@test.com');

        $result = $this->manager->validateToken($token, 'user-456', 'alice@test.com');

        $this->assertFalse($result);
    }

    public function testValidateTokenReturnsFalseForWrongEmail(): void
    {
        $token = $this->manager->createToken('user-123', 'alice@test.com');

        $result = $this->manager->validateToken($token, 'user-123', 'bob@test.com');

        $this->assertFalse($result);
    }

    public function testValidateTokenReturnsFalseForTamperedToken(): void
    {
        $token = $this->manager->createToken('user-123', 'alice@test.com');

        $result = $this->manager->validateToken($token . 'tampered', 'user-123', 'alice@test.com');

        $this->assertFalse($result);
    }

    public function testValidateTokenReturnsFalseForExpiredToken(): void
    {
        $manager = new PasswordResetManager(
            secret: 'test-secret-key-for-hmac',
            tokenLifetimeSeconds: -1,
        );

        $token = $manager->createToken('user-123', 'alice@test.com');

        $result = $manager->validateToken($token, 'user-123', 'alice@test.com');

        $this->assertFalse($result);
    }

    public function testDifferentUsersGetDifferentTokens(): void
    {
        $token1 = $this->manager->createToken('user-123', 'alice@test.com');
        $token2 = $this->manager->createToken('user-456', 'bob@test.com');

        $this->assertNotSame($token1, $token2);
    }

    public function testExtractEmailFromToken(): void
    {
        $token = $this->manager->createToken('user-123', 'alice@test.com');

        $email = $this->manager->extractEmail($token);

        $this->assertSame('alice@test.com', $email);
    }

    public function testExtractEmailReturnsNullForInvalidToken(): void
    {
        $email = $this->manager->extractEmail('garbage-token');

        $this->assertNull($email);
    }
}
