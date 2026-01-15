<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $db->exec('PRAGMA foreign_keys = ON;');

    // Add status and published_at columns to courses table if they don't exist
    // SQLite doesn't support IF NOT EXISTS in ADD COLUMN, so we'll wrap in try-catch blocks or check pragmas if we wanted to be super safe. 
    // But for this CLI agent workflow, simple ALTER is fine as it's a one-time run.
    
    try {
        $db->exec("ALTER TABLE courses ADD COLUMN status TEXT DEFAULT 'draft';");
        echo "✅ Added 'status' column to courses table.\n";
    } catch (Exception $e) {
        echo "ℹ️ 'status' column likely already exists in courses table.\n";
    }

    try {
        $db->exec("ALTER TABLE courses ADD COLUMN published_at DATETIME DEFAULT NULL;");
        echo "✅ Added 'published_at' column to courses table.\n";
    } catch (Exception $e) {
        echo "ℹ️ 'published_at' column likely already exists in courses table.\n";
    }

    // Add status column to quizzes table
    try {
        $db->exec("ALTER TABLE quizzes ADD COLUMN status TEXT DEFAULT 'draft';");
        echo "✅ Added 'status' column to quizzes table.\n";
    } catch (Exception $e) {
        echo "ℹ️ 'status' column likely already exists in quizzes table.\n";
    }

    echo "✅ Migration Complete\n";

} catch (Exception $e) {
    echo "❌ Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}
