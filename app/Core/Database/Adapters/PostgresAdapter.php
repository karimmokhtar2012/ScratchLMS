<?php

declare(strict_types=1);

namespace App\Core\Database\Adapters;

use App\Core\Database\Grammar\Grammar;
use App\Core\Database\Grammar\PostgresGrammar;
use PDO;
use RuntimeException;

/**
 * PostgreSQL database adapter.
 */
class PostgresAdapter implements AdapterInterface
{
    private PostgresGrammar $grammar;

    public function __construct()
    {
        $this->grammar = new PostgresGrammar();
    }

    public function getDriverName(): string
    {
        return 'pgsql';
    }

    public function connect(array $config): PDO
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 5432;
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? 'postgres';
        $password = $config['password'] ?? '';
        $schema = $config['schema'] ?? 'public';

        if (empty($database)) {
            throw new RuntimeException('PostgreSQL database name is not configured.');
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $host,
            $port,
            $database
        );

        $pdo = new PDO($dsn, $username, $password, $this->getDefaultOptions());

        // Set the default schema
        if ($schema !== 'public') {
            $pdo->exec("SET search_path TO " . $pdo->quote($schema));
        }

        return $pdo;
    }

    public function getGrammar(): Grammar
    {
        return $this->grammar;
    }

    public function enableForeignKeys(PDO $pdo): void
    {
        // PostgreSQL has foreign keys enabled by default
        // No action needed
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
