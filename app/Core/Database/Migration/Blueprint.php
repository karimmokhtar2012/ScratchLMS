<?php

declare(strict_types=1);

namespace App\Core\Database\Migration;

use App\Core\Database;
use App\Core\Database\Grammar\Grammar;

/**
 * Blueprint for defining table schema.
 * Provides a fluent API for defining columns and constraints.
 */
class Blueprint
{
    private string $table;
    private array $columns = [];
    private array $constraints = [];
    private array $indexes = [];
    private Grammar $grammar;
    private bool $creating = true;

    public function __construct(string $table, bool $creating = true)
    {
        $this->table = $table;
        $this->creating = $creating;
        $this->grammar = Database::getInstance()->getGrammar();
    }

    /**
     * Get the table name.
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Add an auto-incrementing primary key column.
     */
    public function id(string $name = 'id'): self
    {
        $this->columns[] = $this->grammar->quoteIdentifier($name) . ' ' . $this->grammar->compileAutoIncrement();
        return $this;
    }

    /**
     * Add an integer column.
     */
    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn($name, $this->grammar->compileIntegerType());
    }

    /**
     * Add a big integer column.
     */
    public function bigInteger(string $name): ColumnDefinition
    {
        $type = $this->grammar->getDriverName() === 'pgsql' ? 'BIGINT' : 'BIGINT';
        return $this->addColumn($name, $type);
    }

    /**
     * Add a string (VARCHAR) column.
     */
    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn($name, $this->grammar->compileStringType($length));
    }

    /**
     * Add a text column.
     */
    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn($name, $this->grammar->compileTextType());
    }

    /**
     * Add a boolean column.
     */
    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn($name, $this->grammar->compileBooleanType());
    }

    /**
     * Add a decimal column.
     */
    public function decimal(string $name, int $precision = 10, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn($name, $this->grammar->compileDecimalType($precision, $scale));
    }

    /**
     * Add a datetime column.
     */
    public function datetime(string $name): ColumnDefinition
    {
        return $this->addColumn($name, $this->grammar->compileDatetimeType());
    }

    /**
     * Add a timestamp column.
     */
    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn($name, $this->grammar->compileDatetimeType());
    }

    /**
     * Add created_at and updated_at timestamp columns.
     */
    public function timestamps(): self
    {
        $datetimeType = $this->grammar->compileDatetimeType();
        $default = $this->grammar->compileCurrentTimestamp();
        
        $this->columns[] = $this->grammar->quoteIdentifier('created_at') . " $datetimeType DEFAULT $default";
        $this->columns[] = $this->grammar->quoteIdentifier('updated_at') . " $datetimeType DEFAULT NULL";
        
        return $this;
    }

    /**
     * Add a foreign key column (integer).
     */
    public function foreignId(string $name): ForeignKeyDefinition
    {
        $this->addColumn($name, $this->grammar->compileIntegerType())->notNull();
        return new ForeignKeyDefinition($this, $name);
    }

    /**
     * Add a column definition.
     */
    private function addColumn(string $name, string $type): ColumnDefinition
    {
        $definition = new ColumnDefinition($this, $name, $type);
        return $definition;
    }

    /**
     * Add a raw column definition (for complex cases).
     */
    public function addRawColumn(string $definition): self
    {
        $this->columns[] = $definition;
        return $this;
    }

    /**
     * Add a unique constraint.
     */
    public function unique(string|array $columns): self
    {
        $this->constraints[] = $this->grammar->compileUnique($columns);
        return $this;
    }

    /**
     * Add a primary key constraint (for composite keys).
     */
    public function primaryKey(array $columns): self
    {
        $quoted = array_map(fn($col) => $this->grammar->quoteIdentifier($col), $columns);
        $this->constraints[] = "PRIMARY KEY (" . implode(", ", $quoted) . ")";
        return $this;
    }

    /**
     * Add a foreign key constraint.
     */
    public function foreign(string $column): ForeignKeyDefinition
    {
        return new ForeignKeyDefinition($this, $column, false);
    }

    /**
     * Add a CHECK constraint for allowed values.
     */
    public function check(string $column, array $allowedValues): self
    {
        $this->constraints[] = $this->grammar->compileCheckIn($column, $allowedValues);
        return $this;
    }

    /**
     * Store a column definition string.
     * Called internally by ColumnDefinition.
     */
    public function storeColumn(string $definition): void
    {
        $this->columns[] = $definition;
    }

    /**
     * Store a constraint string.
     * Called internally by ForeignKeyDefinition.
     */
    public function storeConstraint(string $constraint): void
    {
        $this->constraints[] = $constraint;
    }

    /**
     * Get the grammar instance.
     */
    public function getGrammar(): Grammar
    {
        return $this->grammar;
    }

    /**
     * Compile the blueprint into SQL.
     */
    public function toSql(): string
    {
        if ($this->creating) {
            return $this->grammar->compileCreateTable($this->table, $this->columns, $this->constraints);
        }

        // For ALTER TABLE operations, return individual statements
        // This is a simplified version - full ALTER support would need more work
        return '';
    }

    /**
     * Get all column definitions.
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get all constraints.
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }
}

/**
 * Column definition builder.
 */
