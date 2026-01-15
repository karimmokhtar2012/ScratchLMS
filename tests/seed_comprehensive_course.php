<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;

try {
    $db = Database::getInstance();
    $dbConnection = $db->getConnection();
    
    echo "🏗 SEEDING 'ULTIMATE FEATURE SHOWCASE' COURSE...\n";

    // 1. Get Instructor (User ID 1)
    $instructorId = 1;

    // 2. Create Course
    $courseModel = new Course();
    $courseId = $courseModel->create([
        'title' => 'Ultimate Feature Showcase: Masterclass',
        'slug' => 'ultimate-showcase-' . time(),
        'description' => "A comprehensive demonstration of every feature in the Scratch LMS. 

Includes: 
- YouTube Embeds 
- Markdown Articles with Code 
- Advanced Quizzes (All Types) 
- File Attachments.",
        'price' => 0.00, // Free for easy testing
        'thumbnail' => '/assets/images/course.png',
        'instructor_id' => $instructorId
    ]);
    echo "✅ Course Created (ID: $courseId)\n";

    // 3. Create Modules & Lessons
    $modules = [
        'Module 1: Video Capabilities',
        'Module 2: Rich Text & Code',
        'Module 3: Assessment Engine',
        'Module 4: Advanced Scenarios'
    ];

    $videoUrl = 'https://www.youtube.com/watch?v=aGvnjGd7tWk'; // Embed logic handles this? 
    // Note: Our player currently uses <video src="..."> for local. 
    // We need to update the player to handle YouTube embeds if we want this to work visually.
    // For now, I will use the URL, but the player might try to load it as MP4. 
    // *Self-correction*: I should update the Player to detect YouTube URLs later. 
    // But for seeding, I will use this URL.

    $lessonCounter = 1;

    foreach ($modules as $modIndex => $modTitle) {
        $moduleModel = new Module();
        $dbConnection->query("INSERT INTO modules (course_id, title, sort_order) VALUES ($courseId, '$modTitle', $modIndex)");
        $moduleId = $dbConnection->lastInsertId();
        echo "   📂 Module Created: $modTitle\n";

        for ($i = 1; $i <= 5; $i++) {
            $type = 'video';
            $title = "Lesson $lessonCounter: ";
            $content = "";
            $contentText = "";
            $duration = 600; // 10 mins

            // Varying Content
            if ($modIndex == 0) {
                // Video Module
                $title .= "Video Lecture";
                $content = $videoUrl;
            } elseif ($modIndex == 1) {
                // Article Module
                $type = 'article';
                $title .= "Deep Dive Article";
                $contentText = "# Introduction to Architecture\n\nHere is a list of principles:\n* SOLID\n* DRY\n* KISS\n\n```php\nclass Architecture {\n    public function build() {\n        return 'Robust';\n    }\n}\n```";
                $duration = 300; // 5 mins read
            } elseif ($modIndex == 2) {
                // Quiz Module
                $type = 'quiz';
                $title .= "Speed Test";
                $duration = 60; // 1 min duration metadata
            } else {
                // Mixed
                $type = ($i % 2 == 0) ? 'quiz' : 'article';
                $title .= "Advanced Topic";
                $contentText = "Advanced analysis...";
            }

            // Insert Lesson
            $sql = "INSERT INTO lessons (module_id, title, type, content_url, content_text, duration, sort_order) 
                    VALUES (:mid, :title, :type, :url, :text, :dur, :ord)";
            $db->query($sql, [
                ':mid' => $moduleId,
                ':title' => $title,
                ':type' => $type,
                ':url' => $content,
                ':text' => $contentText,
                ':dur' => $duration,
                ':ord' => $i
            ]);
            $lessonId = $dbConnection->lastInsertId();
            echo "      - Lesson Added: $title ($type)\n";

            // If Quiz, Add Questions
            if ($type === 'quiz') {
                // Create Quiz Settings (1 Minute Timer)
                $db->query("INSERT INTO quizzes (lesson_id, time_limit, passing_score) VALUES ($lessonId, 1, 50)");
                $quizId = $dbConnection->lastInsertId();

                // Add 4 Questions (One of each type)
                
                // 1. Single Choice
                $opts1 = json_encode([['id'=>1, 'text'=>'Blue'], ['id'=>2, 'text'=>'Red'], ['id'=>3, 'text'=>'Green']]);
                $db->query("INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers) VALUES ($quizId, 'single_choice', 'What color is the sky?', 10, '$opts1', '[1]')");

                // 2. Multi Choice
                $opts2 = json_encode([['id'=>1, 'text'=>'Apple'], ['id'=>2, 'text'=>'Carrot'], ['id'=>3, 'text'=>'Bread']]);
                $db->query("INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers) VALUES ($quizId, 'multi_choice', 'Select fruits/veg', 10, '$opts2', '[1,2]')");

                // 3. True/False
                $db->query("INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers) VALUES ($quizId, 'true_false', 'PHP is a server-side language?', 10, '[]', '[1]')");

                // 4. Single Choice (Hard)
                $opts4 = json_encode([['id'=>1, 'text'=>'O(1)'], ['id'=>2, 'text'=>'O(n)'], ['id'=>3, 'text'=>'O(log n)']]);
                $db->query("INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers) VALUES ($quizId, 'single_choice', 'Complexity of Array Access?', 10, '$opts4', '[1]')");
                
                echo "         + Quiz Configured (1 min limit, 4 Questions)\n";
            }

            $lessonCounter++;
        }
    }

    echo "\n✨ SEEDING COMPLETE. Course is ready.\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

