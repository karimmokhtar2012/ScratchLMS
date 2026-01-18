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

    $db->query("CREATE TABLE IF NOT EXISTS content_rule_definitions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        rule_type TEXT NOT NULL,
        version INTEGER DEFAULT 1,
        schema_json TEXT NOT NULL,
        ui_schema_json TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ Table 'content_rule_definitions' created.
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

    $driver = $db->getConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $db->query("CREATE UNIQUE INDEX IF NOT EXISTS idx_content_progress_user_item ON content_progress (user_id, item_id)");
        $db->query("CREATE INDEX IF NOT EXISTS idx_content_items_course_position ON content_items (course_id, position)");
        $db->query("CREATE INDEX IF NOT EXISTS idx_content_item_rules_item ON content_item_rules (item_id)");
        $db->query("CREATE INDEX IF NOT EXISTS idx_content_prereq_item ON content_prerequisites (item_id)");
        $db->query("CREATE INDEX IF NOT EXISTS idx_content_prereq_prereq_item ON content_prerequisites (prerequisite_item_id)");
        $db->query("CREATE UNIQUE INDEX IF NOT EXISTS idx_content_rule_definitions_type_version ON content_rule_definitions (rule_type, version)");
    } else {
        $indexCheck = $db->fetchAll("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'content_progress' AND index_name = 'idx_content_progress_user_item'");
        if (empty($indexCheck)) {
            $db->query("CREATE UNIQUE INDEX idx_content_progress_user_item ON content_progress (user_id, item_id)");
        }

        $indexCheck = $db->fetchAll("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'content_items' AND index_name = 'idx_content_items_course_position'");
        if (empty($indexCheck)) {
            $db->query("CREATE INDEX idx_content_items_course_position ON content_items (course_id, position)");
        }

        $indexCheck = $db->fetchAll("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'content_item_rules' AND index_name = 'idx_content_item_rules_item'");
        if (empty($indexCheck)) {
            $db->query("CREATE INDEX idx_content_item_rules_item ON content_item_rules (item_id)");
        }

        $indexCheck = $db->fetchAll("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'content_prerequisites' AND index_name = 'idx_content_prereq_item'");
        if (empty($indexCheck)) {
            $db->query("CREATE INDEX idx_content_prereq_item ON content_prerequisites (item_id)");
        }

        $indexCheck = $db->fetchAll("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'content_prerequisites' AND index_name = 'idx_content_prereq_prereq_item'");
        if (empty($indexCheck)) {
            $db->query("CREATE INDEX idx_content_prereq_prereq_item ON content_prerequisites (prerequisite_item_id)");
        }

        $indexCheck = $db->fetchAll("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'content_rule_definitions' AND index_name = 'idx_content_rule_definitions_type_version'");
        if (empty($indexCheck)) {
            $db->query("CREATE UNIQUE INDEX idx_content_rule_definitions_type_version ON content_rule_definitions (rule_type, version)");
        }
    }
    echo "✅ Indexes created.
";

    echo "🎉 Content Items Model Migration Successful!
";

} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "
";
    exit(1);
}
