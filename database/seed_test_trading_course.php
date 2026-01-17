<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance();
$driver = $db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);

$contentItemColumns = [
    'course_id',
    'module_id',
    'item_type',
    'item_id',
    'position',
    'display_group_id',
    'created_at',
];
$hasContentItems = false;

if ($driver === 'sqlite') {
    $tableCheck = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name='content_items'");
    if (!empty($tableCheck)) {
        $columns = $db->fetchAll("PRAGMA table_info('content_items')");
        $columnNames = array_column($columns, 'name');
        $missingColumns = array_diff($contentItemColumns, $columnNames);
        $hasContentItems = empty($missingColumns);
    }
} else {
    $tableCheck = $db->fetchAll("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'content_items'");
    if (!empty($tableCheck)) {
        $columns = $db->fetchAll("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'content_items'");
        $columnNames = array_column($columns, 'column_name');
        $missingColumns = array_diff($contentItemColumns, $columnNames);
        $hasContentItems = empty($missingColumns);
    }
}

echo "🧪 Seeding Virtual Test Course: Trading Mastery...\n";

// 1. Create Instructor (Ensure Role Exists)
$instructorId = 999;
$userCheck = $db->fetchAll("SELECT id FROM users WHERE id = $instructorId OR email = 'trading_test@example.com'");
if (empty($userCheck)) {
    $db->query("INSERT INTO users (id, name, email, password) VALUES ($instructorId, 'Trading Instructor', 'trading_test@example.com', 'password123')");
    // Assign Instructor Role (ID 2)
    $db->query("INSERT OR IGNORE INTO role_user (user_id, role_id) VALUES ($instructorId, 2)");
} else {
    // Ensure role exists even if user existed
    $db->query("INSERT OR IGNORE INTO role_user (user_id, role_id) VALUES ($instructorId, 2)");
}

