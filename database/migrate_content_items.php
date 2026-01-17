<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance();

echo "🚀 Migrating Content Items Model (Plan A)...
";

try {
    // 1. content_items
    $db->query("CREATE TABLE IF NOT EXISTS content_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        course_id INTEGER NOT NULL,
        module_id INTEGER NOT NULL,
        item_type TEXT NOT NULL, -- 'lesson', 'quiz', 'test'
        item_id INTEGER NOT NULL,
        position INTEGER NOT NULL,
        display_group_id INTEGER DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
    )");
    echo "✅ Table 'content_items' created.
";

    // 2. content_item_rules
    $db->query("CREATE TABLE IF NOT EXISTS content_item_rules (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        item_id INTEGER NOT NULL,
        rule_type TEXT NOT NULL,
        params_json TEXT NOT NULL,
        version INTEGER DEFAULT 1,
        FOREIGN KEY (item_id) REFERENCES content_items(id) ON DELETE CASCADE
    )");
    echo "✅ Table 'content_item_rules' created.
";

    // 3. content_prerequisites
    $db->query("CREATE TABLE IF NOT EXISTS content_prerequisites (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        item_id INTEGER NOT NULL,
        prerequisite_item_id INTEGER NOT NULL,
        relation_type TEXT DEFAULT 'completion', -- 'completion', 'score'
        FOREIGN KEY (item_id) REFERENCES content_items(id) ON DELETE CASCADE,
        FOREIGN KEY (prerequisite_item_id) REFERENCES content_items(id) ON DELETE CASCADE
    )");
    echo "✅ Table 'content_prerequisites' created.
";

    // 4. content_progress
    $db->query("CREATE TABLE IF NOT EXISTS content_progress (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        item_id INTEGER NOT NULL,
        status TEXT DEFAULT 'not_started', -- 'not_started', 'in_progress', 'completed'
        completed_at DATETIME DEFAULT NULL,
        score REAL DEFAULT NULL,
        attempts INTEGER DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES content_items(id) ON DELETE CASCADE
    )");
    echo "✅ Table 'content_progress' created.
";

    echo "🎉 Content Items Model Migration Successful!
";

} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "
";
    exit(1);
}
