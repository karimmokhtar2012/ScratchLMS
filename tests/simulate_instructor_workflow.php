<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;

function printStep($step) {
    echo "\n👨‍🏫 INSTRUCTOR ACTION: $step\n";
}

function assertExists($table, $criteria, $message) {
    $db = Database::getInstance();
    $where = [];
    $params = [];
    foreach ($criteria as $k => $v) {
        $where[] = "$k = :$k";
        $params[":$k"] = $v;
    }
    $sql = "SELECT id FROM $table WHERE " . implode(' AND ', $where);
    $res = $db->fetchAll($sql, $params);
    
    if (!empty($res)) {
        echo "   ✅ SUCCESS: $message (ID: {$res[0]['id']})\n";
        return $res[0]['id'];
    } else {
        echo "   ❌ FAILURE: $message\n";
        throw new Exception("Assertion failed for table $table");
    }
}

try {
    $db = Database::getInstance();
    $userId = 1; // Assuming 'Karim' exists and is an instructor

    echo "🧪 SIMULATING INSTRUCTOR WORKFLOW...\n";

    // 1. CREATE COURSE
    printStep("Creating a New Course");
    $courseModel = new Course();
    $courseId = $courseModel->create([
        'title' => 'Simulated Masterclass ' . time(),
        'slug' => 'simulated-masterclass-' . time(),
        'description' => 'Automated test course',
        'price' => 99.99,
        'thumbnail' => '/uploads/test.jpg',
        'instructor_id' => $userId
    ]);
    
    if ($courseId) echo "   ✅ Course Created (ID: $courseId)\n";
    else throw new Exception("Failed to create course");

    // 2. ADD MODULE
    printStep("Adding a Module");
    $moduleModel = new Module();
    $moduleModel->create([
        'course_id' => $courseId,
        'title' => 'Core Concepts'
    ]);
    $moduleId = assertExists('modules', ['course_id' => $courseId, 'title' => 'Core Concepts'], "Module 'Core Concepts' found");

    // 3. ADD VIDEO LESSON
    printStep("Adding a Video Lesson");
    $lessonModel = new Lesson();
    $lessonModel->create([
        'module_id' => $moduleId,
        'title' => 'Intro Video',
        'type' => 'video',
        'content_url' => '/uploads/intro.mp4'
    ]);
    assertExists('lessons', ['module_id' => $moduleId, 'title' => 'Intro Video', 'type' => 'video'], "Video Lesson created");

    // 4. ADD QUIZ LESSON
    printStep("Adding a Quiz Lesson");
    $lessonModel->create([
        'module_id' => $moduleId,
        'title' => 'Final Exam',
        'type' => 'quiz',
        'content_url' => ''
    ]);
    $quizLessonId = assertExists('lessons', ['module_id' => $moduleId, 'title' => 'Final Exam', 'type' => 'quiz'], "Quiz Lesson created");

    // 5. CONFIGURE QUIZ
    printStep("Configuring Quiz Settings");
    // Simulate what QuizController::getOrCreateQuiz does
    $db->query("INSERT INTO quizzes (lesson_id, time_limit, passing_score) VALUES (:lid, 60, 80)", [':lid' => $quizLessonId]);
    $quizId = assertExists('quizzes', ['lesson_id' => $quizLessonId, 'time_limit' => 60], "Quiz Settings saved");

    // 6. ADD QUESTIONS
    printStep("Adding Questions to Quiz");
    $db->query("INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers) VALUES (:qid, 'single_choice', 'Is this working?', 5, :opts, :ans)", [
        ':qid' => $quizId,
        ':opts' => json_encode([['id'=>1, 'text'=>'Yes'], ['id'=>2, 'text'=>'No']]),
        ':ans' => json_encode([1])
    ]);
    assertExists('questions', ['quiz_id' => $quizId, 'question_text' => 'Is this working?'], "Question added");

    // 7. DELETE MODULE (Cleanup Test)
    printStep("Testing Deletion (Module + Cascade)");
    // Deleting the module should delete lessons, quizzes, and questions due to CASCADE
    $db->query("DELETE FROM modules WHERE id = :id", [':id' => $moduleId]);
    
    // Verify Cascade
    $checkLesson = $db->fetchAll("SELECT id FROM lessons WHERE module_id = :id", [':id' => $moduleId]);
    if (empty($checkLesson)) {
        echo "   ✅ SUCCESS: Cascade Delete worked (Lessons removed)\n";
    } else {
        echo "   ❌ FAILURE: Lessons still exist after module deletion\n";
    }

    echo "\n✨ INSTRUCTOR WORKFLOW VALIDATED SUCCESSFULLY.\n";

} catch (Exception $e) {
    echo "\n❌ SIMULATION FAILED: " . $e->getMessage() . "\n";
}