// 2. Create Course
$courseId = 500;
$db->query("DELETE FROM courses WHERE id = $courseId");
$db->query("INSERT INTO courses (id, instructor_id, title, slug, description, price, status, content_flow) 
            VALUES ($courseId, $instructorId, 'Virtual Trading Mastery', 'virtual-trading-test', 'A course designed to stress-test all question types and grading logic.', 0, 'published', 'free')");

// 3. Create Module
$moduleId = 500;
$db->query("DELETE FROM modules WHERE id = $moduleId");
$db->query("INSERT INTO modules (id, course_id, title, sort_order) VALUES ($moduleId, $courseId, 'Market Fundamentals', 0)");

$sectionModuleId = 501;
$db->query("DELETE FROM modules WHERE id = $sectionModuleId");
$db->query("INSERT INTO modules (id, course_id, title, sort_order) VALUES ($sectionModuleId, $courseId, 'Section Tests', 1)");

// 4. Create Lessons & Quizzes

// --- Topic 1: Market Basics ---
// Content
$l1 = 501;
$db->query("DELETE FROM lessons WHERE id = $l1");
$db->query("INSERT INTO lessons (id, module_id, instructor_id, title, type, content_url, sort_order) 
            VALUES ($l1, $moduleId, $instructorId, 'Lesson 1: Market Basics', 'video', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 1)");

// Quiz
$l1_q = 502; $q1 = 502;
$db->query("DELETE FROM lessons WHERE id = $l1_q");
$db->query("INSERT INTO lessons (id, module_id, instructor_id, title, type, sort_order) 
            VALUES ($l1_q, $moduleId, $instructorId, 'Quiz 1: Market Basics', 'quiz', 2)");

$db->query("DELETE FROM quizzes WHERE id = $q1");
$db->query("INSERT INTO quizzes (id, lesson_id, passing_score, status) VALUES ($q1, $l1_q, 50, 'published')");
$db->query("DELETE FROM questions WHERE quiz_id = $q1");

// Q1.1 Single Choice
$opts1 = json_encode([
    ['id' => 1, 'text' => 'Bullish'],
    ['id' => 2, 'text' => 'Bearish'],
    ['id' => 3, 'text' => 'Sideways']
]);
$db->query("INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers) 
            VALUES ($q1, 'single_choice', 'Which trend signifies rising prices?', 10, :opts, '[1]')", [':opts' => $opts1]);

// Q1.2 Multi Choice
$opts2 = json_encode([
    ['id' => 1, 'text' => 'USD'],
    ['id' => 2, 'text' => 'EUR'],
    ['id' => 3, 'text' => 'Gold']
]);
$db->query("INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers) 
            VALUES ($q1, 'multi_choice', 'Select the fiat currencies.', 10, :opts, '[1,2]')", [':opts' => $opts2]);

// Q1.3 True/False
$opts3 = json_encode([
    ['id' => 1, 'text' => 'True'],
    ['id' => 0, 'text' => 'False']
]);
$db->query("INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers) 
            VALUES ($q1, 'true_false', 'Forex market is open 24/5.', 10, :opts, '[1]')", [':opts' => $opts3]);


// --- Topic 2: Advanced Patterns ---
// Content
$l2 = 503;
$db->query("DELETE FROM lessons WHERE id = $l2");
$db->query("INSERT INTO lessons (id, module_id, instructor_id, title, type, content_text, sort_order) 
            VALUES ($l2, $moduleId, $instructorId, 'Lesson 2: Advanced Patterns', 'article', '<h2>Candlestick Patterns</h2><p>Doji, Hammer, Engulfing...</p>', 3)");

// Quiz
$l2_q = 504; $q2 = 504;
$db->query("DELETE FROM lessons WHERE id = $l2_q");
$db->query("INSERT INTO lessons (id, module_id, instructor_id, title, type, sort_order) 
            VALUES ($l2_q, $moduleId, $instructorId, 'Quiz 2: Patterns Check', 'quiz', 4)");

$db->query("DELETE FROM quizzes WHERE id = $q2");
$db->query("INSERT INTO quizzes (id, lesson_id, passing_score, status) VALUES ($q2, $l2_q, 60, 'published')");
$db->query("DELETE FROM questions WHERE quiz_id = $q2");

// Q2.1 Matching (Strict)
$optsMatch = json_encode([
    ['left_id' => 1, 'left_text' => 'Doji', 'right_id' => 1, 'right_text' => 'Indecision'],
    ['left_id' => 2, 'left_text' => 'Hammer', 'right_id' => 2, 'right_text' => 'Reversal']
]);
$ansMatch = json_encode([['left_id' => 1, 'right_id' => 1], ['left_id' => 2, 'right_id' => 2]]);
$db->query("INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers, grading_policy) 
            VALUES ($q2, 'matching', 'Match the candlestick to its meaning.', 20, :opts, :ans, 'strict')", 
            [':opts' => $optsMatch, ':ans' => $ansMatch]);

// Q2.2 Fill Blank (Fuzzy)
$ansBlank = json_encode(['Stop Loss']);
$db->query("INSERT INTO questions (quiz_id, type, question_text, points, correct_answers, grading_policy) 
            VALUES ($q2, 'fill_blank', 'What tool limits your downside risk? (Two words)', 15, :ans, 'fuzzy')", 
            [':ans' => $ansBlank]);


// --- Topic 3: Trading Psychology ---
// Content
$l3 = 505;
$db->query("DELETE FROM lessons WHERE id = $l3");
$db->query("INSERT INTO lessons (id, module_id, instructor_id, title, type, content_url, sort_order) 
            VALUES ($l3, $moduleId, $instructorId, 'Lesson 3: Trading Psychology', 'video', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 5)");

// Quiz (AI)
$l3_q = 506; $q3 = 506;
$db->query("DELETE FROM lessons WHERE id = $l3_q");
$db->query("INSERT INTO lessons (id, module_id, instructor_id, title, type, sort_order) 
            VALUES ($l3_q, $moduleId, $instructorId, 'Quiz 3: Psychology (AI)', 'quiz', 6)");

$db->query("DELETE FROM quizzes WHERE id = $q3");
$db->query("INSERT INTO quizzes (id, lesson_id, passing_score, status) VALUES ($q3, $l3_q, 50, 'published')");
$db->query("DELETE FROM questions WHERE quiz_id = $q3");

// Q3.1 Short Text (AI)
$rubricShort = "The answer must define FOMO as 'Fear Of Missing Out' and mention emotional trading.";
$db->query("INSERT INTO questions (quiz_id, type, question_text, points, grading_policy, ai_rubric) 
            VALUES ($q3, 'short_text', 'Define FOMO in the context of trading.', 20, 'ai', :rubric)", 
            [':rubric' => $rubricShort]);

// Q3.2 Essay (AI Pre-score)
$rubricEssay = "Evaluate based on: 1. Risk Management rules (1-2% rule). 2. Entry strategy. 3. Exit strategy. High score for comprehensive plan.";
$db->query("INSERT INTO questions (quiz_id, type, question_text, points, grading_policy, ai_rubric) 
            VALUES ($q3, 'essay', 'Outline a complete trading plan for a swing trader.', 50, 'ai', :rubric)", 
            [':rubric' => $rubricEssay]);

// Q3.3 Video Submission (Manual)
$db->query("INSERT INTO questions (quiz_id, type, question_text, points, grading_policy) 
            VALUES ($q3, 'video_submission', 'Upload a video analysis of the EUR/USD chart.', 30, 'manual')");


// --- Topic 4: Algorithmic Trading ---
// Content
$l4 = 507;
$db->query("DELETE FROM lessons WHERE id = $l4");
$db->query("INSERT INTO lessons (id, module_id, instructor_id, title, type, content_text, sort_order) 
            VALUES ($l4, $moduleId, $instructorId, 'Lesson 4: Algorithmic Trading', 'article', '<h2>Algos and HFT</h2><p>Computers rule the markets...</p>', 7)");

// Quiz
$l4_q = 508; $q4 = 508;
$db->query("DELETE FROM lessons WHERE id = $l4_q");
$db->query("INSERT INTO lessons (id, module_id, instructor_id, title, type, sort_order) 
            VALUES ($l4_q, $moduleId, $instructorId, 'Quiz 4: Algo Check', 'quiz', 8)");

$db->query("DELETE FROM quizzes WHERE id = $q4");
$db->query("INSERT INTO quizzes (id, lesson_id, passing_score, status) VALUES ($q4, $l4_q, 50, 'published')");
$db->query("DELETE FROM questions WHERE quiz_id = $q4");

// Q4.1 Short Text (AI)
$rubricAlgo = "Answer should mention speed, automation, and frequency.";
$db->query("INSERT INTO questions (quiz_id, type, question_text, points, grading_policy, ai_rubric) 
            VALUES ($q4, 'short_text', 'What is High Frequency Trading?', 30, 'ai', :rubric)", 
            [':rubric' => $rubricAlgo]);

// Q4.2 Fill Blank (Strict)
$ansAlgo = json_encode(['Python']);
$db->query("INSERT INTO questions (quiz_id, type, question_text, points, correct_answers, grading_policy) 
            VALUES ($q4, 'fill_blank', 'Which programming language is most popular for algo trading?', 20, :ans, 'strict')", 
            [':ans' => $ansAlgo]);


// --- Topic 5: Final Assessment ---
// Content
$l5 = 509;
$db->query("DELETE FROM lessons WHERE id = $l5");
$db->query("INSERT INTO lessons (id, module_id, instructor_id, title, type, content_url, sort_order) 
            VALUES ($l5, $moduleId, $instructorId, 'Lesson 5: The Future of Markets', 'video', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 9)");

// Quiz
$l5_q = 510; $q5 = 510;
$db->query("DELETE FROM lessons WHERE id = $l5_q");
$db->query("INSERT INTO lessons (id, module_id, instructor_id, title, type, sort_order) 
            VALUES ($l5_q, $moduleId, $instructorId, 'Final Assessment', 'quiz', 10)");

$db->query("DELETE FROM quizzes WHERE id = $q5");
$db->query("INSERT INTO quizzes (id, lesson_id, passing_score, status) VALUES ($q5, $l5_q, 60, 'published')");
$db->query("DELETE FROM questions WHERE quiz_id = $q5");

// Q5.1 Essay (AI)
$rubricEthic = "Discuss market manipulation, fairness, and AI bias. High score for critical thinking.";
$db->query("INSERT INTO questions (quiz_id, type, question_text, points, grading_policy, ai_rubric) 
            VALUES ($q5, 'essay', 'Discuss the ethical implications of AI in financial markets.', 50, 'ai', :rubric)", 
            [':rubric' => $rubricEthic]);

// Q5.2 Single Choice
$opts5 = json_encode([['id' => 1, 'text' => 'Efficient'], ['id' => 2, 'text' => 'Irrational']]);
$db->query("INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers) 
            VALUES ($q5, 'single_choice', 'The Market is always...', 20, :opts, '[2]')", [':opts' => $opts5]);

// Q5.3 True/False
$opts5tf = json_encode([['id' => 1, 'text' => 'True'], ['id' => 0, 'text' => 'False']]);
$db->query("INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers) 
            VALUES ($q5, 'true_false', 'You should trade with money you cannot afford to lose.', 30, :opts, '[0]')", [':opts' => $opts5tf]);

// --- Topic 6: Market Recap (Ungrouped Lesson -> Standalone Quiz) ---
$l6 = 511;
$db->query("DELETE FROM lessons WHERE id = $l6");
$db->query("INSERT INTO lessons (id, module_id, instructor_id, title, type, content_text, sort_order) 
            VALUES ($l6, $moduleId, $instructorId, 'Lesson 6: Market Recap', 'article', '<h2>Market Recap</h2><p>Key takeaways before the final test.</p>', 11)");

$l6_q = 512; $q6 = 512;
$db->query("DELETE FROM lessons WHERE id = $l6_q");
$db->query("INSERT INTO lessons (id, module_id, instructor_id, title, type, sort_order) 
            VALUES ($l6_q, $moduleId, $instructorId, 'Standalone Quiz: Market Recap', 'quiz', 12)");

$db->query("DELETE FROM quizzes WHERE id = $q6");
$db->query("INSERT INTO quizzes (id, lesson_id, passing_score, status) VALUES ($q6, $l6_q, 70, 'published')");
$db->query("DELETE FROM questions WHERE quiz_id = $q6");

$opts6 = json_encode([['id' => 1, 'text' => 'Liquidity'], ['id' => 2, 'text' => 'Leverage'], ['id' => 3, 'text' => 'Latency']]);
$db->query("INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers) 
            VALUES ($q6, 'single_choice', 'Which concept affects execution speed the most?', 20, :opts, '[3]')", [':opts' => $opts6]);

// --- Section Test (Ungrouped) ---
$sectionTestLesson = 513;
$db->query("DELETE FROM lessons WHERE id = $sectionTestLesson");
$db->query("INSERT INTO lessons (id, module_id, instructor_id, title, type, sort_order) 
            VALUES ($sectionTestLesson, $sectionModuleId, $instructorId, 'Section Test: Market Fundamentals', 'quiz', 1)");

$sectionTestQuiz = 513;
$db->query("DELETE FROM quizzes WHERE id = $sectionTestQuiz");
$db->query("INSERT INTO quizzes (id, lesson_id, passing_score, status) VALUES ($sectionTestQuiz, $sectionTestLesson, 75, 'published')");
$db->query("DELETE FROM questions WHERE quiz_id = $sectionTestQuiz");

$optsSection = json_encode([['id' => 1, 'text' => 'Trend'], ['id' => 2, 'text' => 'Range'], ['id' => 3, 'text' => 'Breakdown']]);
$db->query("INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers) 
            VALUES ($sectionTestQuiz, 'single_choice', 'A market with higher highs and higher lows is in a...', 25, :opts, '[1]')", [':opts' => $optsSection]);

if ($hasContentItems) {
    $db->query("DELETE FROM content_items WHERE course_id = $courseId");

    $groupMarketBasics = 9001;
    $createdAt = date('Y-m-d H:i:s');
    $contentItems = [
        ['module_id' => $moduleId, 'item_type' => 'lesson', 'item_id' => $l1, 'position' => 1, 'display_group_id' => $groupMarketBasics],
        ['module_id' => $moduleId, 'item_type' => 'quiz', 'item_id' => $q1, 'position' => 2, 'display_group_id' => $groupMarketBasics],
        ['module_id' => $moduleId, 'item_type' => 'lesson', 'item_id' => $l2, 'position' => 3, 'display_group_id' => null],
        ['module_id' => $moduleId, 'item_type' => 'quiz', 'item_id' => $q2, 'position' => 4, 'display_group_id' => null],
        ['module_id' => $moduleId, 'item_type' => 'lesson', 'item_id' => $l3, 'position' => 5, 'display_group_id' => null],
        ['module_id' => $moduleId, 'item_type' => 'quiz', 'item_id' => $q3, 'position' => 6, 'display_group_id' => null],
        ['module_id' => $moduleId, 'item_type' => 'lesson', 'item_id' => $l4, 'position' => 7, 'display_group_id' => null],
        ['module_id' => $moduleId, 'item_type' => 'quiz', 'item_id' => $q4, 'position' => 8, 'display_group_id' => null],
        ['module_id' => $moduleId, 'item_type' => 'lesson', 'item_id' => $l5, 'position' => 9, 'display_group_id' => null],
        ['module_id' => $moduleId, 'item_type' => 'quiz', 'item_id' => $q5, 'position' => 10, 'display_group_id' => null],
        ['module_id' => $moduleId, 'item_type' => 'lesson', 'item_id' => $l6, 'position' => 11, 'display_group_id' => null],
        ['module_id' => $moduleId, 'item_type' => 'quiz', 'item_id' => $q6, 'position' => 12, 'display_group_id' => null],
        ['module_id' => $sectionModuleId, 'item_type' => 'quiz', 'item_id' => $sectionTestQuiz, 'position' => 13, 'display_group_id' => null],
    ];

    foreach ($contentItems as $item) {
        $db->query(
            "INSERT INTO content_items (course_id, module_id, item_type, item_id, position, display_group_id, created_at)
             VALUES (:course_id, :module_id, :item_type, :item_id, :position, :display_group_id, :created_at)",
            [
                ':course_id' => $courseId,
                ':module_id' => $item['module_id'],
                ':item_type' => $item['item_type'],
                ':item_id' => $item['item_id'],
                ':position' => $item['position'],
                ':display_group_id' => $item['display_group_id'],
                ':created_at' => $createdAt,
            ]
        );
    }
} else {
    echo "Note: content_items table not found or missing columns. Skipping item grouping seed.\n";
}


echo "✅ Virtual Course 'Trading Mastery' seeded successfully!\n";
echo "   Course ID: $courseId\n";
echo "   Instructor Email: trading_test@example.com\n";
