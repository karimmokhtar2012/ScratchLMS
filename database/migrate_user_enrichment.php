<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "🔄 Migrating Database for User Enrichment...\n";

    $cols = $db->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1);
    
    $newCols = [
        'gender' => 'TEXT DEFAULT NULL',
        'dob' => 'DATE DEFAULT NULL',
        'country' => 'TEXT DEFAULT NULL',
        'phone' => 'TEXT DEFAULT NULL',
        'field_of_work' => 'TEXT DEFAULT NULL',
        'usage_intent' => 'TEXT DEFAULT NULL',
        'preferred_name' => 'TEXT DEFAULT NULL',
        'is_onboarded' => 'INTEGER DEFAULT 0'
    ];

    foreach ($newCols as $col => $type) {
        if (!in_array($col, $cols)) {
            $db->exec("ALTER TABLE users ADD COLUMN $col $type");
            echo "   + Added $col column\n";
        }
    }

    echo "✅ Migration Complete\n";

} catch (Exception $e) {
    echo "❌ Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}