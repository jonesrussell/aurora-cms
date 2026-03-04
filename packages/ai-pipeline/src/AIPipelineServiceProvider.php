<?php

declare(strict_types=1);

namespace Waaseyaa\AI\Pipeline;

use Waaseyaa\Entity\EntityType;
use Waaseyaa\Foundation\ServiceProvider\ServiceProvider;

final class AIPipelineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->entityType(new EntityType(
            id: 'pipeline',
            label: 'Pipeline',
            class: Pipeline::class,
            keys: ['id' => 'id', 'label' => 'label'],
        ));
    }
}
