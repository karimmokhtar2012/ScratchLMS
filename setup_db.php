<?php

declare(strict_types=1);

$databaseDir = __DIR__ . '/database';
$databaseFile = $databaseDir . '/database.sqlite';

if (!is_dir($databaseDir)) {
    mkdir($databaseDir, 0755, true);
    echo "Created directory: {$databaseDir}\n";
}

if (!file_exists($databaseFile)) {
    touch($databaseFile);
    echo "Created database file: {$databaseFile}\n";
} else {
    echo "Database file already exists: {$databaseFile}\n";
}

