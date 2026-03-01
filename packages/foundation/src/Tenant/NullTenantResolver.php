<?php

declare(strict_types=1);

namespace Waaseyaa\Foundation\Tenant;

final class NullTenantResolver implements TenantResolverInterface
{
    public function resolve(array $requestAttributes = []): ?string
    {
        return null;
    }
}
