<?php

declare(strict_types=1);

namespace Aurora\Entity\Tests\Unit;

use Aurora\Entity\EntityBase;

/**
 * Concrete entity subclass for testing EntityBase.
 */
class TestEntity extends EntityBase
{
    protected string $entityTypeId = 'test_entity';
}
