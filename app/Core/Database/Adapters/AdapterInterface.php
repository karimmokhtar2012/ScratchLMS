<?php

declare(strict_types=1);

namespace App\Core\Database\Adapters;

use App\Core\Database\Grammar\Grammar;
use PDO;

/**
 * Interface for database adapters.
 * Each database type (SQLite, MySQL, PostgreSQL) implements this interface
 * to handle driver-specific connection and configuration.
 */
interface AdapterInterface
{
    /**
     * Get the driver name identifier.
     */
    public function getDriverName(): string;

    /**
     * Create a PDO connection using the provided configuration.
     *
     * @param array $config Database configuration array
     * @return PDO Configured PDO connection
     */
    public function connect(array $config): PDO;

    /**
     * Get the SQL grammar for this database type.
     */
    public function getGrammar(): Grammar;

    /**
     * Enable foreign key constraints for this connection.
     * Some databases (like SQLite) require explicit enabling.
     *
     * @param PDO $pdo The PDO connection
     */
    public function enableForeignKeys(PDO $pdo): void;

    /**
     * Get the default PDO options for this adapter.
     *
     * @return array PDO options array
     */
    public function getDefaultOptions(): array;
}
