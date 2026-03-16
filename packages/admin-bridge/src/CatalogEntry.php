<?php

declare(strict_types=1);

namespace Waaseyaa\AdminBridge;

/**
 * @deprecated Use Waaseyaa\AdminSurface\Catalog\EntityDefinition instead. Will be removed in v1.0.
 */
final readonly class CatalogEntry
{
    public function __construct(
        public string $id,
        public string $label,
        public CatalogCapabilities $capabilities = new CatalogCapabilities(),
    ) {}

    /** @return array{id: string, label: string, capabilities: array<string, bool>} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'capabilities' => $this->capabilities->toArray(),
        ];
    }
}
