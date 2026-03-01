<?php

declare(strict_types=1);

namespace Waaseyaa\Mcp;

use Waaseyaa\Routing\AuroraRouter;
use Waaseyaa\Routing\RouteBuilder;

final readonly class McpRouteProvider
{
    public function registerRoutes(AuroraRouter $router): void
    {
        $router->addRoute(
            'mcp.endpoint',
            RouteBuilder::create('/mcp')
                ->controller('Waaseyaa\\Mcp\\McpEndpoint::handle')
                ->methods('POST', 'GET')
                ->build(),
        );

        $router->addRoute(
            'mcp.server_card',
            RouteBuilder::create('/.well-known/mcp.json')
                ->controller('Waaseyaa\\Mcp\\McpServerCard::toJson')
                ->methods('GET')
                ->allowAll()
                ->build(),
        );
    }
}
