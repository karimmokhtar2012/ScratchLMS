<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Services\QuizService;
use App\Http\Middleware\AuthMiddleware;
use App\Policies\Authorization;

class QuizRunnerController extends Controller
{
    private QuizService $quizService;

    public function __construct()
    {
        (new AuthMiddleware())->handle();
        $this->quizService = new QuizService();
    }

    /**
     * Show Quiz Intro / Landing Page
     */
    public function intro(string $quizId): void
    {
        $quizId = (int) $quizId;
        $userId = $_SESSION['user_id'];
        $db = \App\Core\Database::getInstance();

        // Security: Check Enrollment
        $sqlContext = "SELECT m.course_id 
                       FROM quizzes q 
                       JOIN lessons l ON q.lesson_id = l.id 
                       JOIN modules m ON l.module_id = m.id 
                       WHERE q.id = :qid";
        $context = $db->fetchAll($sqlContext, [':qid' => $quizId])[0] ?? null;
        
        if ($context) {
            try {
                Authorization::requireEnrollment($userId, (int)$context['course_id']);
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'You must be enrolled in the course to view this quiz.';
                header('Location: /course/' . $context['course_id']);
                exit;
            }
        }

        // Fetch Quiz Metadata
        $quiz = $db->fetchAll("SELECT * FROM quizzes WHERE id = :id", [':id' => $quizId])[0] ?? null;
        if (!$quiz) {
            header('Location: /dashboard');
            exit;
        }

        // Fetch Lesson Title & Course ID
        $lessonTitle = ''; // Already have context but let's stick to existing logic or optimize
        $courseId = $context['course_id'] ?? 0;
        
        // Re-fetching title to match existing variable names if needed, or just use joins
        $sqlLesson = "SELECT title FROM lessons WHERE id = :lid";
        $lessonTitle = $db->fetchAll($sqlLesson, [':lid' => $quiz['lesson_id']])[0]['title'] ?? 'Quiz';

        // Fetch Questions Count
        $qCount = $db->fetchAll("SELECT COUNT(*) as count FROM questions WHERE quiz_id = :id", [':id' => $quizId])[0]['count'] ?? 0;

        // Fetch Previous Attempts
        $attempts = $db->fetchAll("SELECT * FROM quiz_attempts WHERE quiz_id = :id AND user_id = :uid ORDER BY started_at DESC", [
            ':id' => $quizId,
            ':uid' => $userId
        ]);

        // Check for existing Request
        $request = $db->fetchAll("SELECT * FROM attempt_requests WHERE quiz_id = :qid AND user_id = :uid AND status = 'pending' LIMIT 1", [
            ':qid' => $quizId,
            ':uid' => $userId
        ])[0] ?? null;

        $this->view('learn.quiz_intro', [
            'quiz' => $quiz,
            'lessonTitle' => $lessonTitle,
            'courseId' => $courseId,
            'questionCount' => $qCount,
            'attempts' => $attempts,
            'pendingRequest' => $request
        ]);
    }

