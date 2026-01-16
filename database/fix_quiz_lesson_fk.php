<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $db->exec('PRAGMA foreign_keys = OFF;');
    $db->beginTransaction();

    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='quizzes'")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($tables)) {
        $db->exec("ALTER TABLE quizzes RENAME TO quizzes_old;");

        $db->exec("
            CREATE TABLE quizzes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lesson_id INTEGER NOT NULL UNIQUE,
                time_limit INTEGER DEFAULT 0,
                passing_score INTEGER DEFAULT 70,
                allowed_attempts INTEGER DEFAULT 0,
                is_randomized INTEGER DEFAULT 0,
                show_correct_answers INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                status TEXT DEFAULT 'draft',
                FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
            );
        ");

        $db->exec("
            INSERT INTO quizzes (id, lesson_id, time_limit, passing_score, allowed_attempts, is_randomized, show_correct_answers, created_at, status)
            SELECT id, lesson_id, time_limit, passing_score, allowed_attempts, is_randomized, show_correct_answers, created_at, status
            FROM quizzes_old;
        ");

        $db->exec("DROP TABLE quizzes_old;");
    }

    $db->commit();
    $db->exec('PRAGMA foreign_keys = ON;');
    echo "✅ Quiz foreign key fixed\n";
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $db->exec('PRAGMA foreign_keys = ON;');
    echo "❌ Fix failed: " . $e->getMessage() . "\n";
    exit(1);
}
