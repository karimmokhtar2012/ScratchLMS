<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $db->exec('PRAGMA foreign_keys = OFF;');
    $db->beginTransaction();

    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='quiz_attempts'")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($tables)) {
        $db->exec("ALTER TABLE quiz_attempts RENAME TO quiz_attempts_old;");

        $db->exec("
            CREATE TABLE quiz_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                quiz_id INTEGER NOT NULL,
                score INTEGER,
                is_passed INTEGER DEFAULT 0,
                started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                completed_at DATETIME,
                answers TEXT,
                grading_status TEXT DEFAULT 'final',
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
            );
        ");

        $db->exec("
            INSERT INTO quiz_attempts (id, user_id, quiz_id, score, is_passed, started_at, completed_at, answers, grading_status)
            SELECT id, user_id, quiz_id, score, is_passed, started_at, completed_at, answers, grading_status
            FROM quiz_attempts_old;
        ");

        $db->exec("DROP TABLE quiz_attempts_old;");
    }

    $db->commit();
    $db->exec('PRAGMA foreign_keys = ON;');
    echo "✅ Quiz attempts foreign key fixed\n";
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $db->exec('PRAGMA foreign_keys = ON;');
    echo "❌ Fix failed: " . $e->getMessage() . "\n";
    exit(1);
}
