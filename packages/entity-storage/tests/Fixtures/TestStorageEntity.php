<?php

declare(strict_types=1);

namespace Waaseyaa\EntityStorage\Tests\Fixtures;

use Waaseyaa\Entity\ContentEntityBase;

/**
 * Test entity class for storage tests.
 */
class TestStorageEntity extends ContentEntityBase
{
    public function __construct(
        array $values = [],
        string $entityTypeId = 'test_entity',
        array $entityKeys = [],
        array $fieldDefinitions = [],
    ) {
        parent::__construct($values, $entityTypeId, $entityKeys, $fieldDefinitions);
    }
}
