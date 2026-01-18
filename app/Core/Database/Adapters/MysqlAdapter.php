<?php

declare(strict_types=1);

namespace App\Core\Database\Adapters;

use App\Core\Database\Grammar\Grammar;
use App\Core\Database\Grammar\MysqlGrammar;
use PDO;
use RuntimeException;

/**
 * MySQL/MariaDB database adapter.
 */
class MysqlAdapter implements AdapterInterface
{
    private MysqlGrammar $grammar;

    public function __construct()
    {
        $this->grammar = new MysqlGrammar();
    }

    public function getDriverName(): string
    {
        return 'mysql';
    }

    public function connect(array $config): PDO
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        if (empty($database)) {
            throw new RuntimeException('MySQL database name is not configured.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $database,
            $charset
        );

        $pdo = new PDO($dsn, $username, $password, $this->getDefaultOptions());

        // Set MySQL-specific modes for strict SQL
        $pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");

        return $pdo;
    }

    public function getGrammar(): Grammar
    {
        return $this->grammar;
    }

    public function enableForeignKeys(PDO $pdo): void
    {
        // Foreign keys are enabled by default in InnoDB
        // But we can explicitly set it
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function getDefaultOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];
    }
}
