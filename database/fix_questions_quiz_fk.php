<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $db->exec('PRAGMA foreign_keys = OFF;');
    $db->beginTransaction();

    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='questions'")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($tables)) {
        $db->exec("ALTER TABLE questions RENAME TO questions_old;");

        $db->exec("
            CREATE TABLE questions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                quiz_id INTEGER NOT NULL,
                type TEXT NOT NULL CHECK(type IN (
                    'single_choice',
                    'multi_choice',
                    'true_false',
                    'fill_blank',
                    'matching',
                    'essay',
                    'video_submission'
                )),
                question_text TEXT NOT NULL,
                media_url TEXT,
                points INTEGER DEFAULT 1,
                options TEXT,
                correct_answers TEXT,
                explanation TEXT,
                grading_policy TEXT DEFAULT 'auto',
                ai_rubric TEXT,
                sort_order INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
            );
        ");

        $db->exec("
            INSERT INTO questions (id, quiz_id, type, question_text, media_url, points, options, correct_answers, explanation, grading_policy, ai_rubric, sort_order, created_at)
            SELECT id, quiz_id, type, question_text, media_url, points, options, correct_answers, explanation, grading_policy, ai_rubric, sort_order, created_at
            FROM questions_old;
        ");

        $db->exec("DROP TABLE questions_old;");
    }

    $db->commit();
    $db->exec('PRAGMA foreign_keys = ON;');
    echo "✅ Questions foreign key fixed\n";
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $db->exec('PRAGMA foreign_keys = ON;');
    echo "❌ Fix failed: " . $e->getMessage() . "\n";
    exit(1);
}
