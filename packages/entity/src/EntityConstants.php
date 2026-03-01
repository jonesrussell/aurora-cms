<?php

declare(strict_types=1);

namespace Waaseyaa\Entity;

/**
 * Constants for the entity system.
 */
final class EntityConstants
{
    /** Returned by storage save() when a new entity was inserted. */
    public const SAVED_NEW = 1;

    /** Returned by storage save() when an existing entity was updated. */
    public const SAVED_UPDATED = 2;
}
