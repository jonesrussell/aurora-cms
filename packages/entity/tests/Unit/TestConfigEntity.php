<?php

declare(strict_types=1);

namespace Waaseyaa\Entity\Tests\Unit;

use Waaseyaa\Entity\ConfigEntityBase;

/**
 * Concrete config entity subclass for testing ConfigEntityBase.
 */
class TestConfigEntity extends ConfigEntityBase
{
    protected string $entityTypeId = 'test_config';
}
