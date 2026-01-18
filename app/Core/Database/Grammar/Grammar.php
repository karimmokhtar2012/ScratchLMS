<?php

declare(strict_types=1);

namespace App\Core\Database\Grammar;

/**
 * Base grammar class for SQL generation.
 * Provides common SQL generation methods with database-specific overrides.
 */
abstract class Grammar
{
    /**
     * Get the driver name this grammar belongs to.
     */
    abstract public function getDriverName(): string;

    /**
     * Compile an INSERT OR IGNORE statement.
     * Different databases handle duplicate key conflicts differently.
     *
     * @param string $table Table name
     * @param array $columns Column names
     * @return string SQL statement prefix (before VALUES)
     */
    abstract public function compileInsertOrIgnore(string $table, array $columns): string;

    /**
     * Get the SQL type for auto-incrementing primary key.
     */
    abstract public function compileAutoIncrement(): string;

    /**
     * Get the SQL type for boolean columns.
     */
    abstract public function compileBooleanType(): string;

    /**
     * Convert a PHP boolean to the database representation.
     *
     * @param bool $value PHP boolean
     * @return mixed Database boolean representation
     */
    abstract public function compileBoolean(bool $value): mixed;

    /**
     * Get the SQL type for datetime columns.
     */
    abstract public function compileDatetimeType(): string;

    /**
     * Get the SQL for current timestamp default.
     */
    abstract public function compileCurrentTimestamp(): string;

    /**
     * Quote an identifier (table or column name).
     *
     * @param string $identifier The identifier to quote
     * @return string Quoted identifier
     */
    abstract public function quoteIdentifier(string $identifier): string;

    /**
     * Get the SQL type for text columns.
     */
    public function compileTextType(): string
    {
        return 'TEXT';
    }

    /**
     * Get the SQL type for integer columns.
     */
    public function compileIntegerType(): string
    {
        return 'INTEGER';
    }

    /**
     * Get the SQL type for decimal/money columns.
     *
     * @param int $precision Total digits
     * @param int $scale Decimal places
     */
    public function compileDecimalType(int $precision = 10, int $scale = 2): string
    {
        return "DECIMAL($precision, $scale)";
    }

    /**
     * Get the SQL type for string/varchar columns.
     *
     * @param int $length Maximum length
     */
    public function compileStringType(int $length = 255): string
    {
        return "VARCHAR($length)";
    }

    /**
     * Compile a CREATE TABLE statement.
     *
     * @param string $table Table name
     * @param array $columns Column definitions
     * @param array $constraints Table constraints (foreign keys, etc.)
     * @return string SQL statement
     */
    public function compileCreateTable(string $table, array $columns, array $constraints = []): string
    {
        $quotedTable = $this->quoteIdentifier($table);
        $columnsSql = implode(",\n    ", $columns);
        
        $sql = "CREATE TABLE IF NOT EXISTS $quotedTable (\n    $columnsSql";
        
        if (!empty($constraints)) {
            $sql .= ",\n    " . implode(",\n    ", $constraints);
        }
        
        $sql .= "\n)";
        
        return $sql;
    }

    /**
     * Compile a DROP TABLE statement.
     *
     * @param string $table Table name
     * @return string SQL statement
     */
    public function compileDropTable(string $table): string
    {
        return "DROP TABLE IF EXISTS " . $this->quoteIdentifier($table);
    }

    /**
     * Compile a foreign key constraint.
     *
     * @param string $column Local column
     * @param string $references Referenced table
     * @param string $referencedColumn Referenced column
     * @param string $onDelete ON DELETE action
     * @param string $onUpdate ON UPDATE action
     * @return string SQL constraint
     */
    public function compileForeignKey(
        string $column,
        string $references,
        string $referencedColumn,
        string $onDelete = 'CASCADE',
        string $onUpdate = 'CASCADE'
    ): string {
        return sprintf(
            "FOREIGN KEY (%s) REFERENCES %s(%s) ON DELETE %s ON UPDATE %s",
            $this->quoteIdentifier($column),
            $this->quoteIdentifier($references),
            $this->quoteIdentifier($referencedColumn),
            $onDelete,
            $onUpdate
        );
    }

    /**
     * Compile a UNIQUE constraint.
     *
     * @param string|array $columns Column(s) that should be unique
     * @return string SQL constraint
     */
    public function compileUnique(string|array $columns): string
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        
        $quoted = array_map(fn($col) => $this->quoteIdentifier($col), $columns);
        return "UNIQUE (" . implode(", ", $quoted) . ")";
    }

    /**
     * Compile a CHECK constraint.
     *
     * @param string $column Column name
     * @param array $values Allowed values
     * @return string SQL constraint
     */
    public function compileCheckIn(string $column, array $values): string
    {
        $quotedValues = array_map(fn($v) => "'$v'", $values);
        return sprintf(
            "CHECK(%s IN (%s))",
            $this->quoteIdentifier($column),
            implode(", ", $quotedValues)
        );
    }
}
