<?php

declare(strict_types=1);

/**
 * Global helper functions for ScratchLMS.
 */

if (!function_exists('env')) {
    /**
     * Get an environment variable value.
     *
     * @param string $key Environment variable name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        // Try getenv first
        $value = getenv($key);
        
        if ($value !== false) {
            // Convert string representations to proper types
            return match (strtolower($value)) {
                'true', '(true)' => true,
                'false', '(false)' => false,
                'null', '(null)' => null,
                'empty', '(empty)' => '',
                default => $value,
            };
        }

        // Try $_ENV superglobal
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        // Try $_SERVER superglobal (some setups use this)
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        return $default;
    }
}

if (!function_exists('loadEnvFile')) {
    /**
     * Load environment variables from a .env file.
     * Simple implementation - for production, consider using vlucas/phpdotenv.
     *
     * @param string $path Path to .env file
     * @return bool Success
     */
    function loadEnvFile(string $path): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            // Parse KEY=value
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove surrounding quotes if present
                if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                    $value = $matches[2];
                }

                // Set as environment variable
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }

        return true;
    }
}

if (!function_exists('config')) {
    /**
     * Get a configuration value using dot notation.
     *
     * @param string $key Configuration key (e.g., 'database.default')
     * @param mixed $default Default value if not found
     * @return mixed
     */
    function config(string $key, mixed $default = null): mixed
    {
        static $cache = [];

        $parts = explode('.', $key);
        $file = array_shift($parts);

        // Load config file if not cached
        if (!isset($cache[$file])) {
            $path = __DIR__ . '/../config/' . $file . '.php';
            if (file_exists($path)) {
                $cache[$file] = require $path;
            } else {
                return $default;
            }
        }

        // Navigate to the requested key
        $value = $cache[$file];
        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return $default;
            }
        }

        return $value;
    }
}
