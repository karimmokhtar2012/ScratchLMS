<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $db->exec('PRAGMA foreign_keys = OFF;');
    $db->beginTransaction();

    // Expand questions table (types + grading metadata)
    $cols = $db->query("PRAGMA table_info(questions)")->fetchAll(PDO::FETCH_ASSOC);
    $colNames = array_column($cols, 'name');

    if (!empty($colNames)) {
        $db->exec("ALTER TABLE questions RENAME TO questions_old;");

        $db->exec("
            CREATE TABLE questions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                quiz_id INTEGER NOT NULL,
                type TEXT NOT NULL CHECK(type IN (
                    'single_choice',
                    'multi_choice',
                    'true_false',
                    'short_text',
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
            INSERT INTO questions (id, quiz_id, type, question_text, media_url, points, options, correct_answers, explanation, sort_order, created_at)
            SELECT id, quiz_id, type, question_text, media_url, points, options, correct_answers, explanation, sort_order, created_at
            FROM questions_old;
        ");

        $db->exec("DROP TABLE questions_old;");
    }

    // Expand question bank types
    $bankCols = $db->query("PRAGMA table_info(bank_questions)")->fetchAll(PDO::FETCH_ASSOC);
    $bankNames = array_column($bankCols, 'name');
    if (!empty($bankNames)) {
        $db->exec("ALTER TABLE bank_questions RENAME TO bank_questions_old;");
        $db->exec("
            CREATE TABLE bank_questions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                bank_id INTEGER NOT NULL,
                type TEXT NOT NULL CHECK(type IN (
                    'single_choice',
                    'multi_choice',
                    'true_false',
                    'short_text',
                    'fill_blank',
                    'matching',
                    'essay',
                    'video_submission'
                )),
                question_text TEXT NOT NULL,
                options TEXT,
                correct_answers TEXT,
                explanation TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (bank_id) REFERENCES question_banks(id) ON DELETE CASCADE
            );
        ");
        $db->exec("
            INSERT INTO bank_questions (id, bank_id, type, question_text, options, correct_answers, explanation, created_at)
            SELECT id, bank_id, type, question_text, options, correct_answers, explanation, created_at
            FROM bank_questions_old;
        ");
        $db->exec("DROP TABLE bank_questions_old;");
    }

    // Add grading_status to quiz_attempts
    $attemptCols = $db->query("PRAGMA table_info(quiz_attempts)")->fetchAll(PDO::FETCH_ASSOC);
    $attemptNames = array_column($attemptCols, 'name');
    if (!in_array('grading_status', $attemptNames, true)) {
        $db->exec("ALTER TABLE quiz_attempts ADD COLUMN grading_status TEXT DEFAULT 'final';");
    }

    // Grading queue for essays/video submissions
    $db->exec("
        CREATE TABLE IF NOT EXISTS grading_queue (
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

    // Drip rules per lesson
    $db->exec("
        CREATE TABLE IF NOT EXISTS lesson_drip_rules (
            lesson_id INTEGER PRIMARY KEY,
            unlock_after_days INTEGER,
            unlock_after_lesson_id INTEGER,
            unlock_after_percent INTEGER,
            FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
            FOREIGN KEY (unlock_after_lesson_id) REFERENCES lessons(id) ON DELETE SET NULL
        );
    ");

    $db->commit();
    $db->exec('PRAGMA foreign_keys = ON;');
    echo "✅ Quiz extensions migration complete\n";
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $db->exec('PRAGMA foreign_keys = ON;');
    echo "❌ Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}