    public function requestReset(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $quizId = (int) $_POST['quiz_id'];
        $userId = $_SESSION['user_id'];
        $db = \App\Core\Database::getInstance();

        if (!$quizId) {
            $_SESSION['flash_error'] = 'Invalid quiz request.';
            header('Location: /dashboard');
            exit;
        }

        $sqlQuiz = "SELECT q.allowed_attempts,
                           c.id as course_id,
                           c.title as course_title,
                           c.instructor_id
                    FROM quizzes q
                    JOIN lessons l ON q.lesson_id = l.id
                    JOIN modules m ON l.module_id = m.id
                    JOIN courses c ON m.course_id = c.id
                    WHERE q.id = :qid";
        $quizInfo = $db->fetchAll($sqlQuiz, [':qid' => $quizId])[0] ?? null;

        if (!$quizInfo) {
            $_SESSION['flash_error'] = 'Quiz not found.';
            header('Location: /dashboard');
            exit;
        }

        try {
            \App\Policies\Authorization::requireEnrollment($userId, (int) $quizInfo['course_id']);
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'You are not enrolled in this course.';
            header('Location: /dashboard');
            exit;
        }

        $attempts = $db->fetchAll("SELECT completed_at FROM quiz_attempts WHERE quiz_id = :qid AND user_id = :uid", [
            ':qid' => $quizId,
            ':uid' => $userId
        ]);
        $attemptsUsed = count($attempts);
        $hasIncomplete = false;
        foreach ($attempts as $attempt) {
            if (empty($attempt['completed_at'])) {
                $hasIncomplete = true;
                break;
            }
        }
        $maxAttempts = (int) ($quizInfo['allowed_attempts'] ?? 0);
        $canStart = $hasIncomplete || $maxAttempts === 0 || $attemptsUsed < $maxAttempts;

        if ($canStart) {
            $_SESSION['flash_info'] = 'You still have attempts available for this quiz.';
            header('Location: /learn/quiz/intro/' . $quizId);
            exit;
        }

        // Prevent duplicate
        $exists = $db->fetchAll("SELECT id FROM attempt_requests WHERE quiz_id = :qid AND user_id = :uid AND status = 'pending'", [
            ':qid' => $quizId, 
            ':uid' => $userId
        ]);

        if (empty($exists)) {
            $db->query("INSERT INTO attempt_requests (user_id, quiz_id) VALUES (:uid, :qid)", [
                ':uid' => $userId,
                ':qid' => $quizId
            ]);
            $_SESSION['flash_success'] = 'Reset request sent to instructor.';

            // Notify Instructor
            if ($quizInfo) {
                $studentName = $_SESSION['user_name'] ?? 'Student';
                (new \App\Domain\Notifications\Notification())->create([
                    'user_id' => $quizInfo['instructor_id'],
                    'type' => 'quiz_request',
                    'title' => 'Quiz Reset Request',
                    'message' => "$studentName requested a reset for quiz in '{$quizInfo['course_title']}'",
                    'link' => '/instructor/requests#attempts'
                ]);
            }
        } else {
            $_SESSION['flash_info'] = 'You already have a pending reset request.';
        }

        header('Location: /learn/quiz/intro/' . $quizId);
        exit;
    }

    /**
     * Entry point: Start or Resume quiz for a lesson.
     */
    public function start(string $quizId): void
    {
        $quizId = (int) $quizId;
        $userId = $_SESSION['user_id'];

        // Rate Limit: 10 attempts per minute (prevent spamming starts)
        if (!\App\Core\RateLimiter::attempt('quiz_start_' . $userId, 10, 60)) {
            $_SESSION['flash_error'] = 'Too many attempts. Please wait a moment.';
            header('Location: /learn/quiz/intro/' . $quizId);
            exit;
        }

        try {
            $attempt = $this->quizService->startAttempt($quizId, $userId);
            // Redirect to the runner view with the attempt ID
            header('Location: /learn/quiz/attempt/' . $attempt['id']);
        } catch (\Exception $e) {
            if ($e->getMessage() === 'MAX_ATTEMPTS_REACHED') {
                $_SESSION['flash_error'] = 'You have used all allowed attempts for this quiz.';
                header('Location: /learn/quiz/intro/' . $quizId);
            } elseif ($e->getMessage() === 'ACCESS_DENIED_NOT_ENROLLED') {
                $_SESSION['flash_error'] = 'You must be enrolled to start this quiz.';
                header('Location: /dashboard');
            } else {
                header('Location: /dashboard');
            }
        }
        exit;
    }

    /**
     * Render the Quiz Runner Interface.
     */
    public function show(string $attemptId): void
    {
        $attemptId = (int) $attemptId;
        $userId = $_SESSION['user_id'];

        $data = $this->quizService->getQuizContext($attemptId, $userId);

        if (!$data || !empty($data['attempt']['completed_at'])) {
            // If invalid or already done, go to results
            header('Location: /learn/quiz/result/' . $attemptId);
            exit;
        }

        // Calculate Real Remaining Time (Server-Side Enforcement)
        $timeLimitMinutes = $data['attempt']['time_limit'];
        $remainingSeconds = 0;

        if ($timeLimitMinutes > 0) {
            $startedAt = strtotime($data['attempt']['started_at']);
            $elapsedSeconds = time() - $startedAt;
            $totalSeconds = $timeLimitMinutes * 60;
            $remainingSeconds = $totalSeconds - $elapsedSeconds;

            // If time is up (allowing 10s buffer for latency), auto-submit
            if ($remainingSeconds <= -10) {
                // Force Submit
                $this->quizService->submitAttempt($attemptId, $userId);
                header('Location: /learn/quiz/result/' . $attemptId);
                exit;
            }
        }

        // Pass calculated remaining time to view
        $data['remainingSeconds'] = $remainingSeconds > 0 ? $remainingSeconds : 0;

        $this->view('learn.quiz_runner', $data);
    }

