<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $db->exec('PRAGMA foreign_keys = ON;');

    echo "↑↑ Migrating Database for Question Banks...\n";

    // 1. Question Banks (Folders)
    $db->exec("
        CREATE TABLE IF NOT EXISTS question_banks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            instructor_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");

    // 2. Bank Questions (Templates)
    $db->exec("
        CREATE TABLE IF NOT EXISTS bank_questions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            bank_id INTEGER NOT NULL,
            type TEXT NOT NULL CHECK(type IN ('single_choice', 'multi_choice', 'true_false', 'short_text')),
            question_text TEXT NOT NULL,
            options TEXT, -- JSON Array
            correct_answers TEXT, -- JSON Array
            explanation TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (bank_id) REFERENCES question_banks(id) ON DELETE CASCADE
        );
    ");

    echo "✅ Question Bank Tables Created Successfully\n";

} catch (Exception $e) {
    echo "❌ Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}

