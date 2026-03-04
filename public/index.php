<?php

declare(strict_types=1);

// Find autoloader.
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../../autoload.php',
];

foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require $path;
        break;
    }
}

$kernel = new Waaseyaa\Foundation\Kernel\HttpKernel(dirname(__DIR__));
$kernel->handle();
