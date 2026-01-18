<?php

declare(strict_types=1);

/**
 * Database Configuration
 * 
 * This file configures database connections for ScratchLMS.
 * Supports SQLite, MySQL, and PostgreSQL.
 * 
 * Set the DB_DRIVER environment variable to switch databases:
 * - sqlite (default) - File-based, no server needed
 * - mysql - MySQL/MariaDB server
 * - pgsql - PostgreSQL server
 */

// Load helpers if not already loaded
if (!function_exists('env')) {
    require_once __DIR__ . '/../app/helpers.php';
}

// Load .env file if it exists
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath) && function_exists('loadEnvFile')) {
    loadEnvFile($envPath);
}

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    |
    | The default database connection to use. Set via DB_DRIVER env variable.
    | Supported: "sqlite", "mysql", "pgsql"
    |
    */
    'default' => env('DB_DRIVER', 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Configuration for each database driver.
    |
    */
    'connections' => [

        /*
        |--------------------------------------------------------------------------
        | SQLite Configuration
        |--------------------------------------------------------------------------
        |
        | SQLite is a file-based database, perfect for development and small
        | deployments. No server required.
        |
        */
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', __DIR__ . '/../database/database.sqlite'),
            'foreign_keys' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | MySQL Configuration
        |--------------------------------------------------------------------------
        |
        | MySQL/MariaDB is widely available on shared hosting and production
        | servers. Good balance of features and compatibility.
        |
        */
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'scratchlms'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],

        /*
        |--------------------------------------------------------------------------
        | PostgreSQL Configuration
        |--------------------------------------------------------------------------
        |
        | PostgreSQL offers advanced features and is ideal for complex
        | applications and enterprise deployments.
        |
        */
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 5432),
            'database' => env('DB_DATABASE', 'scratchlms'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'schema' => env('DB_SCHEMA', 'public'),
        ],

    ],
];
