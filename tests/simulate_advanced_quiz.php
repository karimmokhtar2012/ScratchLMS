<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\QuizService;
use App\Core\Database;

function printStep($step) {
    echo "\n🔹 STEP: $step\n";
}

function assertVal($expected, $actual, $desc) {
    if ($expected === $actual) {
        echo "   ✅ PASS: $desc\n";
    } else {
        echo "   ❌ FAIL: $desc (Expected: " . json_encode($expected) . ", Got: " . json_encode($actual) . ")\n";
    }
}

try {
    $db = Database::getInstance();
    $service = new QuizService();
    $userId = 999; // Mock User

    echo "🧪 STARTING ADVANCED QUIZ SIMULATION\n";

    // 1. SETUP
    printStep("Setting up Quiz environment");
    $db->query("DELETE FROM quiz_attempts WHERE user_id = :uid", [':uid' => $userId]);
    
    // Create Quiz (ID 100) linked to Lesson (ID 100)
    $db->query("INSERT OR IGNORE INTO lessons (id, module_id, title, type) VALUES (100, 1, 'Simulated Exam', 'quiz')");
    $db->query("DELETE FROM quizzes WHERE id = 100");
    $db->query("INSERT INTO quizzes (id, lesson_id, time_limit, passing_score) VALUES (100, 100, 30, 50)");
    
    // Add Questions
    $db->query("DELETE FROM questions WHERE quiz_id = 100");
    // Q1: True/False (5pts)
    $db->query("INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers) VALUES (100, 'true_false', 'Sky is blue?', 5, '[]', '[1]')");
    $q1 = $db->getConnection()->lastInsertId();
    
    // Q2: Multi Choice (10pts)
    $db->query("INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers) VALUES (100, 'multi_choice', 'Pick 2', 10, '[]', '[1,2]')");
    $q2 = $db->getConnection()->lastInsertId();


    // 2. START & RESUME
    printStep("Testing Resume Capability");
    $attempt1 = $service->startAttempt(100, $userId);
    echo "   -> Started Attempt ID: {$attempt1['id']}\n";
    
    // Answer Q1
    $service->saveAnswer($attempt1['id'], $userId, $q1, 1); // Correct
    echo "   -> Saved Answer for Q1\n";

    // Simulate "Leaving" and returning
    $attempt2 = $service->startAttempt(100, $userId);
    echo "   -> Resumed Attempt ID: {$attempt2['id']}\n";
    
    assertVal($attempt1['id'], $attempt2['id'], "Attempt ID should remain consistent (Resume)");
    
    $context = $service->getQuizContext($attempt2['id'], $userId);
    $savedAns = $context['user_answers'][$q1] ?? null;
    assertVal(1, $savedAns, "Previous answer to Q1 should be persisted");


    // 3. SUBMISSION & SCORING
    printStep("Testing Submission Logic");
    // Answer Q2 Partially (Wrong)
    $service->saveAnswer($attempt1['id'], $userId, $q2, [1]); // Missing 2
    
    $result = $service->submitAttempt($attempt1['id'], $userId);
    echo "   -> Scored: {$result['score']}%\n";
    
    // Math: Total 15 pts. Earned 5 (Q1). Q2 is 0 (strict grading). 5/15 = 33%
    assertVal(33.0, (float)$result['score'], "Score Calculation (Strict Grading)");
    assertVal(false, $result['is_passed'], "Pass/Fail Status");


    // 4. TIMEOUT SIMULATION
    printStep("Testing Timeout Logic (Backdating)");
    // Start NEW attempt
    $attempt3 = $service->startAttempt(100, $userId); // Should be new since prev is completed
    echo "   -> Started New Attempt ID: {$attempt3['id']}\n";
    
    // Backdate start time by 60 minutes (Limit is 30)
    $backdate = date('Y-m-d H:i:s', strtotime('-60 minutes'));
    $db->query("UPDATE quiz_attempts SET started_at = '$backdate' WHERE id = {$attempt3['id']}");
    
    // Try to submit
    try {
        $res = $service->submitAttempt($attempt3['id'], $userId);
        echo "   -> Submitted successfully (Logic allows late submission for now)\n";
        
        // Fetch completed_at
        $row = $db->fetchAll("SELECT completed_at, started_at FROM quiz_attempts WHERE id = {$attempt3['id']}")[0];
        $duration = (strtotime($row['completed_at']) - strtotime($row['started_at'])) / 60;
        echo "   -> Recorded Duration: " . round($duration) . " minutes\n";
        
        if ($duration > 30) {
            echo "   ✅ SYSTEM DETECTED DURATION > LIMIT (Server-side timestamps are valid)\n";
        }
        
    } catch (Exception $e) {
        echo "   -> Exception: " . $e->getMessage() . "\n";
    }

    echo "\n✨ SIMULATION COMPLETE\n";

} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
}
