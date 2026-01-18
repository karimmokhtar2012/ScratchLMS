<?php

declare(strict_types=1);

namespace App\Core\Database\Adapters;

use App\Core\Database\Grammar\Grammar;
use App\Core\Database\Grammar\SqliteGrammar;
use PDO;
use RuntimeException;

/**
 * SQLite database adapter.
 */
class SqliteAdapter implements AdapterInterface
{
    private SqliteGrammar $grammar;

    public function __construct()
    {
        $this->grammar = new SqliteGrammar();
    }

    public function getDriverName(): string
    {
        return 'sqlite';
    }

    public function connect(array $config): PDO
    {
        $database = $config['database'] ?? '';

        if (empty($database)) {
            throw new RuntimeException('SQLite database path is not configured.');
        }

        // Ensure the database directory exists
        $directory = dirname($database);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new RuntimeException("Failed to create database directory: $directory");
            }
        }

        // Create the database file if it doesn't exist
        if (!file_exists($database)) {
            touch($database);
        }

        $dsn = 'sqlite:' . $database;

        $pdo = new PDO($dsn, null, null, $this->getDefaultOptions());

        // Enable foreign keys
        if ($config['foreign_keys'] ?? true) {
            $this->enableForeignKeys($pdo);
        }

        return $pdo;
    }

    public function getGrammar(): Grammar
    {
        return $this->grammar;
    }

    public function enableForeignKeys(PDO $pdo): void
    {
        $pdo->exec('PRAGMA foreign_keys = ON;');
    }

    public function getDefaultOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }
}
