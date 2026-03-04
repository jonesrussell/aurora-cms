<?php

declare(strict_types=1);

return [
    // SQLite database path. Override with WAASEYAA_DB env var.
    'database' => __DIR__ . '/../waaseyaa.sqlite',

    // Config sync directory. Override with WAASEYAA_CONFIG_DIR env var.
    'config_dir' => __DIR__ . '/sync',

    // Allowed CORS origins for the admin SPA.
    'cors_origins' => ['http://localhost:3000', 'http://127.0.0.1:3000'],
];
