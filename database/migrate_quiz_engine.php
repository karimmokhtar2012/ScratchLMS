<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $db->exec('PRAGMA foreign_keys = ON;');

    // Quizzes Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS quizzes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lesson_id INTEGER NOT NULL UNIQUE,
            time_limit INTEGER DEFAULT 0, -- Minutes, 0 = unlimited
            passing_score INTEGER DEFAULT 70, -- Percentage
            allowed_attempts INTEGER DEFAULT 0, -- 0 = unlimited
            is_randomized INTEGER DEFAULT 0, -- Boolean (0/1)
            show_correct_answers INTEGER DEFAULT 1, -- Boolean (0/1)
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
        );
    ");

    // Questions Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS questions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            quiz_id INTEGER NOT NULL,
            type TEXT NOT NULL CHECK(type IN ('single_choice', 'multi_choice', 'true_false', 'short_text')),
            question_text TEXT NOT NULL,
            media_url TEXT,
            points INTEGER DEFAULT 1,
            options TEXT, -- JSON Array of options
            correct_answers TEXT, -- JSON Array of correct values
            explanation TEXT,
            sort_order INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
        );
    ");

    // Quiz Attempts Table (For Tracking Results later)
    $db->exec("
        CREATE TABLE IF NOT EXISTS quiz_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            quiz_id INTEGER NOT NULL,
            score INTEGER,
            is_passed INTEGER DEFAULT 0,
            started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME,
            answers TEXT, -- JSON storing user responses
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
        );
    ");

    echo "✅ Quiz Engine Tables Created\n";

} catch (Exception $e) {
    echo "❌ Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}