    /**
     * AJAX: Save Answer
     */
    public function saveAnswer(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $input = json_decode(file_get_contents('php://input'), true);
        $attemptId = (int) ($input['attempt_id'] ?? 0);
        $questionId = (int) ($input['question_id'] ?? 0);
        $answer = $input['answer'] ?? null;
        $userId = $_SESSION['user_id'];

        // Rate Limit: 60 answers per minute (approx 1 per sec) to prevent spam
        if (!\App\Core\RateLimiter::attempt('quiz_save_' . $userId, 60, 60)) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests']);
            return;
        }

        if ($attemptId && $questionId) {
            $this->quizService->saveAnswer($attemptId, $userId, $questionId, $answer);
            echo json_encode(['status' => 'saved']);
        } else {
            http_response_code(400);
        }
    }

    /**
     * Finish Attempt
     */
    public function submit(string $attemptId): void
    {
        $attemptId = (int) $attemptId;
        $userId = $_SESSION['user_id'];

        try {
            $result = $this->quizService->submitAttempt($attemptId, $userId);
            
            // If passed, trigger progress update
            if ($result['is_passed'] && ($result['grading_status'] ?? 'final') === 'final') {
                $db = \App\Core\Database::getInstance();
                
                // 1. Fetch Item/Lesson Context
                $quizContext = $db->fetchAll("SELECT q.lesson_id, c.id as course_id 
                                             FROM quizzes q 
                                             JOIN lessons l ON q.lesson_id = l.id 
                                             JOIN modules m ON l.module_id = m.id 
                                             JOIN courses c ON m.course_id = c.id 
                                             WHERE q.id = :qid", [':qid' => $result['quiz_id']])[0] ?? null;
                
                if ($quizContext) {
                    $lessonId = $quizContext['lesson_id'];
                    $courseId = $quizContext['course_id'];

                    // New Model Progress (content_items)
                    $item = $db->fetchAll("SELECT id FROM content_items WHERE item_type = 'quiz' AND item_id = :qid", [':qid' => $result['quiz_id']])[0] ?? null;
                    if ($item && $this->hasTable($db, 'content_progress')) {
                        // Fetch current attempts to increment safely via generic upsert
                        $currentProgress = $db->fetchAll("SELECT attempts FROM content_progress WHERE user_id = :uid AND item_id = :iid", [':uid' => $userId, ':iid' => $item['id']])[0] ?? null;
                        $newAttempts = ($currentProgress['attempts'] ?? 0) + 1;

                        $db->upsert(
                            'content_progress',
                            [
                                'user_id' => $userId,
                                'item_id' => $item['id'],
                                'status' => 'completed',
                                'completed_at' => date('Y-m-d H:i:s'), // Current timestamp
                                'score' => $result['score'],
                                'attempts' => $newAttempts
                            ],
                            ['user_id', 'item_id']
                        );
                    }

                    // Legacy progress
                    $db->insertIgnore('completed_lessons', [
                        'user_id' => $userId,
                        'lesson_id' => $lessonId
                    ]);

                    // Trigger Course Progress Update
                    $progressService = new \App\Services\CourseProgressService();
                    $progressService->updateProgress($userId, $courseId);
                    
                    // Log xAPI
                    $db->query("INSERT INTO xapi_logs (user_id, lesson_id, verb, meta_data) VALUES (:uid, :lid, 'completed', :meta)", [
                        ':uid' => $userId,
                        ':lid' => $lessonId,
                        ':meta' => json_encode(['score' => $result['score'], 'quiz_id' => $result['quiz_id']])
                    ]);
                }
            }

            if (($result['grading_status'] ?? 'final') === 'pending') {
                $_SESSION['flash_info'] = 'Your submission is pending manual review.';
            }
            header('Location: /learn/quiz/result/' . $attemptId);
        } catch (\Exception $e) {
            if ($e->getMessage() === 'INVALID_VIDEO_URL') {
                $_SESSION['flash_error'] = 'Video submissions must be valid URLs (http/https).';
                header('Location: /learn/quiz/attempt/' . $attemptId);
                exit;
            }
            // Log error but try to redirect to result if attempt was at least created/graded partially
            // If attemptId exists, we should try to show its state rather than dumping to dashboard
            error_log("Quiz Submit Error: " . $e->getMessage());
            
            if (isset($attemptId) && $attemptId > 0) {
                 $_SESSION['flash_error'] = 'An error occurred during submission processing, but your attempt was recorded.';
                 header('Location: /learn/quiz/result/' . $attemptId);
            } else {
                 $_SESSION['flash_error'] = 'Submission failed: ' . $e->getMessage();
                 header('Location: /dashboard');
            }
        }
    }

        /**

         * Show Result Page

         */

        public function result(string $attemptId): void

        {

            $userId = $_SESSION['user_id'];

            $db = \App\Core\Database::getInstance();

            

            // 1. Fetch Attempt & Context

            $sql = "SELECT qa.*, q.passing_score, q.show_correct_answers, l.title as lesson_title, l.id as lesson_id, c.id as course_id

                    FROM quiz_attempts qa 

                    JOIN quizzes q ON qa.quiz_id = q.id

                    JOIN lessons l ON q.lesson_id = l.id

                    JOIN modules m ON l.module_id = m.id

                    JOIN courses c ON m.course_id = c.id

                    WHERE qa.id = :id AND qa.user_id = :uid";

            

            $attempt = $db->fetchAll($sql, [':id' => $attemptId, ':uid' => $userId]);

    

            if (empty($attempt)) {

                header('Location: /dashboard');

                exit;

            }

            $attempt = $attempt[0];

    

            // 1.5 Fetch Detailed Results if allowed

            $canReview = !empty($attempt['show_correct_answers']); 

            

            $reviewData = null;

            if ($canReview) {

                $questions = $db->fetchAll("SELECT * FROM questions WHERE quiz_id = :qid ORDER BY sort_order ASC, id ASC", [':qid' => $attempt['quiz_id']]);

                $userAnswers = json_decode($attempt['answers'] ?? '{}', true);

                

                // Format for View

                foreach ($questions as &$q) {

                    $q['options'] = json_decode($q['options'] ?? '[]', true);

                    $q['correct_answers'] = json_decode($q['correct_answers'] ?? '[]', true);

                    $q['user_answer'] = $userAnswers[$q['id']] ?? null;

                }

                $reviewData = $questions;

            }

    

            // 2. Fetch Course & Curriculum (New ContentItems Logic)

            $courseModel = new \App\Domain\Courses\Course();

            $course = $courseModel->find($attempt['course_id']);

            $modules = $courseModel->modules($attempt['course_id']);

            $contentItems = $courseModel->contentItems($attempt['course_id']);

            $useContentItems = !empty($contentItems);

    

            // Build Playlist & Flat Items

            $playlist = [];

            $flatItems = [];

    

            if ($useContentItems) {

                $moduleMap = [];

                foreach ($modules as $m) {

                    $moduleMap[$m['id']] = $m;

                    $moduleMap[$m['id']]['items'] = [];

                }

                foreach ($contentItems as $item) {

                    $flatItems[] = $item;

                    if (isset($moduleMap[$item['module_id']])) {

                        $moduleMap[$item['module_id']]['items'][] = $item;

                    }

                }

                $playlist = array_values($moduleMap);

            } else {

                // Legacy Fallback

                $moduleModel = new \App\Domain\Courses\Module();

                foreach ($modules as &$module) {

                    $module['lessons'] = $moduleModel->lessons($module['id']);

                    foreach ($module['lessons'] as $l) {

                        $l['item_type'] = 'lesson';

                        $l['item_id'] = $l['id'];

                        $flatItems[] = $l;

                    }

                    $module['items'] = $module['lessons'];

                }

                $playlist = $modules;

            }

    

            // 3. Determine Current Item (The Quiz that was just attempted)

            // Find item corresponding to the quiz's lesson_id (legacy mapping) or direct quiz item

            $currentItem = null;

            if ($useContentItems) {

                // Try finding by Quiz ID

                foreach ($flatItems as $item) {

                    if ($item['item_type'] === 'quiz' && $item['item_id'] == $attempt['quiz_id']) {

                        $currentItem = $item;

                        break;

                    }

                }

                // Fallback: Try finding by Lesson ID (if quiz attached to lesson in old model)

                if (!$currentItem) {

                    foreach ($flatItems as $item) {

                        if ($item['item_type'] === 'lesson' && $item['item_id'] == $attempt['lesson_id']) {

                            $currentItem = $item;

                            break;

                        }

                    }

                }

            } else {

                // Legacy: Find by lesson_id

                foreach ($flatItems as $item) {

                    if ($item['id'] == $attempt['lesson_id']) {

                        $currentItem = $item;

                        break;

                    }

                }

            }

    

                    // 4. Calculate Prev/Next

    

                    $prevItem = null;

    

                    $nextItem = null;

    

                    $found = false;

    

                    

    

                    if ($currentItem) {

    

                        foreach ($flatItems as $item) {

    

                            if ($found) {

    

                                $nextItem = $item;

    

                                break;

    

                            }

    

                            if ($useContentItems && isset($item['id']) && $currentItem['id'] == $item['id']) {

    

                                $found = true;

    

                            } elseif (!$useContentItems && $item['id'] == $currentItem['id']) {

    

                                $found = true;

    

                            } else {

    

                                $prevItem = $item;

    

                            }

    

                        }

    

                    }

    

            

    

                    $nextItemUrl = null;

    

                    if ($nextItem) {

    

                        $nextItemUrl = ($useContentItems && isset($nextItem['id']))

    

                            ? "/learn/{$attempt['course_id']}/item/{$nextItem['id']}"

    

                            : "/learn/{$attempt['course_id']}/lesson/{$nextItem['item_id']}";

    

                    }

    

            

    

                    // 5. Get Progress

    

                    $completedLessonIds = [];

    

                    $completedItems = [];

    

                    

    

                    if ($useContentItems) {

    

                        $sqlProgress = "SELECT ci.item_type, ci.item_id 

    

                                        FROM content_progress cp 

    

                                        JOIN content_items ci ON cp.item_id = ci.id 

    

                                        WHERE cp.user_id = :uid AND cp.status = 'completed'";

    

                        $progress = $db->fetchAll($sqlProgress, [':uid' => $userId]);

    

                        $completedItems = $progress;

    

                    } else {

    

                        $completedSql = "SELECT lesson_id FROM completed_lessons WHERE user_id = :uid";

    

                        $rows = $db->fetchAll($completedSql, [':uid' => $userId]);

    

                        $completedLessonIds = array_column($rows, 'lesson_id');

    

                    }

    

            

    

                    $this->view('learn.player', [

    

                        'course' => $course,

    

                        'modules' => $playlist,

    

                        'currentLesson' => ['id' => $attempt['lesson_id'], 'title' => $attempt['lesson_title'], 'type' => 'quiz_result'], 

    

                        'currentItem' => $currentItem,

    

                        'quizResult' => $attempt, 

    

                        'reviewData' => null, 

    

                        'prevItem' => $prevItem, 

    

                        'nextItem' => $nextItem,

    

                        'nextItemUrl' => $nextItemUrl,

    

                        'completedLessonIds' => $completedLessonIds,

    

                        'completedItems' => $completedItems,

    

                        'useContentItems' => $useContentItems,

    

                        'lockedItemIds' => []

    

                    ]);

    

                }

    

            

    

        

            /**

             * Show Standalone Review Page

             */

                public function review(string $attemptId): void

                {

                    $attemptId = (int) $attemptId;

                    $userId = $_SESSION['user_id'];

                    $db = \App\Core\Database::getInstance();

            

                    // 1. Fetch Attempt & Course Info for Permission Check

                    $sql = "SELECT qa.*, q.show_correct_answers, c.instructor_id 

                            FROM quiz_attempts qa 

                            JOIN quizzes q ON qa.quiz_id = q.id 

                            JOIN lessons l ON q.lesson_id = l.id

                            JOIN modules m ON l.module_id = m.id

                            JOIN courses c ON m.course_id = c.id

                            WHERE qa.id = :id";

                    

                    $attempt = $db->fetchAll($sql, [':id' => $attemptId])[0] ?? null;

            

                    if (!$attempt) {

                        header('Location: /dashboard');

                        exit;

                    }

            

                    // Permission Logic: Owner OR Instructor

                    $isOwner = ($attempt['user_id'] == $userId);

                    $isInstructor = ($attempt['instructor_id'] == $userId);

            

                    if (!$isOwner && !$isInstructor) {

                        header('Location: /dashboard'); // Access Denied

                        exit;

                    }

            

                    // 2. Fetch Detailed Questions

                    $questions = $db->fetchAll("SELECT * FROM questions WHERE quiz_id = :qid ORDER BY sort_order ASC, id ASC", [':qid' => $attempt['quiz_id']]);

                    $userAnswers = json_decode($attempt['answers'] ?? '{}', true);

                    

                    foreach ($questions as &$q) {

                        $q['options'] = json_decode($q['options'] ?? '[]', true);

                        $q['correct_answers'] = json_decode($q['correct_answers'] ?? '[]', true);

                        $q['user_answer'] = $userAnswers[$q['id']] ?? null;

                    }

                    // Attach grading outcomes (AI/manual) and per-question score
                    $gradingRows = $db->fetchAll("SELECT question_id, status, ai_score, ai_feedback, ai_rationale, final_score FROM grading_queue WHERE attempt_id = :aid", [
                        ':aid' => $attemptId
                    ]);
                    $gradingByQuestion = [];
                    foreach ($gradingRows as $gr) {
                        $gradingByQuestion[(int) $gr['question_id']] = $gr;
                    }

                    foreach ($questions as &$q) {
                        $g = $gradingByQuestion[(int) $q['id']] ?? null;
                        $q['ai_rationale'] = $g['ai_rationale'] ?? null;
                        $q['ai_feedback'] = $g['ai_feedback'] ?? null;
                        $q['final_score'] = null;
                        $q['allow_student_ai_view'] = ($g && ($g['status'] === 'approved'));

                        if ($g && isset($g['final_score']) && $g['status'] === 'approved') {
                            $q['final_score'] = (float) $g['final_score'];
                            continue;
                        }

                        // Auto-scored question score (basic correctness check)
                        if ($q['type'] === 'single_choice' || $q['type'] === 'true_false') {
                            $correct = $q['correct_answers'][0] ?? null;
                            $q['final_score'] = ($correct !== null && $q['user_answer'] == $correct) ? (float) $q['points'] : 0.0;
                        } elseif ($q['type'] === 'multi_choice') {
                            $ua = is_array($q['user_answer']) ? $q['user_answer'] : [];
                            $ca = is_array($q['correct_answers']) ? $q['correct_answers'] : [];
                            sort($ua);
                            sort($ca);
                            $q['final_score'] = ($ua === $ca) ? (float) $q['points'] : 0.0;
                        } elseif ($q['type'] === 'fill_blank') {
                            $ua = is_string($q['user_answer']) ? trim($q['user_answer']) : '';
                            $match = false;
                            foreach ($q['correct_answers'] as $cand) {
                                if (strtolower(trim((string) $cand)) === strtolower($ua)) {
                                    $match = true;
                                    break;
                                }
                            }
                            $q['final_score'] = $match ? (float) $q['points'] : 0.0;
                        } elseif ($q['type'] === 'matching') {
                            $ua = is_array($q['user_answer']) ? $q['user_answer'] : [];
                            $allMatch = true;
                            foreach ($q['correct_answers'] as $pair) {
                                $left = $pair['left_id'] ?? null;
                                $right = $pair['right_id'] ?? null;
                                if ($left === null || $right === null || !isset($ua[$left]) || (int) $ua[$left] !== (int) $right) {
                                    $allMatch = false;
                                    break;
                                }
                            }
                            $q['final_score'] = $allMatch ? (float) $q['points'] : 0.0;
                        } elseif (in_array($q['type'], ['short_text'], true)) {
                            $ua = $q['user_answer'];
                            $correct = $q['correct_answers'][0] ?? null;
                            $q['final_score'] = ($correct !== null && $ua == $correct) ? (float) $q['points'] : 0.0;
                        }
                    }

            

    $this->view('learn.quiz_review', ['reviewData' => $questions, 'attempt' => $attempt]);

}

    private function hasTable(\App\Core\Database $db, string $table): bool
    {
        $driver = $db->getConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $tableCheck = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name = :table", [
                ':table' => $table
            ]);
        } else {
            $tableCheck = $db->fetchAll("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table", [
                ':table' => $table
            ]);
        }

        return !empty($tableCheck);
    }

            }

            

        
