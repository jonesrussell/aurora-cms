<?php

declare(strict_types=1);

namespace Waaseyaa\AdminBridge;

/**
 * @deprecated Use Waaseyaa\AdminSurface\AdminSurfaceServiceProvider routes instead. Will be removed in v1.0.
 */
final readonly class AdminTransportConfig
{
    public function __construct(
        public string $strategy = 'jsonapi',
        public string $apiPath = '/api',
    ) {}

    /** @return array{strategy: string, apiPath: string} */
    public function toArray(): array
    {
        return [
            'strategy' => $this->strategy,
            'apiPath' => $this->apiPath,
        ];
    }
}
