<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $db->exec('PRAGMA foreign_keys = ON;');

    // Enrollments Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS enrollments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            course_id INTEGER NOT NULL,
            progress_percent REAL DEFAULT 0,
            is_completed INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        );
    ");

    // Completed Lessons Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS completed_lessons (
            user_id INTEGER NOT NULL,
            lesson_id INTEGER NOT NULL,
            completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, lesson_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
        );
    ");

    // xAPI Logs Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS xapi_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            lesson_id INTEGER NOT NULL,
            verb TEXT NOT NULL,
            meta_data TEXT, -- JSON string
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
        );
    ");

    echo "✅ Tracking Tables Created\n";

} catch (Exception $e) {
    echo "❌ Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}

