<?php

declare(strict_types=1);

namespace Waaseyaa\Entity\Tests\Unit;

use Waaseyaa\Entity\EntityBase;

/**
 * Concrete entity subclass for testing EntityBase.
 */
class TestEntity extends EntityBase
{
    protected string $entityTypeId = 'test_entity';
}
