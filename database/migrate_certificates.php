<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "🔄 Migrating Database for Certificates...\n";

    // SQLite Add Column (Certificate ID)
    // Note: SQLite ALTER TABLE ADD COLUMN is limited. We do it carefully.
    
    // Check if column exists first to avoid error
    $cols = $db->query("PRAGMA table_info(enrollments)")->fetchAll(PDO::FETCH_COLUMN, 1);
    
    if (!in_array('certificate_id', $cols)) {
        $db->exec("ALTER TABLE enrollments ADD COLUMN certificate_id TEXT DEFAULT NULL");
        echo "   + Added certificate_id column\n";
    }
    
    if (!in_array('certified_at', $cols)) {
        $db->exec("ALTER TABLE enrollments ADD COLUMN certified_at DATETIME DEFAULT NULL");
        echo "   + Added certified_at column\n";
    }

    echo "✅ Certificate Columns Added\n";

} catch (Exception $e) {
    echo "❌ Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}

