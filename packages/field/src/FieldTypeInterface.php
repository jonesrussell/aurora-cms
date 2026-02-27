<?php

declare(strict_types=1);

namespace Aurora\Field;

use Aurora\Plugin\PluginInspectionInterface;

interface FieldTypeInterface extends PluginInspectionInterface
{
    /** @return array<string, array{type: string, description?: string}> */
    public static function schema(): array;

    /** @return array<string, mixed> */
    public static function defaultSettings(): array;

    public static function defaultValue(): mixed;

    public static function jsonSchema(): array;
}
