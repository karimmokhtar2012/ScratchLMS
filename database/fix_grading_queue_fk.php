<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $db->exec('PRAGMA foreign_keys = OFF;');
    $db->beginTransaction();

    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='grading_queue'")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($tables)) {
        $db->exec("ALTER TABLE grading_queue RENAME TO grading_queue_old;");

        $db->exec("
            CREATE TABLE grading_queue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                attempt_id INTEGER NOT NULL,
                question_id INTEGER NOT NULL,
                status TEXT DEFAULT 'pending_review',
                ai_score REAL,
                ai_feedback TEXT,
                final_score REAL,
                reviewer_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME,
                FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
                FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
                FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL
            );
        ");

        $db->exec("
            INSERT INTO grading_queue (id, attempt_id, question_id, status, ai_score, ai_feedback, final_score, reviewer_id, created_at, updated_at)
            SELECT id, attempt_id, question_id, status, ai_score, ai_feedback, final_score, reviewer_id, created_at, updated_at
            FROM grading_queue_old;
        ");

        $db->exec("DROP TABLE grading_queue_old;");
    }

    $db->commit();
    $db->exec('PRAGMA foreign_keys = ON;');
    echo "✅ Grading queue foreign keys fixed\n";
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $db->exec('PRAGMA foreign_keys = ON;');
    echo "❌ Fix failed: " . $e->getMessage() . "\n";
    exit(1);
}
