<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA foreign_keys = OFF;');

    echo "🔄 Fixing xapi_logs Foreign Key...\n";

    $db->beginTransaction();

    // 1. Rename existing table
    $db->exec("ALTER TABLE xapi_logs RENAME TO xapi_logs_old");

    // 2. Create new table with correct FK
    $db->exec("\n        CREATE TABLE xapi_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            lesson_id INTEGER NOT NULL,
            verb TEXT NOT NULL,
            meta_data TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
        )
    ");

    // 3. Copy data
    $db->exec("INSERT INTO xapi_logs (id, user_id, lesson_id, verb, meta_data, created_at) 
               SELECT id, user_id, lesson_id, verb, meta_data, created_at FROM xapi_logs_old");

    // 4. Drop old table
    $db->exec("DROP TABLE xapi_logs_old");

    $db->commit();
    $db->exec('PRAGMA foreign_keys = ON;');

    echo "✅ xapi_logs FK Fixed\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Fix Failed: " . $e->getMessage() . "\n";
    exit(1);
}
