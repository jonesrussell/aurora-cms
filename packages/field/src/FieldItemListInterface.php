<?php

declare(strict_types=1);

namespace Aurora\Field;

use Aurora\TypedData\ListInterface;

interface FieldItemListInterface extends ListInterface
{
    public function getFieldDefinition(): FieldDefinitionInterface;

    public function __get(string $name): mixed;
}
