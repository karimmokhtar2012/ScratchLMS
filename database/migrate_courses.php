<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $db->exec('PRAGMA foreign_keys = ON;');

    // Courses Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS courses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            description TEXT,
            price REAL NOT NULL DEFAULT 0.00,
            thumbnail TEXT,
            instructor_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");

    // Modules Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS modules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            course_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        );
    ");

    // Lessons Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS lessons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            module_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            type TEXT NOT NULL CHECK(type IN ('video', 'quiz')) DEFAULT 'video',
            content_url TEXT,
            duration INTEGER,
            sort_order INTEGER NOT NULL DEFAULT 0,
            FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
        );
    ");

    echo "✅ Course Tables Created\n";

} catch (Exception $e) {
    echo "❌ Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}

