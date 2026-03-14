<?php

declare(strict_types=1);

namespace Waaseyaa\AdminBridge;

use Waaseyaa\Foundation\ServiceProvider\ServiceProvider;
use Waaseyaa\Routing\RouteBuilder;
use Waaseyaa\Routing\WaaseyaaRouter;

final class AdminBridgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(CatalogBuilder::class, fn() =>
            new CatalogBuilder($this->resolve(\Waaseyaa\Entity\EntityTypeManagerInterface::class))
        );
    }

    public function routes(WaaseyaaRouter $router): void
    {
        // Bootstrap endpoint
        $router->addRoute('admin.bootstrap', RouteBuilder::create('/admin/bootstrap')
            ->controller(AdminBootstrapController::class . '::__invoke')
            ->methods('GET')
            ->requireAuthentication()
            ->build());

        // SPA catch-all (must be registered last)
        $router->addRoute('admin.spa', RouteBuilder::create('/admin/{path}')
            ->controller(AdminSpaController::class . '::__invoke')
            ->methods('GET')
            ->requirement('path', '.*')
            ->allowAll()
            ->build());
    }
}
