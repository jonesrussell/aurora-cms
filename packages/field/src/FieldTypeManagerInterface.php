<?php

declare(strict_types=1);

namespace Aurora\Field;

use Aurora\Plugin\PluginManagerInterface;

interface FieldTypeManagerInterface extends PluginManagerInterface
{
    public function getDefaultSettings(string $fieldType): array;

    /** @return array<string, array{type: string, description?: string}> */
    public function getColumns(string $fieldType): array;
}
