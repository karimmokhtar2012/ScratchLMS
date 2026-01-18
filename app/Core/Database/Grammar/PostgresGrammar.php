<?php

declare(strict_types=1);

namespace App\Core\Database\Grammar;

/**
 * PostgreSQL-specific SQL grammar.
 */
class PostgresGrammar extends Grammar
{
    public function getDriverName(): string
    {
        return 'pgsql';
    }

    public function compileInsertOrIgnore(string $table, array $columns): string
    {
        $quotedTable = $this->quoteIdentifier($table);
        $quotedColumns = array_map(fn($col) => $this->quoteIdentifier($col), $columns);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        // PostgreSQL uses ON CONFLICT DO NOTHING
        return sprintf(
            "INSERT INTO %s (%s) VALUES (%s) ON CONFLICT DO NOTHING",
            $quotedTable,
            implode(", ", $quotedColumns),
            implode(", ", $placeholders)
        );
    }

    public function compileAutoIncrement(): string
    {
        // PostgreSQL uses SERIAL for auto-increment
        return 'SERIAL PRIMARY KEY';
    }

    public function compileBooleanType(): string
    {
        // PostgreSQL has native BOOLEAN type
        return 'BOOLEAN';
    }

    public function compileBoolean(bool $value): string
    {
        // PostgreSQL accepts TRUE/FALSE keywords
        return $value ? 'TRUE' : 'FALSE';
    }

    public function compileDatetimeType(): string
    {
        return 'TIMESTAMP';
    }

    public function compileCurrentTimestamp(): string
    {
        return 'CURRENT_TIMESTAMP';
    }

    public function quoteIdentifier(string $identifier): string
    {
        // PostgreSQL uses double quotes
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    /**
     * PostgreSQL uses NUMERIC for exact decimals.
     */
    public function compileDecimalType(int $precision = 10, int $scale = 2): string
    {
        return "NUMERIC($precision, $scale)";
    }

    /**
     * PostgreSQL uses DOUBLE PRECISION for floats.
     */
    public function compileFloatType(): string
    {
        return 'DOUBLE PRECISION';
    }

    /**
     * Override CHECK constraint for PostgreSQL compatibility.
     */
    public function compileCheckIn(string $column, array $values): string
    {
        $quotedValues = array_map(fn($v) => "'$v'", $values);
        return sprintf(
            "CHECK (%s IN (%s))",
            $this->quoteIdentifier($column),
            implode(", ", $quotedValues)
        );
    }
}
