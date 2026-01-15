<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;

// Helper to print section headers
function printHeader($title) {
    echo "\n" . str_repeat('=', 40) . "\n";
    echo " " . strtoupper($title) . "\n";
    echo str_repeat('=', 40) . "\n";
}

try {
    $db = Database::getInstance()->getConnection();
    
    echo "🔍 STARTING SYSTEM INTEGRITY CHECK...\n";

    // 1. USERS & ROLES
    printHeader("Users & Roles");
    $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Total Users: $userCount\n";
    
    $users = $db->query("SELECT u.id, u.name, u.email, GROUP_CONCAT(r.slug) as roles 
                         FROM users u 
                         LEFT JOIN role_user ru ON u.id = ru.user_id 
                         LEFT JOIN roles r ON ru.role_id = r.id 
                         GROUP BY u.id")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $u) {
        echo " - [ID: {$u['id']}] {$u['name']} ({$u['email']}) -> Roles: " . ($u['roles'] ?: 'NONE') . "\n";
    }

    // 2. COURSES & STRUCTURE
    printHeader("Courses & Curriculum");
    $courses = $db->query("SELECT * FROM courses")->fetchAll(PDO::FETCH_ASSOC);
    echo "Total Courses: " . count($courses) . "\n";

    foreach ($courses as $c) {
        echo " + Course [ID: {$c['id']}]: {$c['title']} (Instructor ID: {$c['instructor_id']})\n";
        
        // Modules
        $modules = $db->query("SELECT * FROM modules WHERE course_id = {$c['id']}")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($modules as $m) {
            echo "   L Module [ID: {$m['id']}]: {$m['title']}\n";
            
            // Lessons
            $lessons = $db->query("SELECT * FROM lessons WHERE module_id = {$m['id']}")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($lessons as $l) {
                echo "     - Lesson [ID: {$l['id']}]: {$l['title']} ({$l['type']})\n";
                
                // Content Links check
                if ($l['type'] === 'quiz') {
                    $quiz = $db->query("SELECT * FROM quizzes WHERE lesson_id = {$l['id']}")->fetch(PDO::FETCH_ASSOC);
                    if ($quiz) {
                        $qCount = $db->query("SELECT COUNT(*) FROM questions WHERE quiz_id = {$quiz['id']}")->fetchColumn();
                        echo "       >> LINKED QUIZ [ID: {$quiz['id']}]: {$qCount} Questions defined.\n";
                    } else {
                        echo "       >> ❌ WARNING: Quiz Lesson has no linked Quiz entry!\n";
                    }
                } elseif ($l['type'] === 'video') {
                    if (empty($l['content_url'])) {
                        echo "       >> ⚠️ WARNING: Video Lesson has no content URL.\n";
                    } else {
                        echo "       >> Media: {$l['content_url']}\n";
                    }
                }
            }
        }
    }

    // 3. ENROLLMENTS & PROGRESS
    printHeader("Enrollments & Progress");
    $enrollments = $db->query("SELECT e.*, u.name as user_name, c.title as course_title 
                               FROM enrollments e 
                               JOIN users u ON e.user_id = u.id 
                               JOIN courses c ON e.course_id = c.id")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($enrollments)) {
        echo "No enrollments found.\n";
    } else {
        foreach ($enrollments as $e) {
            echo " - {$e['user_name']} in '{$e['course_title']}' -> Progress: {$e['progress_percent']}%\n";
        }
    }

    // 4. LOGS & ACTIVITY
    printHeader("Activity Logs (xAPI)");
    $logCount = $db->query("SELECT COUNT(*) FROM xapi_logs")->fetchColumn();
    echo "Total xAPI Logs: $logCount\n";
    
    $recentLogs = $db->query("SELECT l.*, u.name FROM xapi_logs l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recentLogs as $log) {
        echo " - [{$log['created_at']}] {$log['name']} performed '{$log['verb']}' on Lesson ID {$log['lesson_id']}\n";
    }

    // 5. ORPHAN CHECK
    printHeader("Integrity Check (Orphans)");
    
    // Check for questions without valid quiz
    $orphanQuestions = $db->query("SELECT COUNT(*) FROM questions WHERE quiz_id NOT IN (SELECT id FROM quizzes)")->fetchColumn();
    if ($orphanQuestions > 0) echo "❌ $orphanQuestions Orphaned Questions found!\n";
    else echo "✅ No orphaned questions.\n";

    // Check for quizzes without valid lessons
    $orphanQuizzes = $db->query("SELECT COUNT(*) FROM quizzes WHERE lesson_id NOT IN (SELECT id FROM lessons)")->fetchColumn();
    if ($orphanQuizzes > 0) echo "❌ $orphanQuizzes Orphaned Quizzes found!\n";
    else echo "✅ No orphaned quizzes.\n";

    echo "\n✅ SYSTEM CHECK COMPLETE.\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
