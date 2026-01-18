<?php

declare(strict_types=1);

namespace App\Core\Database\Migration;

use App\Core\Database;
use Closure;

/**
 * Schema facade for creating and modifying database tables.
 */
class Schema
{
    /**
     * Create a new table.
     *
     * @param string $table Table name
     * @param Closure $callback Function receiving Blueprint instance
     */
    public static function create(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table, true);
        $callback($blueprint);
        
        $sql = $blueprint->toSql();
        
        if (!empty($sql)) {
            Database::getInstance()->getConnection()->exec($sql);
        }
    }

    /**
     * Modify an existing table.
     *
     * @param string $table Table name
     * @param Closure $callback Function receiving Blueprint instance
     */
    public static function table(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table, false);
        $callback($blueprint);
        
        // Execute ALTER statements
        // Note: This is a simplified implementation
        // Full ALTER TABLE support varies significantly between databases
    }

    /**
     * Drop a table if it exists.
     *
     * @param string $table Table name
     */
    public static function dropIfExists(string $table): void
    {
        $grammar = Database::getInstance()->getGrammar();
        $sql = $grammar->compileDropTable($table);
        Database::getInstance()->getConnection()->exec($sql);
    }

    /**
     * Drop a table.
     *
     * @param string $table Table name
     */
    public static function drop(string $table): void
    {
        self::dropIfExists($table);
    }

    /**
     * Check if a table exists.
     *
     * @param string $table Table name
     * @return bool
     */
    public static function hasTable(string $table): bool
    {
        $db = Database::getInstance();
        $driver = $db->getDriverName();
        
        $sql = match ($driver) {
            'sqlite' => "SELECT name FROM sqlite_master WHERE type='table' AND name = :table",
            'mysql' => "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table",
            'pgsql' => "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = :table",
            default => throw new \RuntimeException("Unsupported driver: $driver"),
        };

        $result = $db->fetchAll($sql, [':table' => $table]);
        return !empty($result);
    }

    /**
     * Check if a column exists in a table.
     *
     * @param string $table Table name
     * @param string $column Column name
     * @return bool
     */
    public static function hasColumn(string $table, string $column): bool
    {
        $db = Database::getInstance();
        $driver = $db->getDriverName();
        
        $sql = match ($driver) {
            'sqlite' => "PRAGMA table_info($table)",
            'mysql' => "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column",
            'pgsql' => "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = :table AND column_name = :column",
            default => throw new \RuntimeException("Unsupported driver: $driver"),
        };

        if ($driver === 'sqlite') {
            $result = $db->fetchAll($sql);
            foreach ($result as $row) {
                if ($row['name'] === $column) {
                    return true;
                }
            }
            return false;
        }

        $result = $db->fetchAll($sql, [':table' => $table, ':column' => $column]);
        return !empty($result);
    }

    /**
     * Rename a table.
     *
     * @param string $from Old table name
     * @param string $to New table name
     */
    public static function rename(string $from, string $to): void
    {
        $db = Database::getInstance();
        $grammar = $db->getGrammar();
        
        $sql = sprintf(
            "ALTER TABLE %s RENAME TO %s",
            $grammar->quoteIdentifier($from),
            $grammar->quoteIdentifier($to)
        );
        
        $db->getConnection()->exec($sql);
    }

    /**
     * Execute raw SQL for complex operations.
     *
     * @param string $sql SQL statement
     */
    public static function raw(string $sql): void
    {
        Database::getInstance()->getConnection()->exec($sql);
    }
}
