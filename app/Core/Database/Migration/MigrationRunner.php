<?php

declare(strict_types=1);

namespace App\Core\Database\Migration;

use App\Core\Database;
use RuntimeException;

/**
 * Migration runner.
 * Handles reading, executing, and tracking migrations.
 */
class MigrationRunner
{
    private Database $db;
    private string $migrationsPath;
    private string $migrationsTable = 'migrations';

    public function __construct(?string $migrationsPath = null)
    {
        $this->db = Database::getInstance();
        $this->migrationsPath = $migrationsPath ?? __DIR__ . '/../../../../database/migrations';
    }

    /**
     * Run all pending migrations.
     *
     * @return array List of executed migration files
     */
    public function run(): array
    {
        $this->ensureMigrationsTableExists();
        
        $pending = $this->getPendingMigrations();
        $executed = [];
        
        foreach ($pending as $file) {
            $this->runMigration($file);
            $executed[] = $file;
        }
        
        return $executed;
    }

    /**
     * Rollback the last batch of migrations.
     *
     * @return array List of rolled back migration files
     */
    public function rollback(): array
    {
        $this->ensureMigrationsTableExists();
        
        $batch = $this->getLastBatch();
        if ($batch === 0) {
            return [];
        }
        
        $migrations = $this->getMigrationsForBatch($batch);
        $rolledBack = [];
        
        // Rollback in reverse order
        foreach (array_reverse($migrations) as $migration) {
            $this->rollbackMigration($migration);
            $rolledBack[] = $migration;
        }
        
        return $rolledBack;
    }

    /**
     * Fresh migration: drop all tables and re-run all migrations.
     *
     * @return array List of executed migration files
     */
    public function fresh(): array
    {
        // Get all tables
        $tables = $this->getAllTables();
        
        // Disable foreign key checks for clean drop
        $this->disableForeignKeyChecks();
        
        // Drop all tables
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
        
        // Re-enable foreign key checks
        $this->enableForeignKeyChecks();
        
        // Run all migrations
        return $this->run();
    }

    /**
     * Get the status of all migrations.
     *
     * @return array Migration status info
     */
    public function status(): array
    {
        $this->ensureMigrationsTableExists();
        
        $allFiles = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();
        
        $status = [];
        foreach ($allFiles as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $status[] = [
                'name' => $name,
                'file' => $file,
                'executed' => in_array($name, $executed),
            ];
        }
        
        return $status;
    }

    /**
     * Ensure the migrations tracking table exists.
     */
    private function ensureMigrationsTableExists(): void
    {
        if (!Schema::hasTable($this->migrationsTable)) {
            Schema::create($this->migrationsTable, function (Blueprint $table) {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
            });
        }
    }

    /**
     * Get list of pending migrations.
     */
    private function getPendingMigrations(): array
    {
        $files = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();
        
        $pending = [];
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            if (!in_array($name, $executed)) {
                $pending[] = $file;
            }
        }
        
        // Sort by filename (which includes timestamp)
        sort($pending);
        
