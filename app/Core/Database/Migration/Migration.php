<?php

declare(strict_types=1);

namespace App\Core\Database\Migration;

use App\Core\Database;

/**
 * Base migration class.
 * All migrations should extend this class.
 */
abstract class Migration
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Run the migration (create tables, add columns, etc.).
     */
    abstract public function up(): void;

    /**
     * Reverse the migration (drop tables, remove columns, etc.).
     */
    abstract public function down(): void;

    /**
     * Get the migration description (optional).
     */
    public function getDescription(): string
    {
        return '';
    }
}
