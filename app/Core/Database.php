<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';

        if (($config['driver'] ?? '') === 'sqlite') {
            $dsn = 'sqlite:' . $config['database'];
            $username = null;
            $password = null;
        } else {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['dbname'],
                $config['charset'] ?? 'utf8mb4'
            );
            $username = $config['username'];
            $password = $config['password'];
        }

        try {
            $this->connection = new PDO(
                $dsn,
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            // Enable Foreign Keys for SQLite
            if (($config['driver'] ?? '') === 'sqlite') {
                $this->connection->exec('PRAGMA foreign_keys = ON;');
            }
        } catch (PDOException $e) {
            throw new RuntimeException("Database Connection Error: " . $e->getMessage());
        }
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserializing
    public function __wakeup()
    {
        throw new RuntimeException("Cannot unserialize a singleton.");
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function query(string $sql, array $params = []): false|\PDOStatement
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Perform an UPSERT (Update or Insert) operation in a DB-agnostic way.
     *
     * @param string $table The table name.
     * @param array $data Associative array of column => value to insert/update.
     * @param array $uniqueKeys Array of column names that form the unique constraint (required for SQLite).
     */
    public function upsert(string $table, array $data, array $uniqueKeys): void
    {
        $driver = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ":$c", $columns);
        
        $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        if ($driver === 'sqlite') {
            $conflictCols = implode(', ', $uniqueKeys);
            $updateSet = [];
            foreach ($columns as $col) {
                if (!in_array($col, $uniqueKeys)) {
                    $updateSet[] = "$col = excluded.$col";
                }
            }
            if (!empty($updateSet)) {
                $sql .= " ON CONFLICT($conflictCols) DO UPDATE SET " . implode(', ', $updateSet);
            } else {
                $sql .= " ON CONFLICT($conflictCols) DO NOTHING";
            }
        } elseif ($driver === 'mysql') {
            $updateSet = [];
            foreach ($columns as $col) {
                if (!in_array($col, $uniqueKeys)) {
                    $updateSet[] = "$col = VALUES($col)";
                }
            }
            if (!empty($updateSet)) {
                $sql .= " ON DUPLICATE KEY UPDATE " . implode(', ', $updateSet);
            }
            // Note: MySQL ON DUPLICATE KEY UPDATE without changes is implicit DO NOTHING-ish, 
            // but usually we want at least one field updated or touched. 
            // If updateSet is empty (only unique keys provided), MySQL effectively does nothing.
        } else {
            throw new RuntimeException("UPSERT not supported for driver: $driver");
        }

        $params = [];
        foreach ($data as $col => $val) {
            $params[":$col"] = $val;
        }

        $this->query($sql, $params);
    }

    /**
     * Perform an INSERT IGNORE operation in a DB-agnostic way.
     * Skips insertion if a unique constraint violation occurs.
     *
     * @param string $table The table name.
     * @param array $data Associative array of column => value.
     */
    public function insertIgnore(string $table, array $data): void
    {
        $driver = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ":$c", $columns);
        
        $ignoreKeyword = ($driver === 'sqlite') ? 'OR IGNORE' : 'IGNORE';
        
        $sql = "INSERT $ignoreKeyword INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $params = [];
        foreach ($data as $col => $val) {
            $params[":$col"] = $val;
        }

        $this->query($sql, $params);
    }
}
