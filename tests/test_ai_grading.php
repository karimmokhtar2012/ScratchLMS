<?php

// Manually load env for CLI test
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            // Strip quotes if present
            if (str_starts_with($val, '"') && str_ends_with($val, '"')) {
                $val = substr($val, 1, -1);
            } elseif (str_starts_with($val, "'") && str_ends_with($val, "'")) {
                $val = substr($val, 1, -1);
            }
            $_ENV[$key] = $val;
            putenv("$key=$val");
        }
    }
}

putenv('APP_DEBUG=true');
$_ENV['APP_DEBUG'] = 'true';

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\QuizService;
use App\Core\Database;

try {
    $db = Database::getInstance();
    $service = new QuizService();
    $userId = 1; // Assuming Admin/Instructor exists or we create one. Let's use 1.
    
    // Ensure User 1 exists
    $user = $db->fetchAll("SELECT id FROM users WHERE id = 1")[0] ?? null;
    if (!$user) {
        $db->query("INSERT INTO users (id, name, email, password, role) VALUES (1, 'Test User', 'test@example.com', 'pass', 'student')");
    }

    echo "🧪 STARTING AI GRADING TEST\n";
    echo "   -> GROQ_API_KEY present: " . (getenv('GROQ_API_KEY') ? 'YES' : 'NO') . "\n";

    // 1. SETUP
    // Create Course, Module, Lesson
    $courseId = 999;
    $moduleId = 999;
    $lessonId = 999;
    $quizId = 999;

    $db->query("DELETE FROM courses WHERE id = $courseId");
    $db->query("DELETE FROM modules WHERE id = $moduleId");
    $db->query("DELETE FROM lessons WHERE id = $lessonId");
    $db->query("DELETE FROM quizzes WHERE id = $quizId");
    $db->query("DELETE FROM quiz_attempts WHERE quiz_id = $quizId");
    $db->query("DELETE FROM grading_queue WHERE attempt_id IN (SELECT id FROM quiz_attempts WHERE quiz_id = $quizId)");

    $db->query("INSERT INTO courses (id, instructor_id, title, slug, description, price, status) VALUES ($courseId, 1, 'AI Test Course', 'ai-test', 'Testing AI', 0, 'published')");
    $db->query("INSERT INTO modules (id, course_id, title, sort_order) VALUES ($moduleId, $courseId, 'AI Module', 0)");
    $db->query("INSERT INTO lessons (id, module_id, instructor_id, title, type) VALUES ($lessonId, $moduleId, 1, 'AI Quiz', 'quiz')");
    
    // Enroll user
    $db->query("INSERT OR IGNORE INTO enrollments (user_id, course_id) VALUES ($userId, $courseId)");

    // Create Quiz
    $db->query("INSERT INTO quizzes (id, lesson_id, time_limit, passing_score, status) VALUES ($quizId, $lessonId, 0, 50, 'published')");
    
    // Add Essay Question with Rubric
    $db->query("DELETE FROM questions WHERE quiz_id = $quizId");
    $rubric = "Grade this based on clarity and correctness. The answer should mention 'MVC' means Model View Controller.";
    $sql = "INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers, grading_policy, ai_rubric) 
            VALUES ($quizId, 'essay', 'What is MVC?', 10, '[]', '[]', 'ai', :rubric)";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute([':rubric' => $rubric]);
    $qId = $db->getConnection()->lastInsertId();

    echo "   -> Created Quiz $quizId with Essay Question $qId\n";

    // 2. START ATTEMPT
    $attempt = $service->startAttempt($quizId, $userId);
    echo "   -> Started Attempt ID: " . $attempt['id'] . "\n";

    // 3. ANSWER
    $studentAnswer = "MVC stands for Model View Controller. It separates data, UI, and logic.";
    $service->saveAnswer($attempt['id'], $userId, $qId, $studentAnswer);
    echo "   -> Answered: '" . $studentAnswer . "'\n";

    // 4. SUBMIT
    echo "   -> Submitting...\n";
    $result = $service->submitAttempt($attempt['id'], $userId);
    
    echo "   -> Submission Result: " . json_encode($result) . "\n";

    // 5. VERIFY GRADING QUEUE
    $queue = $db->fetchAll("SELECT * FROM grading_queue WHERE attempt_id = :aid AND question_id = :qid", [
        ':aid' => $attempt['id'],
        ':qid' => $qId
    ]);

    if (!empty($queue)) {
        $item = $queue[0];
        echo "   -> Grading Queue Item Found:\n";
        echo "      - Status: " . $item['status'] . "\n";
        echo "      - AI Score: " . ($item['ai_score'] ?? 'NULL') . "\n";
        echo "      - AI Feedback: " . ($item['ai_feedback'] ?? 'NULL') . "\n";

        if ($item['status'] === 'ai_scored' && $item['ai_score'] !== null) {
            echo "   ✅ PASS: AI Grading triggered and returned a score.\n";
        } else {
            echo "   ❌ FAIL: AI Grading did not return a score or status is not 'ai_scored'.\n";
        }
    } else {
        echo "   ❌ FAIL: No entry in grading_queue.\n";
    }

    // 6. VERIFY AUDIT LOG (Answers JSON)
    $dbAttempt = $db->fetchAll("SELECT answers FROM quiz_attempts WHERE id = :id", [':id' => $attempt['id']])[0];
    $answers = json_decode($dbAttempt['answers'], true);
    $auditKey = $qId . '_ai_result';
    
    if (isset($answers[$auditKey])) {
        echo "   -> Audit Log Found in Answers JSON:\n";
        echo "      - Score: " . $answers[$auditKey]['score'] . "\n";
        echo "      - Feedback: " . substr($answers[$auditKey]['feedback'], 0, 50) . "...\n";
        echo "   ✅ PASS: AI result audited in quiz_attempts table.\n";
    } else {
        echo "   ❌ FAIL: Audit log missing from answers JSON.\n";
    }

} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}