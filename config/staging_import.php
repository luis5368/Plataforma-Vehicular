<?php

return [
    'enabled' => env('STAGING_IMPORT_ENABLED', false),
    // An additional explicit assertion, not proof of separation from production.
    'database' => env('STAGING_IMPORT_DATABASE'),
    'max_bytes' => 20 * 1024 * 1024,
    'max_rows' => 10000,
    // Only overridden in isolated tests; intentionally not connected to an env var.
    'testing_sqlite' => false,
];
