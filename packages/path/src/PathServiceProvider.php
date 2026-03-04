<?php

declare(strict_types=1);

namespace Waaseyaa\Path;

use Waaseyaa\Entity\EntityType;
use Waaseyaa\Foundation\ServiceProvider\ServiceProvider;

final class PathServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->entityType(new EntityType(
            id: 'path_alias',
            label: 'Path Alias',
            class: PathAlias::class,
            keys: ['id' => 'id', 'uuid' => 'uuid', 'label' => 'alias', 'langcode' => 'langcode'],
        ));
    }
}
