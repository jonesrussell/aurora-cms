<?php

declare(strict_types=1);

namespace Waaseyaa\Auth;

use Waaseyaa\Entity\EntityTypeManager;
use Waaseyaa\Foundation\Middleware\HttpMiddlewareInterface;
use Waaseyaa\Foundation\ServiceProvider\ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(AuthManager::class, fn() => new AuthManager());

        $this->singleton(RateLimiter::class, fn() => new RateLimiter());

        $this->singleton(PasswordResetManager::class, fn() => new PasswordResetManager(
            secret: $this->config['auth_secret'] ?? $this->config['app_secret'] ?? 'change-me',
            tokenLifetimeSeconds: (int) ($this->config['password_reset_lifetime'] ?? 3600),
        ));

        $this->singleton(EmailVerifier::class, fn() => new EmailVerifier(
            secret: $this->config['auth_secret'] ?? $this->config['app_secret'] ?? 'change-me',
            urlLifetimeSeconds: (int) ($this->config['email_verification_lifetime'] ?? 3600),
        ));

        $this->singleton(TwoFactorManager::class, fn() => new TwoFactorManager());
    }

    /**
     * @return list<HttpMiddlewareInterface>
     */
    public function middleware(EntityTypeManager $entityTypeManager): array
    {
        return [];
    }
}
