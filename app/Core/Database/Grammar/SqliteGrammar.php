<?php

declare(strict_types=1);

namespace App\Core\Database\Grammar;

/**
 * SQLite-specific SQL grammar.
 */
class SqliteGrammar extends Grammar
{
    public function getDriverName(): string
    {
        return 'sqlite';
    }

    public function compileInsertOrIgnore(string $table, array $columns): string
    {
        $quotedTable = $this->quoteIdentifier($table);
        $quotedColumns = array_map(fn($col) => $this->quoteIdentifier($col), $columns);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        return sprintf(
            "INSERT OR IGNORE INTO %s (%s) VALUES (%s)",
            $quotedTable,
            implode(", ", $quotedColumns),
            implode(", ", $placeholders)
        );
    }

    public function compileUpsert(string $table, array $columns, array $uniqueKeys): string
    {
        $quotedTable = $this->quoteIdentifier($table);
        $quotedColumns = array_map(fn($col) => $this->quoteIdentifier($col), $columns);
        $placeholders = array_map(fn($col) => ":$col", $columns);
        $quotedUnique = array_map(fn($col) => $this->quoteIdentifier($col), $uniqueKeys);

        // Filter out keys that shouldn't be updated (the unique keys themselves)
        $updateColumns = array_diff($columns, $uniqueKeys);

        $updates = array_map(
            fn($col) => sprintf("%s = excluded.%s", $this->quoteIdentifier($col), $this->quoteIdentifier($col)),
            $updateColumns
        );

        return sprintf(
            "INSERT INTO %s (%s) VALUES (%s) ON CONFLICT (%s) DO UPDATE SET %s",
            $quotedTable,
            implode(", ", $quotedColumns),
            implode(", ", $placeholders),
            implode(", ", $quotedUnique),
            implode(", ", $updates)
        );
    }

    public function compileAutoIncrement(): string
    {
        return 'INTEGER PRIMARY KEY AUTOINCREMENT';
    }

    public function compileBooleanType(): string
    {
        // SQLite doesn't have a native boolean type, use INTEGER
        return 'INTEGER';
    }

    public function compileBoolean(bool $value): mixed
    {
        // SQLite uses 0/1 for boolean
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
        // SQLite accepts both backticks and double quotes
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    /**
     * SQLite uses REAL for floating point numbers.
     */
    public function compileDecimalType(int $precision = 10, int $scale = 2): string
    {
        return 'REAL';
    }
}
