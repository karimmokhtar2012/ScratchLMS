<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "🔄 Migrating Database for Content Flow...\n";

    // Check if column exists
    $cols = $db->query("PRAGMA table_info(courses)")->fetchAll(PDO::FETCH_COLUMN, 1);
    
    if (!in_array('content_flow', $cols)) {
        // SQLite doesn't support adding columns with CHECK constraints easily in one go for existing tables in all versions,
        // but we can add the column as TEXT default 'free'.
        $db->exec("ALTER TABLE courses ADD COLUMN content_flow TEXT DEFAULT 'free'");
        echo "   + Added content_flow column to courses\n";
    }

    echo "✅ Content Flow Migration Complete\n";

} catch (Exception $e) {
    echo "❌ Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}

