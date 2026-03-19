<?php

declare(strict_types=1);

namespace Waaseyaa\Auth\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Waaseyaa\Foundation\Middleware\HttpHandlerInterface;
use Waaseyaa\Foundation\Middleware\HttpMiddlewareInterface;

final class AuthenticateMiddleware implements HttpMiddlewareInterface
{
    public function __construct(
        private readonly string $loginUrl = '/login',
    ) {}

    public function process(Request $request, HttpHandlerInterface $next): Response
    {
        $uid = $_SESSION['waaseyaa_uid'] ?? '';

        if ($uid !== '' && $uid !== 0) {
            return $next->handle($request);
        }

        if ($request->headers->get('X-Inertia') === 'true') {
            return new Response('', 409, [
                'X-Inertia-Location' => $this->loginUrl,
            ]);
        }

        return new Response('', 302, [
            'Location' => $this->loginUrl,
        ]);
    }
}
