<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA foreign_keys = OFF;');

    echo "🔄 Fixing completed_lessons Foreign Key...\n";

    $db->beginTransaction();

    // 1. Rename existing table
    $db->exec("ALTER TABLE completed_lessons RENAME TO completed_lessons_old");

    // 2. Create new table with correct FK
    $db->exec("\n        CREATE TABLE completed_lessons (
            user_id INTEGER NOT NULL,
            lesson_id INTEGER NOT NULL,
            completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, lesson_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
        )
    ");

    // 3. Copy data
    $db->exec("INSERT INTO completed_lessons (user_id, lesson_id, completed_at) 
               SELECT user_id, lesson_id, completed_at FROM completed_lessons_old");

    // 4. Drop old table
    $db->exec("DROP TABLE completed_lessons_old");

    $db->commit();
    $db->exec('PRAGMA foreign_keys = ON;');

    echo "✅ completed_lessons FK Fixed\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Fix Failed: " . $e->getMessage() . "\n";
    exit(1);
}

