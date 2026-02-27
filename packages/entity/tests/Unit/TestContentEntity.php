<?php

declare(strict_types=1);

namespace Aurora\Entity\Tests\Unit;

use Aurora\Entity\ContentEntityBase;

/**
 * Concrete content entity subclass for testing ContentEntityBase.
 */
class TestContentEntity extends ContentEntityBase
{
    protected string $entityTypeId = 'test_content';
}
