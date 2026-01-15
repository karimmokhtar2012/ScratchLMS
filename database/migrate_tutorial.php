<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "🔄 Migrating Database for Tutorial Tracking...\n";

    // Check if column exists first
    $cols = $db->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1);
    
    if (!in_array('seen_tutorial', $cols)) {
        $db->exec("ALTER TABLE users ADD COLUMN seen_tutorial INTEGER DEFAULT 0");
        echo "   + Added seen_tutorial column to users table\n";
    }

    echo "✅ Migration Complete\n";

} catch (Exception $e) {
    echo "❌ Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}