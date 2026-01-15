<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\QuizService;
use App\Core\Database;

function assertEquals($expected, $actual, $message) {
    if ($expected === $actual) {
        echo "✅ PASS: $message\n";
    } else {
        echo "❌ FAIL: $message (Expected " . var_export($expected, true) . ", got " . var_export($actual, true) . ")\n";
    }
}

try {
    $db = Database::getInstance();
    $quizService = new QuizService();
    $userId = 1; // Existing user Karim

    echo "🧪 SIMULATING QUIZ ENGINE LOGIC...\n";

    // 1. SETUP TEST DATA
    // Create a dummy quiz for testing
    $db->query("INSERT OR IGNORE INTO lessons (module_id, title, type) VALUES (1, 'Test Quiz Lesson', 'quiz')");
    $lessonId = $db->getConnection()->lastInsertId();
    
    // Ensure we have a quiz entry
    $db->query("INSERT OR IGNORE INTO quizzes (lesson_id, time_limit, passing_score) VALUES (:lid, 10, 70)", [':lid' => $lessonId]);
    $quiz = $db->fetchAll("SELECT * FROM quizzes WHERE lesson_id = :lid", [':lid' => $lessonId])[0];
    $quizId = $quiz['id'];

    // Clear old questions for this quiz
    $db->query("DELETE FROM questions WHERE quiz_id = :qid", [':qid' => $quizId]);

    // Question 1: Single Choice (Points: 10)
    $db->query("INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers) VALUES (:qid, 'single_choice', 'Q1', 10, :opts, :ans)", [
        ':qid' => $quizId,
        ':opts' => json_encode([['id'=>1, 'text'=>'A'], ['id'=>2, 'text'=>'B']]),
        ':ans' => json_encode([1])
    ]);
    $q1Id = $db->getConnection()->lastInsertId();

    // Question 2: Multi Choice (Points: 20)
    $db->query("INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers) VALUES (:qid, 'multi_choice', 'Q2', 20, :opts, :ans)", [
        ':qid' => $quizId,
        ':opts' => json_encode([['id'=>1, 'text'=>'A'], ['id'=>2, 'text'=>'B'], ['id'=>3, 'text'=>'C']]),
        ':ans' => json_encode([1, 3]) // A and C are correct
    ]);
    $q2Id = $db->getConnection()->lastInsertId();

    // 2. START ATTEMPT
    echo "--- Phase 1: Starting Attempt ---\n";
    $attempt = $quizService->startAttempt((int)$quizId, $userId);
    $attemptId = (int)$attempt['id'];
    echo "Attempt ID: $attemptId\n";

    // 3. SIMULATE ANSWERS
    echo "--- Phase 2: Answering Questions ---\n";
    
    // Answer Q1 correctly
    $quizService->saveAnswer($attemptId, $userId, (int)$q1Id, 1);
    
    // Answer Q2 correctly (Array of IDs)
    $quizService->saveAnswer($attemptId, $userId, (int)$q2Id, [1, 3]);

    // 4. SUBMIT AND GRADE
    echo "--- Phase 3: Grading ---\n";
    $result = $quizService->submitAttempt($attemptId, $userId);

    assertEquals(100, $result['score'], "Score should be 100% for all correct answers");
    assertEquals(true, $result['is_passed'], "Status should be PASSED");

    // 5. TEST PARTIAL SCORE
    echo "--- Phase 4: Testing Partial Score (Wrong Answer) ---\n";
    // Reset attempt
    $db->query("UPDATE quiz_attempts SET completed_at = NULL, answers = '{}' WHERE id = :id", [':id' => $attemptId]);
    
    // Correct Q1 (10 pts)
    $quizService->saveAnswer($attemptId, $userId, (int)$q1Id, 1);
    // Wrong Q2 (Should have been [1,3], we give [1,2])
    $quizService->saveAnswer($attemptId, $userId, (int)$q2Id, [1, 2]);

    $result2 = $quizService->submitAttempt($attemptId, $userId);
    // Total pts = 30. Earned = 10. Score = 33%
    assertEquals(33, (int)$result2['score'], "Score should be approx 33%");
    assertEquals(false, $result2['is_passed'], "Status should be FAILED (Passing is 70)");

    echo "\n✨ CORE LOGIC VALIDATION COMPLETE.\n";

} catch (Exception $e) {
    echo "❌ SIMULATION ERROR: " . $e->getMessage() . "\n";
}
