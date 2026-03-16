<?php

declare(strict_types=1);

namespace Waaseyaa\AdminBridge;

use Waaseyaa\Entity\EntityTypeManagerInterface;

/**
 * @deprecated Use Waaseyaa\AdminSurface\Catalog\CatalogBuilder instead. Will be removed in v1.0.
 */
final class CatalogBuilder
{
    private const DEFAULT_CAPABILITIES = [
        'list' => true, 'get' => true, 'create' => false,
        'update' => false, 'delete' => false, 'schema' => true,
    ];

    public function __construct(
        private readonly EntityTypeManagerInterface $entityTypeManager,
    ) {}

    /** @return list<CatalogEntry> */
    public function build(): array
    {
        $entries = [];
        foreach ($this->entityTypeManager->getDefinitions() as $definition) {
            $entries[] = new CatalogEntry(
                id: $definition->id(),
                label: $definition->getLabel(),
                capabilities: new CatalogCapabilities(...self::DEFAULT_CAPABILITIES),
            );
        }
        return $entries;
    }
}
