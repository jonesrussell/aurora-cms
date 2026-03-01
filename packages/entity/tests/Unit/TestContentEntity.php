<?php

declare(strict_types=1);

namespace Waaseyaa\Entity\Tests\Unit;

use Waaseyaa\Entity\ContentEntityBase;

/**
 * Concrete content entity subclass for testing ContentEntityBase.
 */
class TestContentEntity extends ContentEntityBase
{
    protected string $entityTypeId = 'test_content';
}
