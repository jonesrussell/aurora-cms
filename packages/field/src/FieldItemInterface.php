<?php

declare(strict_types=1);

namespace Aurora\Field;

use Aurora\TypedData\ComplexDataInterface;

interface FieldItemInterface extends ComplexDataInterface
{
    public function isEmpty(): bool;

    public function getFieldDefinition(): FieldDefinitionInterface;

    /** @return string[] */
    public static function propertyDefinitions(): array;

    public static function mainPropertyName(): string;
}
