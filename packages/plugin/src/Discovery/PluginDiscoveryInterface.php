<?php

declare(strict_types=1);

namespace Aurora\Plugin\Discovery;

use Aurora\Plugin\Definition\PluginDefinition;

interface PluginDiscoveryInterface
{
    /** @return array<string, PluginDefinition> */
    public function getDefinitions(): array;
}
