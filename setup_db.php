<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;

$databaseDir = __DIR__ . '/database';
$databaseFile = $databaseDir . '/database.sqlite';

// 1. Nuke Existing Database
if (file_exists($databaseFile)) {
    unlink($databaseFile);
    echo "💥 Deleted existing database: {$databaseFile}\n";
}

if (!is_dir($databaseDir)) {
    mkdir($databaseDir, 0755, true);
}

touch($databaseFile);
echo "🆕 Created fresh database file.\n";

$db = Database::getInstance();

try {
    echo "🏗️  Building Schema...\n";

    // --- Users & Auth ---
    $db->query("CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        bio TEXT,
        avatar TEXT,
        preferred_name TEXT,
        gender TEXT,
        dob DATE,
        country TEXT,
        phone TEXT,
        field_of_work TEXT,
        usage_intent TEXT,
        is_onboarded INTEGER DEFAULT 0,
        seen_tutorial INTEGER DEFAULT 0,  -- Added missing column
        can_switch_role INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->query("CREATE TABLE roles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE
    )");

    $db->query("CREATE TABLE role_user (
        user_id INTEGER NOT NULL,
        role_id INTEGER NOT NULL,
        PRIMARY KEY (user_id, role_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
    )");

    // --- Commerce ---
    $db->query("CREATE TABLE orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        total_amount REAL NOT NULL,
        status TEXT DEFAULT 'pending',
        payment_method TEXT,
        transaction_id TEXT UNIQUE, -- Added UNIQUE constraint
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    $db->query("CREATE TABLE order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        course_id INTEGER NOT NULL,
        price_at_purchase REAL NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (course_id) REFERENCES courses(id)
    )");

    $db->query("CREATE TABLE coupons (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT NOT NULL UNIQUE,
        discount_percent INTEGER NOT NULL,
        usage_limit INTEGER DEFAULT 0,
        used_count INTEGER DEFAULT 0,
        valid_until DATETIME
    )");

    // --- Content Structure (Unified Plan A) ---
    $db->query("CREATE TABLE courses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        instructor_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        description TEXT,
        price REAL DEFAULT 0,
        thumbnail TEXT,
        status TEXT DEFAULT 'draft',
        content_flow TEXT DEFAULT 'free', 
        has_certificate INTEGER DEFAULT 0,
        certificate_auto_issue INTEGER DEFAULT 0,
        published_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (instructor_id) REFERENCES users(id)
    )");

    $db->query("CREATE TABLE modules (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        course_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        sort_order INTEGER DEFAULT 0,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )");

    // The Payload Tables
    $db->query("CREATE TABLE lessons (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        module_id INTEGER, -- Deprecated (kept for legacy ref)
        instructor_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        type TEXT NOT NULL,
        content_url TEXT,
        content_text TEXT,
        attachment_url TEXT,
        duration INTEGER DEFAULT 0,
        sort_order INTEGER DEFAULT 0, -- Deprecated
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL,
        FOREIGN KEY (instructor_id) REFERENCES users(id)
    )");

    $db->query("CREATE TABLE quizzes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lesson_id INTEGER NOT NULL, -- Deprecated/Link Ref
        time_limit INTEGER DEFAULT 0,
        passing_score INTEGER DEFAULT 80,
        allowed_attempts INTEGER DEFAULT 0,
        is_randomized INTEGER DEFAULT 0,
        show_correct_answers INTEGER DEFAULT 1,
        status TEXT DEFAULT 'draft',
        FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
    )");

    $db->query("CREATE TABLE questions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        quiz_id INTEGER NOT NULL,
        type TEXT NOT NULL,
        question_text TEXT NOT NULL,
        media_url TEXT, -- Added missing column
        points INTEGER DEFAULT 1,
        options TEXT, -- JSON
        correct_answers TEXT, -- JSON
        explanation TEXT,
        grading_policy TEXT DEFAULT 'auto',
        ai_rubric TEXT,
        sort_order INTEGER DEFAULT 0,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
    )");

    // The Structural Source of Truth
    $db->query("CREATE TABLE content_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        course_id INTEGER NOT NULL,
        module_id INTEGER NOT NULL,
        item_type TEXT NOT NULL, -- 'lesson', 'quiz'
        item_id INTEGER NOT NULL,
        position INTEGER NOT NULL,
        display_group_id INTEGER DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
    )");

    $db->query("CREATE TABLE content_item_rules (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        item_id INTEGER NOT NULL,
        rule_type TEXT NOT NULL,
        params_json TEXT NOT NULL,
        version INTEGER DEFAULT 1,
        FOREIGN KEY (item_id) REFERENCES content_items(id) ON DELETE CASCADE
    )");

    $db->query("CREATE TABLE content_rule_definitions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        rule_type TEXT NOT NULL,
        version INTEGER DEFAULT 1,
        schema_json TEXT NOT NULL,
        ui_schema_json TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->query("CREATE TABLE content_prerequisites (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        item_id INTEGER NOT NULL,
        prerequisite_item_id INTEGER NOT NULL,
        relation_type TEXT DEFAULT 'completion',
        FOREIGN KEY (item_id) REFERENCES content_items(id) ON DELETE CASCADE,
        FOREIGN KEY (prerequisite_item_id) REFERENCES content_items(id) ON DELETE CASCADE
    )");

    // --- Progress & Tracking ---
    $db->query("CREATE TABLE enrollments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        course_id INTEGER NOT NULL,
        progress_percent REAL DEFAULT 0,
        is_completed INTEGER DEFAULT 0,
        certificate_id TEXT, -- Added to Base Schema
        certified_at DATETIME, -- Added to Base Schema
        enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, course_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )");

    $db->query("CREATE TABLE content_progress (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        item_id INTEGER NOT NULL,
        status TEXT DEFAULT 'not_started',
        completed_at DATETIME,
        score REAL,
        attempts INTEGER DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES content_items(id) ON DELETE CASCADE
    )");

    // Legacy Progress Tables (Kept for fallback/logic compatibility)
    $db->query("CREATE TABLE completed_lessons (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        lesson_id INTEGER NOT NULL,
        completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, lesson_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
    )");

    $db->query("CREATE TABLE lesson_drip_rules (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lesson_id INTEGER NOT NULL UNIQUE,
        unlock_after_days INTEGER,
        unlock_after_lesson_id INTEGER,
        unlock_after_percent INTEGER,
        FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
    )");

    $db->query("CREATE TABLE quiz_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        quiz_id INTEGER NOT NULL,
        score REAL,
        answers TEXT,
        started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME,
        grading_status TEXT DEFAULT 'final',
        is_passed INTEGER DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
    )");

    $db->query("CREATE TABLE grading_queue (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        attempt_id INTEGER NOT NULL,
        question_id INTEGER NOT NULL,
        status TEXT DEFAULT 'pending_review',
        ai_score REAL,
        ai_feedback TEXT,
        ai_rationale TEXT,
        ai_rationale_version TEXT,
        final_score REAL,
        reviewer_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME,
        FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
    )");

    $db->query("CREATE TABLE attempt_requests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        quiz_id INTEGER NOT NULL,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
    )");

    // --- Misc ---
    $db->query("CREATE TABLE notes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        lesson_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        timestamp INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
    )");

    $db->query("CREATE TABLE notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        type TEXT NOT NULL,
        title TEXT NOT NULL,
        message TEXT NOT NULL,
        link TEXT,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $db->query("CREATE TABLE xapi_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        lesson_id INTEGER NOT NULL,
        verb TEXT NOT NULL,
        meta_data TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
    )");

    // Question Bank
    $db->query("CREATE TABLE question_banks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        instructor_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $db->query("CREATE TABLE bank_questions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bank_id INTEGER NOT NULL,
        type TEXT NOT NULL,
        question_text TEXT NOT NULL,
        points INTEGER DEFAULT 1,
        options TEXT,
        correct_answers TEXT,
        explanation TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (bank_id) REFERENCES question_banks(id) ON DELETE CASCADE
    )");

    // --- Indexes ---
    $db->query("CREATE UNIQUE INDEX IF NOT EXISTS idx_content_progress_user_item ON content_progress (user_id, item_id)");
    $db->query("CREATE INDEX IF NOT EXISTS idx_content_items_course_position ON content_items (course_id, position)");

    echo "✅ Schema built successfully!\n";

    // --- Seed Basic Roles ---
    echo "🌱 Seeding Roles...\n";
    $db->query("INSERT INTO roles (id, name, slug) VALUES (1, 'Administrator', 'admin')");
    $db->query("INSERT INTO roles (id, name, slug) VALUES (2, 'Instructor', 'instructor')");
    $db->query("INSERT INTO roles (id, name, slug) VALUES (3, 'Student', 'student')");
    $db->query("INSERT INTO roles (id, name, slug) VALUES (4, 'Viewer', 'viewer')");
    $db->query("INSERT INTO roles (id, name, slug) VALUES (5, 'Master Instructor', 'master_instructor')");

    // --- Seed Admin User ---
    echo "🌱 Seeding Admin User...\n";
    $hashedPassword = password_hash('password', PASSWORD_BCRYPT);
    $db->query("INSERT INTO users (id, name, email, password, is_onboarded, can_switch_role) VALUES (1, 'Admin', 'admin@example.com', :pass, 1, 1)", [':pass' => $hashedPassword]);
    $db->query("INSERT INTO role_user (user_id, role_id) VALUES (1, 1)");

    echo "🎉 Database Reset Complete!\n";

} catch (Exception $e) {
    echo "❌ Error building database: " . $e->getMessage() . "\n";
    exit(1);
}