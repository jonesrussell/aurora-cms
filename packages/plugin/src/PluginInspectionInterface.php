<?php

declare(strict_types=1);

namespace Waaseyaa\Plugin;

interface PluginInspectionInterface
{
    public function getPluginId(): string;

    public function getPluginDefinition(): Definition\PluginDefinition;
}
