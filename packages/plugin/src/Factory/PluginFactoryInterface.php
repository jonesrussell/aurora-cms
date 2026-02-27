<?php

declare(strict_types=1);

namespace Aurora\Plugin\Factory;

use Aurora\Plugin\PluginInspectionInterface;

interface PluginFactoryInterface
{
    public function createInstance(string $pluginId, array $configuration = []): PluginInspectionInterface;
}
