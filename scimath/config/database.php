<?php

/**
 * Database connection configuration.
 *
 * Values are read from environment variables so the same code deploys to
 * dev/staging/production without edits. Defaults below match the local dev
 * database created for this project (see README.md "Local development").
 */

return [
    'driver'   => 'mysql',
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'academic_competition',
    'username' => getenv('DB_USERNAME') ?: 'acs_user',
    'password' => getenv('DB_PASSWORD') ?: 'dev_password_change_me',
    'charset'  => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
