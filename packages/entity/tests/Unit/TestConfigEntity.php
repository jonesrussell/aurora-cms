<?php

declare(strict_types=1);

namespace Aurora\Entity\Tests\Unit;

use Aurora\Entity\ConfigEntityBase;

/**
 * Concrete config entity subclass for testing ConfigEntityBase.
 */
class TestConfigEntity extends ConfigEntityBase
{
    protected string $entityTypeId = 'test_config';
}
