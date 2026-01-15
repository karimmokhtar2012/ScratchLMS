<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "↑ Migrating Roles V2...
";

    // 1. Insert New Roles
    $insertStmt = $db->prepare("INSERT OR IGNORE INTO roles (name, slug) VALUES (:name, :slug)");
    
    $newRoles = [
        ['name' => 'Master Instructor', 'slug' => 'master_instructor'],
        ['name' => 'Viewer', 'slug' => 'viewer'],
    ];

    foreach ($newRoles as $role) {
        $insertStmt->execute([':name' => $role['name'], ':slug' => $role['slug']]);
    }
    echo "✅ New Roles Inserted
";

    // 2. Add 'can_switch_role' to users table
    try {
        $db->exec("ALTER TABLE users ADD COLUMN can_switch_role INTEGER DEFAULT 0;");
        echo "✅ Added 'can_switch_role' column to users table.
";
    } catch (Exception $e) {
        echo "ℹ 'can_switch_role' column likely already exists.
";
    }

    echo "✅ Migration V2 Complete
";

} catch (Exception $e) {
    echo "❌ Migration Failed: " . $e->getMessage() . "
";
    exit(1);
}
