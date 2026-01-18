<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Database\Connection;
use App\Core\Database\Grammar\Grammar;
use PDO;
use RuntimeException;

/**
 * Database singleton.
 * Provides a single shared database connection throughout the application.
 * 
 * Supports multiple database drivers: SQLite, MySQL, PostgreSQL.
 */
class Database
{
    private static ?Database $instance = null;
    private Connection $connection;

    private function __construct()
    {
        $this->connection = new Connection();
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserializing
    public function __wakeup()
    {
        throw new RuntimeException("Cannot unserialize a singleton.");
    }

    /**
     * Get the singleton instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get the underlying PDO connection.
     */
    public function getConnection(): PDO
    {
        return $this->connection->getPdo();
    }

    /**
     * Get the Connection wrapper instance.
     */
    public function connection(): Connection
    {
        return $this->connection;
    }

    /**
     * Get the SQL grammar for the current database type.
     * Use this for database-specific SQL generation.
     */
    public function getGrammar(): Grammar
    {
        return $this->connection->getGrammar();
    }

    /**
     * Get the current database driver name.
     */
    public function getDriverName(): string
    {
        return $this->connection->getDriverName();
    }

    /**
     * Execute a prepared statement with parameters.
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return \PDOStatement|false
     */
    public function query(string $sql, array $params = []): false|\PDOStatement
    {
        return $this->connection->query($sql, $params);
    }

    /**
     * Fetch all results from a query.
     *
     * @param string $sql SQL query
     * @param array $params Parameters to bind
     * @return array Results
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->connection->fetchAll($sql, $params);
    }

    /**
     * Fetch a single row from a query.
     *
     * @param string $sql SQL query
     * @param array $params Parameters to bind
     * @return array|null Single row or null
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        return $this->connection->fetchOne($sql, $params);
    }

    /**
     * Execute an INSERT OR IGNORE statement (database-agnostic).
     * Use this instead of raw "INSERT OR IGNORE" or "INSERT IGNORE" SQL.
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return bool Success
     */
    public function insertOrIgnore(string $table, array $data): bool
    {
        return $this->connection->insertOrIgnore($table, $data);
    }

    /**
     * Get the last inserted ID.
     */
    public function lastInsertId(): string|false
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Begin a database transaction.
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit the current transaction.
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Roll back the current transaction.
     */
    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * Check if currently in a transaction.
     */
    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }

    /**
     * Reset the singleton instance.
     * Useful for testing or when switching database configurations.
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }
}
