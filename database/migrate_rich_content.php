<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "🔄 Migrating Database to support Rich Content...\n";

    // SQLite requires a table recreation to modify CHECK constraints or add columns efficiently in some versions
    // We will follow the standard SQLite migration pattern: New -> Copy -> Drop -> Rename

    $db->exec('PRAGMA foreign_keys = OFF;');
    $db->beginTransaction();

    // 1. Create New Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS lessons_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            module_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            type TEXT NOT NULL CHECK(type IN ('video', 'quiz', 'article')) DEFAULT 'video',
            content_url TEXT, -- Video URL or External Link
            content_text TEXT, -- Markdown body for Articles
            attachment_url TEXT, -- Downloadable resource
            video_provider TEXT DEFAULT 'local', -- 'local', 'youtube', 'vimeo'
            duration INTEGER,
            sort_order INTEGER NOT NULL DEFAULT 0,
            FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
        );
    ");

    // 2. Copy Data
    // We explicitly map the existing columns. New columns (content_text, attachment_url) will be NULL by default.
    $db->exec(" 
        INSERT INTO lessons_new (id, module_id, title, type, content_url, duration, sort_order)
        SELECT id, module_id, title, type, content_url, duration, sort_order FROM lessons;
    ");

    // 3. Drop Old Table
    $db->exec("DROP TABLE lessons;");

    // 4. Rename New Table
    $db->exec("ALTER TABLE lessons_new RENAME TO lessons;");

    $db->commit();
    $db->exec('PRAGMA foreign_keys = ON;');

    echo "✅ Lessons Table Updated Successfully (Added 'article' type & resource fields)\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}

