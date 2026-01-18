#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * ScratchLMS Database Migration CLI
 * 
 * Usage:
 *   php migrate.php              Run all pending migrations
 *   php migrate.php --rollback   Rollback the last batch
 *   php migrate.php --fresh      Drop all tables and re-run migrations
 *   php migrate.php --status     Show migration status
 *   php migrate.php --help       Show this help
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database\Migration\MigrationRunner;

// Parse command line arguments
$options = getopt('', ['rollback', 'fresh', 'status', 'help']);

// Show help
if (isset($options['help']) || in_array('--help', $argv) || in_array('-h', $argv)) {
    echo <<<HELP
    
╔═══════════════════════════════════════════════════════════════╗
║              ScratchLMS Database Migration CLI                 ║
╚═══════════════════════════════════════════════════════════════╝

Usage:
  php migrate.php              Run all pending migrations
  php migrate.php --rollback   Rollback the last batch of migrations
  php migrate.php --fresh      Drop all tables and re-run all migrations
  php migrate.php --status     Show the status of all migrations
  php migrate.php --help       Show this help message

Database Configuration:
  Edit config/database.php or create a .env file from .env.example
  
  Supported drivers:
    - sqlite (default) - File-based, no server needed
    - mysql            - MySQL/MariaDB server
    - pgsql            - PostgreSQL server

Examples:
  # Run migrations with SQLite (default)
  php migrate.php
  
  # Run migrations with MySQL
  DB_DRIVER=mysql DB_HOST=localhost DB_DATABASE=lms php migrate.php
  
  # Fresh install (WARNING: destroys all data)
  php migrate.php --fresh

HELP;
    exit(0);
}

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║              ScratchLMS Database Migration                    ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

try {
    $runner = new MigrationRunner();
    
    // Show current driver
    $db = \App\Core\Database::getInstance();
    echo "📦 Database driver: " . strtoupper($db->getDriverName()) . "\n\n";
    
    if (isset($options['status'])) {
        // Show migration status
        echo "📋 Migration Status:\n";
        echo str_repeat('-', 60) . "\n";
        
        $status = $runner->status();
        
        if (empty($status)) {
            echo "   No migrations found in database/migrations/\n";
        } else {
            foreach ($status as $migration) {
                $icon = $migration['executed'] ? '✅' : '⏳';
                $state = $migration['executed'] ? 'Executed' : 'Pending';
                echo "   $icon {$migration['name']} [$state]\n";
            }
        }
        
    } elseif (isset($options['rollback'])) {
        // Rollback last batch
        echo "🔄 Rolling back last batch...\n\n";
        
        $rolledBack = $runner->rollback();
        
        if (empty($rolledBack)) {
            echo "ℹ️  Nothing to rollback.\n";
        } else {
            echo "\n✅ Rolled back " . count($rolledBack) . " migration(s).\n";
        }
        
    } elseif (isset($options['fresh'])) {
        // Fresh migration
        echo "⚠️  WARNING: This will destroy ALL data in the database!\n";
        echo "   Press Ctrl+C to cancel, or wait 3 seconds to continue...\n";
        sleep(3);
        
        echo "\n🔄 Dropping all tables and running fresh migrations...\n\n";
        
        $executed = $runner->fresh();
        
        echo "\n✅ Fresh migration complete. Executed " . count($executed) . " migration(s).\n";
        
    } else {
        // Run pending migrations
        echo "🔄 Running pending migrations...\n\n";
        
        $executed = $runner->run();
        
        if (empty($executed)) {
            echo "ℹ️  No pending migrations.\n";
        } else {
            echo "\n✅ Executed " . count($executed) . " migration(s).\n";
        }
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    if (getenv('APP_DEBUG') === 'true') {
        echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    }
    
    exit(1);
}
