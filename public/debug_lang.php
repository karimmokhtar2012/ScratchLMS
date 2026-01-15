<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Lang;

$lang = Lang::getInstance();
echo "Current Lang: " . $lang->current() . "\n";

// Reflection to access private property for debugging (simulated by recreating logic)
$baseDir = dirname(__DIR__) . '/resources/lang/';
$path = $baseDir . 'en/messages.php';

echo "Calculated Path: " . $path . "\n";
echo "File Exists? " . (file_exists($path) ? 'Yes' : 'No') . "\n";

if (file_exists($path)) {
    $data = require $path;
    echo "Data Type: " . gettype($data) . "\n";
    echo "Count: " . (is_array($data) ? count($data) : 'N/A') . "\n";
    echo "Test Key 'nav_features': " . ($data['nav_features'] ?? 'NOT FOUND') . "\n";
}

echo "Lang::get('nav_features'): " . $lang->get('nav_features') . "\n";