class ColumnDefinition
{
    private Blueprint $blueprint;
    private string $name;
    private string $type;
    private bool $nullable = false;
    private mixed $default = null;
    private bool $hasDefault = false;
    private bool $unique = false;

    public function __construct(Blueprint $blueprint, string $name, string $type)
    {
        $this->blueprint = $blueprint;
        $this->name = $name;
        $this->type = $type;
    }

    public function __destruct()
    {
        $this->build();
    }

    /**
     * Make the column nullable.
     */
    public function nullable(): self
    {
        $this->nullable = true;
        return $this;
    }

    /**
     * Make the column NOT NULL.
     */
    public function notNull(): self
    {
        $this->nullable = false;
        return $this;
    }

    /**
     * Set a default value for the column.
     */
    public function default(mixed $value): self
    {
        $this->default = $value;
        $this->hasDefault = true;
        return $this;
    }

    /**
     * Make the column unique.
     */
    public function unique(): self
    {
        $this->unique = true;
        return $this;
    }

    /**
     * Build the column definition and store it.
     */
    private function build(): void
    {
        $grammar = $this->blueprint->getGrammar();
        $sql = $grammar->quoteIdentifier($this->name) . ' ' . $this->type;

        if (!$this->nullable) {
            $sql .= ' NOT NULL';
        }

        if ($this->hasDefault) {
            if ($this->default === null) {
                $sql .= ' DEFAULT NULL';
            } elseif (is_bool($this->default)) {
                $sql .= ' DEFAULT ' . $grammar->compileBoolean($this->default);
            } elseif (is_int($this->default) || is_float($this->default)) {
                $sql .= ' DEFAULT ' . $this->default;
            } elseif ($this->default === 'CURRENT_TIMESTAMP') {
                $sql .= ' DEFAULT ' . $grammar->compileCurrentTimestamp();
            } else {
                $sql .= " DEFAULT '" . addslashes((string)$this->default) . "'";
            }
        }

        if ($this->unique) {
            $sql .= ' UNIQUE';
        }

        $this->blueprint->storeColumn($sql);
    }
}

/**
 * Foreign key definition builder.
 */
class ForeignKeyDefinition
{
    private Blueprint $blueprint;
    private string $column;
    private string $referencedTable = '';
    private string $referencedColumn = 'id';
    private string $onDelete = 'CASCADE';
    private string $onUpdate = 'CASCADE';
    private bool $addColumn;

    public function __construct(Blueprint $blueprint, string $column, bool $addColumn = true)
    {
        $this->blueprint = $blueprint;
        $this->column = $column;
        $this->addColumn = $addColumn;
    }

    public function __destruct()
    {
        $this->build();
    }

    /**
     * Set the referenced table and column.
     */
    public function references(string $column): self
    {
        $this->referencedColumn = $column;
        return $this;
    }

    /**
     * Set the referenced table.
     */
    public function on(string $table): self
    {
        $this->referencedTable = $table;
        return $this;
    }

    /**
     * Set the ON DELETE action.
     */
    public function onDelete(string $action): self
    {
        $this->onDelete = strtoupper($action);
        return $this;
    }

    /**
     * Set the ON UPDATE action.
     */
    public function onUpdate(string $action): self
    {
        $this->onUpdate = strtoupper($action);
        return $this;
    }

    /**
     * Shortcut: Set CASCADE for both ON DELETE and ON UPDATE.
     */
    public function cascadeOnDelete(): self
    {
        $this->onDelete = 'CASCADE';
        return $this;
    }

    /**
     * Build the foreign key constraint and store it.
     */
    private function build(): void
    {
        if (empty($this->referencedTable)) {
            return;
        }

        $grammar = $this->blueprint->getGrammar();
        $constraint = $grammar->compileForeignKey(
            $this->column,
            $this->referencedTable,
            $this->referencedColumn,
            $this->onDelete,
            $this->onUpdate
        );

        $this->blueprint->storeConstraint($constraint);
    }
}