        return $pending;
    }

    /**
     * Get all migration files from the migrations directory.
     */
    private function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }
        
        $files = glob($this->migrationsPath . '/*.php');
        return $files ?: [];
    }

    /**
     * Get list of already executed migration names.
     */
    private function getExecutedMigrations(): array
    {
        $results = $this->db->fetchAll("SELECT migration FROM {$this->migrationsTable}");
        return array_column($results, 'migration');
    }

    /**
     * Run a single migration file.
     */
    private function runMigration(string $file): void
    {
        $migration = $this->resolveMigration($file);
        
        echo "⏳ Running: " . basename($file) . "\n";
        
        $migration->up();
        
        $this->recordMigration($file);
        
        echo "✅ Migrated: " . basename($file) . "\n";
    }

    /**
     * Rollback a single migration.
     */
    private function rollbackMigration(string $name): void
    {
        $file = $this->migrationsPath . '/' . $name . '.php';
        
        if (!file_exists($file)) {
            echo "⚠️  Migration file not found: $name\n";
            return;
        }
        
        $migration = $this->resolveMigration($file);
        
        echo "⏳ Rolling back: $name\n";
        
        $migration->down();
        
        $this->removeMigrationRecord($name);
        
        echo "✅ Rolled back: $name\n";
    }

    /**
     * Resolve a migration file into an instance.
     */
    private function resolveMigration(string $file): Migration
    {
        require_once $file;
        
        // Extract class name from filename
        // Format: 2024_01_01_000001_create_users_table.php
        $filename = pathinfo($file, PATHINFO_FILENAME);
        
        // Convert snake_case to PascalCase for class name
        // Remove date prefix first
        $parts = explode('_', $filename);
        $nameParts = array_slice($parts, 4); // Skip date parts
        $className = implode('', array_map('ucfirst', $nameParts));
        
        // Try both namespaced and global class
        $classes = [
            "Database\\Migrations\\$className",
            $className,
        ];
        
        foreach ($classes as $class) {
            if (class_exists($class)) {
                return new $class();
            }
        }
        
        throw new RuntimeException("Migration class not found for: $file. Expected class: $className");
    }

    /**
     * Record that a migration was executed.
     */
    private function recordMigration(string $file): void
    {
        $name = pathinfo($file, PATHINFO_FILENAME);
        $batch = $this->getNextBatch();
        
        $this->db->query(
            "INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (:name, :batch)",
            [':name' => $name, ':batch' => $batch]
        );
    }

    /**
     * Remove a migration record.
     */
    private function removeMigrationRecord(string $name): void
    {
        $this->db->query(
            "DELETE FROM {$this->migrationsTable} WHERE migration = :name",
            [':name' => $name]
        );
    }

    /**
     * Get the last batch number.
     */
    private function getLastBatch(): int
    {
        $result = $this->db->fetchAll("SELECT MAX(batch) as batch FROM {$this->migrationsTable}");
        return (int)($result[0]['batch'] ?? 0);
    }

    /**
     * Get the next batch number.
     */
    private function getNextBatch(): int
    {
        return $this->getLastBatch() + 1;
    }

    /**
     * Get migrations for a specific batch.
     */
    private function getMigrationsForBatch(int $batch): array
    {
        $results = $this->db->fetchAll(
            "SELECT migration FROM {$this->migrationsTable} WHERE batch = :batch ORDER BY id ASC",
            [':batch' => $batch]
        );
        return array_column($results, 'migration');
    }

    /**
     * Get all table names in the database.
     */
    private function getAllTables(): array
    {
        $driver = $this->db->getDriverName();
        
        $sql = match ($driver) {
            'sqlite' => "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'",
            'mysql' => "SELECT TABLE_NAME as name FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()",
            'pgsql' => "SELECT table_name as name FROM information_schema.tables WHERE table_schema = 'public'",
            default => throw new RuntimeException("Unsupported driver: $driver"),
        };
        
        $results = $this->db->fetchAll($sql);
        return array_column($results, 'name');
    }

    /**
     * Disable foreign key checks (for fresh migrations).
     */
    private function disableForeignKeyChecks(): void
    {
        $driver = $this->db->getDriverName();
        $pdo = $this->db->getConnection();
        
        match ($driver) {
            'sqlite' => $pdo->exec('PRAGMA foreign_keys = OFF'),
            'mysql' => $pdo->exec('SET FOREIGN_KEY_CHECKS = 0'),
            'pgsql' => null, // PostgreSQL handles this differently
            default => null,
        };
    }

    /**
     * Enable foreign key checks.
     */
    private function enableForeignKeyChecks(): void
    {
        $driver = $this->db->getDriverName();
        $pdo = $this->db->getConnection();
        
        match ($driver) {
            'sqlite' => $pdo->exec('PRAGMA foreign_keys = ON'),
            'mysql' => $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'),
            'pgsql' => null,
            default => null,
        };
    }
}
