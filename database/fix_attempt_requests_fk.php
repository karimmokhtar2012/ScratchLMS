<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $db->exec('PRAGMA foreign_keys = OFF;');
    $db->beginTransaction();

    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='attempt_requests'")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($tables)) {
        $db->exec("ALTER TABLE attempt_requests RENAME TO attempt_requests_old;");

        $db->exec("
            CREATE TABLE attempt_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                quiz_id INTEGER NOT NULL,
                status TEXT DEFAULT 'pending', -- 'pending', 'approved', 'rejected'
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
            );
        ");

        $db->exec("
            INSERT INTO attempt_requests (id, user_id, quiz_id, status, created_at)
            SELECT id, user_id, quiz_id, status, created_at
            FROM attempt_requests_old;
        ");

        $db->exec("DROP TABLE attempt_requests_old;");
    }

    $db->commit();
    $db->exec('PRAGMA foreign_keys = ON;');
    echo "✅ Attempt requests foreign key fixed\n";
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $db->exec('PRAGMA foreign_keys = ON;');
    echo "❌ Fix failed: " . $e->getMessage() . "\n";
    exit(1);
}

