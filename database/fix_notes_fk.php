<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA foreign_keys = OFF;');

    echo "🔄 Fixing notes Foreign Key...\n";

    $db->beginTransaction();

    // Rename existing table
    $db->exec("ALTER TABLE notes RENAME TO notes_old");

    // Create new table with correct FK
    $db->exec("
        CREATE TABLE notes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            lesson_id INTEGER NOT NULL,
            content TEXT NOT NULL,
            timestamp INTEGER DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
        )
    ");

    // Copy data
    $db->exec("INSERT INTO notes (id, user_id, lesson_id, content, timestamp, created_at)
               SELECT id, user_id, lesson_id, content, timestamp, created_at FROM notes_old");

    // Drop old table
    $db->exec("DROP TABLE notes_old");

    $db->commit();
    $db->exec('PRAGMA foreign_keys = ON;');

    echo "✅ notes FK Fixed\n";
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Fix Failed: " . $e->getMessage() . "\n";
    exit(1);
}
