<?php

declare(strict_types=1);

namespace App\Core\Database\Grammar;

/**
 * MySQL/MariaDB-specific SQL grammar.
 */
class MysqlGrammar extends Grammar
{
    public function getDriverName(): string
    {
        return 'mysql';
    }

    public function compileInsertOrIgnore(string $table, array $columns): string
    {
        $quotedTable = $this->quoteIdentifier($table);
        $quotedColumns = array_map(fn($col) => $this->quoteIdentifier($col), $columns);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        return sprintf(
            "INSERT IGNORE INTO %s (%s) VALUES (%s)",
            $quotedTable,
            implode(", ", $quotedColumns),
            implode(", ", $placeholders)
        );
    }

    public function compileAutoIncrement(): string
    {
        return 'INT AUTO_INCREMENT PRIMARY KEY';
    }

    public function compileBooleanType(): string
    {
        // MySQL uses TINYINT(1) for boolean
        return 'TINYINT(1)';
    }

    public function compileBoolean(bool $value): mixed
    {
        // MySQL uses 0/1 for boolean
        return $value ? 1 : 0;
    }

    public function compileDatetimeType(): string
    {
        return 'DATETIME';
    }

    public function compileCurrentTimestamp(): string
    {
        return 'CURRENT_TIMESTAMP';
    }

    public function quoteIdentifier(string $identifier): string
    {
        // MySQL uses backticks
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * Override CREATE TABLE to add MySQL-specific options.
     */
    public function compileCreateTable(string $table, array $columns, array $constraints = []): string
    {
        $sql = parent::compileCreateTable($table, $columns, $constraints);
        // Add InnoDB engine for foreign key support
        return $sql . " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }
}
