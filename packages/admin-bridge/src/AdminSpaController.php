<?php

declare(strict_types=1);

namespace Waaseyaa\AdminBridge;

final class AdminSpaController
{
    public function __construct(
        private readonly string $adminPath,
    ) {}

    public function __invoke(): string
    {
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-cache');
        return file_get_contents($this->adminPath . '/index.html');
    }
}
