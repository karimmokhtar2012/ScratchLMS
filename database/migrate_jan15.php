<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Disable FKs for table reconstruction
    $db->exec('PRAGMA foreign_keys = OFF;');

    echo "🔄 Migrating Database for Jan 15 Updates...\n";
    
    // 1. Add Certificate Columns to Courses
    $cols = $db->query("PRAGMA table_info(courses)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('has_certificate', $cols)) {
        $db->exec("ALTER TABLE courses ADD COLUMN has_certificate INTEGER DEFAULT 1");
        echo "   + Added has_certificate to courses\n";
    }
    if (!in_array('certificate_auto_issue', $cols)) {
        $db->exec("ALTER TABLE courses ADD COLUMN certificate_auto_issue INTEGER DEFAULT 1");
        echo "   + Added certificate_auto_issue to courses\n";
    }

    // 2. Modify Lessons Table (Make module_id nullable + Add instructor_id)
    // SQLite doesn't support changing column constraints directly.
    // We must recreate the table.
    
    $lessonCols = $db->query("PRAGMA table_info(lessons)")->fetchAll(PDO::FETCH_COLUMN, 1);
    $hasInstructorId = in_array('instructor_id', $lessonCols);

    // If we haven't done this migration yet (check for instructor_id)
    if (!$hasInstructorId) {
        $db->beginTransaction();

        // Rename old table
        $db->exec("ALTER TABLE lessons RENAME TO lessons_old");

        // Create new table with nullable module_id and new instructor_id
        $db->exec("            CREATE TABLE lessons (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                module_id INTEGER, 
                instructor_id INTEGER DEFAULT NULL,
                title TEXT NOT NULL,
                type TEXT NOT NULL CHECK(type IN ('video', 'quiz', 'article')) DEFAULT 'video',
                content_url TEXT,
                content_text TEXT,
                attachment_url TEXT,
                duration INTEGER,
                sort_order INTEGER NOT NULL DEFAULT 0,
                FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
                FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        
        // Copy data
        // We need to map columns explicitly because order might differ or we have new columns
        // We will infer instructor_id from the module->course->instructor relation for existing lessons
        
        // 1. Copy basic data
        $db->exec("            INSERT INTO lessons (id, module_id, title, type, content_url, content_text, attachment_url, duration, sort_order)
            SELECT id, module_id, title, type, content_url, content_text, attachment_url, duration, sort_order
            FROM lessons_old
        ");
        
        // 2. Backfill instructor_id
        $db->exec("            UPDATE lessons 
            SET instructor_id = (
                SELECT c.instructor_id 
                FROM modules m 
                JOIN courses c ON m.course_id = c.id 
                WHERE m.id = lessons.module_id
            )
        ");

        // Drop old table
        $db->exec("DROP TABLE lessons_old");

        $db->commit();
        echo "   + Recreated lessons table (module_id nullable, added instructor_id)\n";
    } else {
        echo "   + Lessons table already updated\n";
    }

    echo "✅ Jan 15 Migration Complete\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}
