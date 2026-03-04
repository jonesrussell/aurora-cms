<?php

declare(strict_types=1);

namespace Waaseyaa\Foundation\Kernel;

final class ConfigLoader
{
    /**
     * Load configuration from a PHP file that returns an array.
     *
     * @return array<string, mixed>
     */
    public static function load(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $data = require $path;

        return is_array($data) ? $data : [];
    }
}
