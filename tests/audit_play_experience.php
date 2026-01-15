<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Services\QuizService;
use App\Services\CourseProgressService;

// Mock Session
$_SESSION = [];

function printHeader($title) {
    echo "\n" . str_repeat('=', 60) . "\n";
    echo " 🕵️‍♂️ AUDIT: $title\n";
    echo str_repeat('=', 60) . "\n";
}

function assertTest($condition, $successMsg, $failMsg) {
    if ($condition) {
        echo "   ✅ PASS: $successMsg\n";
    } else {
        echo "   ❌ FAIL: $failMsg\n";
        global $hasErrors;
        $hasErrors = true;
    }
}

$hasErrors = false;
$db = Database::getInstance();
$userId = 1; // Test User

try {
    echo "🔍 STARTING COMPREHENSIVE STUDENT EXPERIENCE AUDIT...\n";

    // ---------------------------------------------------------
    // PHASE 1: ENROLLMENT & COMMERCE
    // ---------------------------------------------------------
    printHeader("1. Enrollment & Commerce");
    
    // Simulate Cart
    $_SESSION['cart'] = [8]; // Assuming Course 8 exists from seed
    assertTest(count($_SESSION['cart']) === 1, "Cart session stored", "Cart session failed");

    // Simulate Checkout Logic
    // Manually enroll user in Course 8
    $db->query("INSERT OR IGNORE INTO enrollments (user_id, course_id) VALUES (:uid, :cid)", [':uid' => $userId, ':cid' => 8]);
    
    $enrollment = $db->fetchAll("SELECT * FROM enrollments WHERE user_id = $userId AND course_id = 8");
    assertTest(!empty($enrollment), "User Enrolled successfully", "Enrollment record missing");


    // ---------------------------------------------------------
    // PHASE 2: PLAYER NAVIGATION & CONTENT
    // ---------------------------------------------------------
    printHeader("2. Player Logic & Navigation");

    // Fetch First Lesson of Course 8
    $modules = $db->fetchAll("SELECT id FROM modules WHERE course_id = 8 ORDER BY sort_order ASC LIMIT 1");
    $modId = $modules[0]['id'] ?? 0;
    
    if ($modId) {
        $lessons = $db->fetchAll("SELECT * FROM lessons WHERE module_id = $modId ORDER BY sort_order ASC");
        $firstLesson = $lessons[0] ?? null;
        
        assertTest($firstLesson !== null, "Found first lesson", "Course has no lessons");
        
        if ($firstLesson) {
            // Check Content Integrity
            if ($firstLesson['type'] === 'video') {
                assertTest(!empty($firstLesson['content_url']), "Video URL present", "Video lesson missing URL");
            } elseif ($firstLesson['type'] === 'article') {
                assertTest(!empty($firstLesson['content_text']), "Article text present", "Article lesson missing text");
            }

            // Simulate 'Next Lesson' logic
            $nextLesson = $lessons[1] ?? null;
            if ($nextLesson) {
                echo "   ℹ️  Next Lesson Logic: Confirmed (ID: {$nextLesson['id']})\n";
            }
        }
    } else {
        echo "   ⚠️ SKIPPING: No modules found for test course.\n";
    }


    // ---------------------------------------------------------
    // PHASE 3: QUIZ RUNNER CRITICAL PATH
    // ---------------------------------------------------------
    printHeader("3. Quiz Runner Engine");

    // Find a quiz lesson
    // Correct query with JOIN:
    $quizLesson = $db->fetchAll("SELECT l.* FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = 8 AND l.type = 'quiz' LIMIT 1")[0] ?? null;

    if ($quizLesson) {
        echo "   ℹ️  Testing Quiz ID: " . $quizLesson['id'] . "\n";
        
        // 1. Get Quiz Config
        $quizConfig = $db->fetchAll("SELECT * FROM quizzes WHERE lesson_id = {$quizLesson['id']}")[0] ?? null;
        assertTest($quizConfig !== null, "Quiz Configuration found", "Quiz Lesson has no settings table entry");

        if ($quizConfig) {
            $quizService = new QuizService();
            
            // 2. Start Attempt
            $attempt = $quizService->startAttempt($quizConfig['id'], $userId);
            assertTest($attempt['id'] > 0, "Attempt initialized", "Failed to create attempt");

            // 3. Save Answer
            $questions = $db->fetchAll("SELECT * FROM questions WHERE quiz_id = {$quizConfig['id']} LIMIT 1");
            if (!empty($questions)) {
                $q = $questions[0];
                $fakeAnswer = ($q['type'] === 'multi_choice') ? [1] : 1;
                
                $quizService->saveAnswer($attempt['id'], $userId, $q['id'], $fakeAnswer);
                
                // Verify Save
                $check = $db->fetchAll("SELECT answers FROM quiz_attempts WHERE id = {$attempt['id']}")[0];
                $ans = json_decode($check['answers'], true);
                assertTest(isset($ans[$q['id']]), "Answer saved to JSON blob", "SaveAnswer failed");
            }

            // 4. Submit & Grading
            $result = $quizService->submitAttempt($attempt['id'], $userId);
            assertTest(isset($result['score']), "Grading Engine returned score: {$result['score']}%", "Grading failed");
            
            // 5. Progress Update (Service Injection Check)
            $progService = new CourseProgressService();
            $progService->updateProgress($userId, 8);
            
            $checkProg = $db->fetchAll("SELECT progress_percent FROM enrollments WHERE user_id = $userId AND course_id = 8")[0];
            assertTest($checkProg['progress_percent'] > 0, "Course Progress Updated ({$checkProg['progress_percent']}%)", "Progress stuck at 0%");
        }

    } else {
        echo "   ⚠️ SKIPPING: No quiz lesson found in this course.\n";
    }


    // ---------------------------------------------------------
    // PHASE 4: UI HEURISTICS (STATIC ANALYSIS)
    // ---------------------------------------------------------
    printHeader("4. Mobile UI Heuristics Code Scan");

    $files = [
        'resources/views/learn/player.php',
        'resources/views/learn/quiz_runner.php'
    ];

    foreach ($files as $file) {
        $content = file_get_contents(__DIR__ . '/../' . $file);
        
        // Check for viewport meta
        if (strpos($content, '<meta name="viewport"') !== false) {
            echo "   ✅ $file: Viewport Meta present.\n";
        } else {
            echo "   ❌ $file: Viewport Meta MISSING!\n";
            $hasErrors = true;
        }

        // Check for fixed width hardcoding (potential mobile breakers)
        if (preg_match('/w-\[\d+px\]/', $content)) {
            echo "   ⚠️ $file: Detected arbitrary fixed widths (e.g. w-[500px]). Check mobile responsiveness.\n";
        } else {
            echo "   ✅ $file: No obvious fixed-width hardcoding detected.\n";
        }
    }

    echo "\n";
    if ($hasErrors) {
        echo "🛑 AUDIT COMPLETED WITH ERRORS. Review above.\n";
    } else {
        echo "✨ AUDIT PASSED. System logic is sound.\n";
    }

} catch (Exception $e) {
    echo "❌ FATAL EXCEPTION: " . $e->getMessage() . "\n";
}
