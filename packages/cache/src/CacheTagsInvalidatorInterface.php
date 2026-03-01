<?php

declare(strict_types=1);

namespace Waaseyaa\Cache;

interface CacheTagsInvalidatorInterface
{
    /** @param string[] $tags */
    public function invalidateTags(array $tags): void;
}
