<?php

declare(strict_types=1);

namespace App\Core\Database;

use App\Core\Database\Adapters\AdapterInterface;
use App\Core\Database\Adapters\MysqlAdapter;
use App\Core\Database\Adapters\PostgresAdapter;
use App\Core\Database\Adapters\SqliteAdapter;
use App\Core\Database\Grammar\Grammar;
use PDO;
use RuntimeException;

/**
 * Database connection factory.
 * Creates the appropriate adapter based on configuration and returns a PDO connection.
 */
class Connection
{
    private PDO $pdo;
    private AdapterInterface $adapter;
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? $this->loadConfig();
        $this->adapter = $this->createAdapter();
        $this->pdo = $this->adapter->connect($this->getConnectionConfig());
    }

    /**
     * Load database configuration from config file.
     */
    private function loadConfig(): array
    {
        $configPath = __DIR__ . '/../../../config/database.php';

        if (!file_exists($configPath)) {
            throw new RuntimeException("Database configuration file not found: $configPath");
        }

        return require $configPath;
    }

    /**
     * Get the connection configuration for the current driver.
     */
    private function getConnectionConfig(): array
    {
        // Support both old (flat) and new (connections array) config formats
        if (isset($this->config['connections'])) {
            $driver = $this->config['default'] ?? 'sqlite';
            return $this->config['connections'][$driver] ?? [];
        }

        // Legacy flat config format
        return $this->config;
    }

    /**
     * Get the current driver name.
     */
    public function getDriverName(): string
    {
        // Support both old and new config formats
        if (isset($this->config['connections'])) {
            return $this->config['default'] ?? 'sqlite';
        }
        return $this->config['driver'] ?? 'sqlite';
    }

    /**
     * Create the appropriate adapter based on the driver configuration.
     */
    private function createAdapter(): AdapterInterface
    {
        $driver = $this->getDriverName();

        return match ($driver) {
            'sqlite' => new SqliteAdapter(),
            'mysql' => new MysqlAdapter(),
            'pgsql', 'postgres', 'postgresql' => new PostgresAdapter(),
            default => throw new RuntimeException("Unsupported database driver: $driver"),
        };
    }

    /**
     * Get the PDO connection.
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Get the adapter for this connection.
     */
    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * Get the SQL grammar for the current database type.
     */
    public function getGrammar(): Grammar
    {
        return $this->adapter->getGrammar();
    }

    /**
     * Execute a prepared statement with parameters.
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return \PDOStatement|false
     */
    public function query(string $sql, array $params = []): \PDOStatement|false
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
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
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
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
        $stmt = $this->query($sql, $params);
        $result = $stmt ? $stmt->fetch() : false;
        return $result ?: null;
    }

    /**
     * Execute an INSERT OR IGNORE statement (database-agnostic).
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return bool Success
     */
    public function insertOrIgnore(string $table, array $data): bool
    {
        $columns = array_keys($data);
        $sql = $this->getGrammar()->compileInsertOrIgnore($table, $columns);
        $params = [];

        foreach ($data as $column => $value) {
            $params[":$column"] = $value;
        }

        $stmt = $this->query($sql, $params);
        return $stmt !== false;
    }

    /**
     * Execute an UPSERT statement (Insert on duplicate key update).
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param array $uniqueKeys Keys that determine uniqueness (conflict target)
     * @return bool Success
     */
    public function upsert(string $table, array $data, array $uniqueKeys): bool
    {
        $columns = array_keys($data);
        $sql = $this->getGrammar()->compileUpsert($table, $columns, $uniqueKeys);
        $params = [];

        foreach ($data as $column => $value) {
            $params[":$column"] = $value;
        }

        $stmt = $this->query($sql, $params);
        return $stmt !== false;
    }

    /**
     * Get the last inserted ID.
     */
    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Begin a database transaction.
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit the current transaction.
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Roll back the current transaction.
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Check if currently in a transaction.
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }
}
