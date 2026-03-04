<?php

declare(strict_types=1);

return [
    // SQLite database path. Null means "resolve in kernel":
    // WAASEYAA_DB env var -> {projectRoot}/waaseyaa.sqlite fallback.
    // Set an explicit path here to override both.
    'database' => null,

    // Config sync directory. Override with WAASEYAA_CONFIG_DIR env var.
    'config_dir' => getenv('WAASEYAA_CONFIG_DIR') ?: __DIR__ . '/sync',

    // File storage root for LocalFileRepository (media package).
    'files_dir' => getenv('WAASEYAA_FILES_DIR') ?: __DIR__ . '/../files',

    // Allowed CORS origins for the admin SPA.
    'cors_origins' => ['http://localhost:3000', 'http://127.0.0.1:3000'],
];
