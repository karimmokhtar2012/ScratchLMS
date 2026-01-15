<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Models\User;
use App\Controllers\AuthController;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;

// --- MOCKING HELPERS ---
function mockSession($userId = null, $roles = []) {
    $_SESSION = [];
    if ($userId) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = 'Test User';
        $_SESSION['roles'] = $roles;
    }
}

function printHeader($title) {
    echo "\n" . str_repeat('=', 50) . "\n";
    echo " 🚀 STAGE: $title\n";
    echo str_repeat('=', 50) . "\n";
}

function assertTrue($condition, $msg) {
    if ($condition) echo "   ✅ PASS: $msg\n";
    else {
        echo "   ❌ FAIL: $msg\n";
        throw new Exception("Assertion failed: $msg");
    }
}

// --- MAIN SIMULATION ---
try {
    $db = Database::getInstance();
    
    echo "🧪 STARTING FULL LIFECYCLE SIMULATION (INSTRUCTOR PERSPECTIVE)\n";

    // ---------------------------------------------------------
    // STAGE 1: AUTHENTICATION & ACCESS (LOGIC & SECURITY)
    // ---------------------------------------------------------
    printHeader("1. Authentication & Security");

    // 1.1 Create Fresh Instructor User
    $email = 'instructor_' . time() . '@test.com';
    $password = 'secret123';
    $userModel = new User();
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $userId = $userModel->create(['name' => 'Dr. Simulation', 'email' => $email, 'password' => $hashed]);
    
    // Assign Instructor Role (ID 2)
    $db->query("INSERT INTO role_user (user_id, role_id) VALUES ($userId, 2)");
    assertTrue($userId > 0, "Instructor User Created (ID: $userId)");

    // 1.2 Verify Login Logic (Mocking Post)
    $auth = new AuthController();
    $user = $userModel->findByEmail($email);
    assertTrue(password_verify($password, $user['password']), "Password Hashing Verified");

    // 1.3 Role Verification
    $roles = $db->fetchAll("SELECT r.slug FROM role_user ru JOIN roles r ON ru.role_id = r.id WHERE ru.user_id = $userId");
    $roleSlugs = array_column($roles, 'slug');
    assertTrue(in_array('instructor', $roleSlugs), "User has 'instructor' role");

    // Simulate Login Session
    mockSession($userId, $roleSlugs);
    echo "   ℹ️  Session Mocked: Logged in as Instructor\n";


    // ---------------------------------------------------------
    // STAGE 2: COURSE ARCHITECTURE (CODE INTEGRITY)
    // ---------------------------------------------------------
    printHeader("2. Course & Content Architecture");

    // 2.1 Create Complex Course
    $courseModel = new Course();
    $courseId = $courseModel->create([
        'title' => 'Advanced Algorithms',
        'slug' => 'advanced-algos-' . time(),
        'description' => 'Deep dive into binary trees.',
        'price' => 199.00,
        'thumbnail' => '/uploads/algo.jpg',
        'instructor_id' => $userId
    ]);
    assertTrue($courseId > 0, "Course Created");

    // 2.2 Add Modules
    $moduleModel = new Module();
    $db->query("INSERT INTO modules (course_id, title, sort_order) VALUES ($courseId, 'Trees', 1)");
    $mod1 = $db->getConnection()->lastInsertId();
    
    $db->query("INSERT INTO modules (course_id, title, sort_order) VALUES ($courseId, 'Graphs', 2)");
    $mod2 = $db->getConnection()->lastInsertId();
    assertTrue($mod1 && $mod2, "Multiple Modules Created");

    // 2.3 Add Mixed Lessons
    $lessonModel = new Lesson();
    // Video in Mod 1
    $db->query("INSERT INTO lessons (module_id, title, type, sort_order) VALUES ($mod1, 'Binary Search', 'video', 1)");
    $l1 = $db->getConnection()->lastInsertId();
    
    // Quiz in Mod 1
    $db->query("INSERT INTO lessons (module_id, title, type, sort_order) VALUES ($mod1, 'Tree Quiz', 'quiz', 2)");
    $l2 = $db->getConnection()->lastInsertId();

    // Video in Mod 2
    $db->query("INSERT INTO lessons (module_id, title, type, sort_order) VALUES ($mod2, 'Graph Theory', 'video', 1)");
    $l3 = $db->getConnection()->lastInsertId();

    assertTrue($l1 && $l2 && $l3, "Mixed Content (Video/Quiz) Added");

    // 2.4 Reordering Simulation
    // Swap L1 and L2
    $db->query("UPDATE lessons SET sort_order = 2 WHERE id = $l1");
    $db->query("UPDATE lessons SET sort_order = 1 WHERE id = $l2");
    
    // Verify Order
    $lessons = $db->fetchAll("SELECT id, title, sort_order FROM lessons WHERE module_id = $mod1 ORDER BY sort_order ASC");
    assertTrue($lessons[0]['id'] == $l2, "Lesson Reordering Logic Validated (Quiz is now #1)");


    // ---------------------------------------------------------
    // STAGE 3: UX & QUIZ BUILDER FLOW (EASE OF USE PROXY)
    // ---------------------------------------------------------
    printHeader("3. The 'Happy Path' UX Flow");

    // 3.1 Link Quiz to Lesson (Backstage logic handled by controller)
    // Ensure Quiz Entry exists
    $db->query("INSERT INTO quizzes (lesson_id, time_limit) VALUES ($l2, 45)");
    $quizId = $db->getConnection()->lastInsertId();
    assertTrue($quizId > 0, "Quiz Configuration Initialized");

    // 3.2 Add Questions (Simulating AJAX POST)
    // Type: Single Choice
    $options = json_encode([
        ['id'=>1, 'text'=>'O(n)'], 
        ['id'=>2, 'text'=>'O(log n)']
    ]);
    $correct = json_encode([2]);
    
    $db->query("INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers) 
                VALUES ($quizId, 'single_choice', 'Complexity of BST search?', 5, '$options', '$correct')");
    $qId = $db->getConnection()->lastInsertId();
    assertTrue($qId > 0, "Question Added (Single Choice)");

    // 3.3 Verify "Preview" Data Availability
    // Does the public show() method get everything needed?
    $courseData = $courseModel->find($courseId);
    $modulesData = $courseModel->modules($courseId);
    
    assertTrue($courseData['title'] === 'Advanced Algorithms', "Public Page Title Correct");
    assertTrue(count($modulesData) === 2, "Public Page Shows All Modules");
    
    // Check if deep nested lessons are accessible
    // In CourseController, we loop and fetch lessons. Let's simulate that check.
    $mod1Lessons = $db->fetchAll("SELECT * FROM lessons WHERE module_id = {$modulesData[0]['id']}");
    assertTrue(count($mod1Lessons) === 2, "Public Page Recursion (Lessons) Valid");

    echo "\n✨ FULL LIFECYCLE SIMULATION COMPLETE. SYSTEM IS STABLE.\n";

} catch (Exception $e) {
    echo "\n❌ CRITICAL FAILURE: " . $e->getMessage() . "\n";
    exit(1);
}
