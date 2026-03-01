<?php

declare(strict_types=1);

namespace Waaseyaa\Cache;

interface CacheFactoryInterface
{
    public function get(string $bin): CacheBackendInterface;
}
