<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;

// Define Base Path
const BASE_PATH = __DIR__ . '/../';

// Load Composer Autoloader
if (file_exists(BASE_PATH . 'vendor/autoload.php')) {
    require_once BASE_PATH . 'vendor/autoload.php';
} else {
    // In production, composer autoload is mandatory.
    die('Please run "composer install" to generate the autoloader.');
}

// Global Translation Helper
function __(string $key): string {
    return \App\Core\Lang::getInstance()->get($key);
}

// Basic Environment Loader (for development purposes)
if (file_exists(BASE_PATH . '.env')) {
    $envLines = file(BASE_PATH . '.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $_ENV[trim($parts[0])] = trim($parts[1]);
        }
    }
}

// Initialize Router
$router = new Router();

// Load Routes
require_once BASE_PATH . 'routes/web.php';

// Dispatch
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
