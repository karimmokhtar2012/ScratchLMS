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
}
