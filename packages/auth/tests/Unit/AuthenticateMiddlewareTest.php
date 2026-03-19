<?php

declare(strict_types=1);

namespace Waaseyaa\Auth\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Waaseyaa\Auth\Middleware\AuthenticateMiddleware;
use Waaseyaa\Foundation\Middleware\HttpHandlerInterface;

#[CoversClass(AuthenticateMiddleware::class)]
final class AuthenticateMiddlewareTest extends TestCase
{
    public function testAuthenticatedUserPassesThrough(): void
    {
        $_SESSION = ['waaseyaa_uid' => 'user-123'];
        $middleware = new AuthenticateMiddleware('/login');
        $request = Request::create('/dashboard', 'GET');

        $handler = $this->createMockHandler(new Response('dashboard', 200));
        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('dashboard', $response->getContent());
    }

    public function testUnauthenticatedUserRedirectsToLogin(): void
    {
        $_SESSION = [];
        $middleware = new AuthenticateMiddleware('/login');
        $request = Request::create('/dashboard', 'GET');

        $handler = $this->createMockHandler(new Response('dashboard', 200));
        $response = $middleware->process($request, $handler);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->headers->get('Location'));
    }

    public function testUnauthenticatedUserWithEmptyUidRedirects(): void
    {
        $_SESSION = ['waaseyaa_uid' => ''];
        $middleware = new AuthenticateMiddleware('/login');
        $request = Request::create('/dashboard', 'GET');

        $handler = $this->createMockHandler(new Response('dashboard', 200));
        $response = $middleware->process($request, $handler);

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testCustomLoginUrl(): void
    {
        $_SESSION = [];
        $middleware = new AuthenticateMiddleware('/auth/sign-in');
        $request = Request::create('/dashboard', 'GET');

        $handler = $this->createMockHandler(new Response());
        $response = $middleware->process($request, $handler);

        $this->assertSame('/auth/sign-in', $response->headers->get('Location'));
    }

    public function testInertiaRequestGets409InsteadOfRedirect(): void
    {
        $_SESSION = [];
        $middleware = new AuthenticateMiddleware('/login');
        $request = Request::create('/dashboard', 'GET');
        $request->headers->set('X-Inertia', 'true');

        $handler = $this->createMockHandler(new Response());
        $response = $middleware->process($request, $handler);

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('/login', $response->headers->get('X-Inertia-Location'));
    }

    private function createMockHandler(Response $response): HttpHandlerInterface
    {
        return new class ($response) implements HttpHandlerInterface {
            public function __construct(private readonly Response $response)
            {
            }

            public function handle(Request $request): Response
            {
                return $this->response;
            }
        };
    }
}
