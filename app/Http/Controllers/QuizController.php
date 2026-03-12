<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Http\Middleware\AuthMiddleware;

use App\Policies\Authorization;

class QuizController extends Controller
{
    private Database $db;

    public function __construct()
    {
        (new AuthMiddleware())->handle(['instructor', 'master_instructor', 'admin']);
        $this->db = Database::getInstance();
    }

    /**
     * Show the Quiz Builder for a specific lesson.
     * Automatically creates a default quiz entry if one doesn't exist.
     */
    public function build(string $lessonId): void
    {
        $lessonId = (int) $lessonId;

        // 1. Fetch or Create Quiz
        try {
            $quiz = $this->getOrCreateQuiz($lessonId);
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Lesson not found.';
            header('Location: /dashboard');
            exit;
        }

        // 2. Fetch Course ID for the "Back" link
        $sqlCourse = "SELECT m.course_id 
                      FROM lessons l 
                      JOIN modules m ON l.module_id = m.id 
                      WHERE l.id = :lid";
        $resCourse = $this->db->fetchAll($sqlCourse, [':lid' => $lessonId]);
        $courseId = $resCourse[0]['course_id'] ?? 0;

        // Access Check
        try {
            Authorization::requireInstructor($_SESSION['user_id'], $courseId);
        } catch (\Exception $e) {
            header('Location: /dashboard');
            exit;
        }

        // 3. Fetch Questions
        $sql = "SELECT * FROM questions WHERE quiz_id = :qid ORDER BY sort_order ASC, id ASC";
        $questions = $this->db->fetchAll($sql, [':qid' => $quiz['id']]);

        // Decode JSON fields for the view
        foreach ($questions as &$q) {
            $q['options'] = json_decode($q['options'] ?? '[]', true);
            $q['correct_answers'] = json_decode($q['correct_answers'] ?? '[]', true);
        }

        // 4. Fetch Question Banks for Import
        $bankModel = new \App\Domain\Quizzes\QuestionBank();
        $banks = $bankModel->findAllByInstructor($_SESSION['user_id']);
        // Fetch questions for each bank too (simplified for the modal)
        $bankQuestionModel = new \App\Domain\Quizzes\BankQuestion();
        foreach ($banks as &$bank) {
            $bank['questions'] = $bankQuestionModel->findAllByBank($bank['id']);
        }

        $this->view('instructor.quiz_builder', [
            'lesson_id' => $lessonId,
            'quiz' => $quiz,
            'course_id' => $courseId,
            'questions' => $questions,
            'banks' => $banks
        ]);
    }

    /**
     * Update General Quiz Settings (Time limit, Passing score, etc.)
     */
    public function updateSettings(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $quizId = (int) ($_POST['quiz_id'] ?? 0);
        $lessonId = (int) ($_POST['lesson_id'] ?? 0);

        if (!$quizId) {
            header("Location: /dashboard"); // Error
            exit;
        }

        // Security: Verify Quiz Ownership via Course
        $sqlCheck = "SELECT m.course_id FROM quizzes q JOIN lessons l ON q.lesson_id = l.id JOIN modules m ON l.module_id = m.id WHERE q.id = :qid";
        $res = $this->db->fetchAll($sqlCheck, [':qid' => $quizId]);

        if (empty($res)) {
            http_response_code(403);
            exit;
        }

        try {
            Authorization::requireInstructor($_SESSION['user_id'], (int)$res[0]['course_id']);
        } catch (\Exception $e) {
            http_response_code(403);
            exit;
        }

        $sql = "UPDATE quizzes SET 
                time_limit = :time, 
                passing_score = :score, 
                allowed_attempts = :attempts, 
                is_randomized = :rand, 
                show_correct_answers = :show,
                status = :status
                WHERE id = :id";

        $this->db->query($sql, [
            ':time' => (int) $_POST['time_limit'],
            ':score' => (int) $_POST['passing_score'],
            ':attempts' => (int) $_POST['allowed_attempts'],
            ':rand' => isset($_POST['is_randomized']) ? 1 : 0,
            ':show' => isset($_POST['show_correct_answers']) ? 1 : 0,
            ':status' => $_POST['status'] ?? 'draft',
            ':id' => $quizId
        ]);

        $_SESSION['flash_success'] = 'Quiz settings saved.';
        header("Location: /instructor/lesson/$lessonId/quiz");
        exit;
    }

    /**
     * Add a new question via AJAX/Form
     */
    public function addQuestion(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $quizId = (int) $_POST['quiz_id'];
        $lessonId = (int) $_POST['lesson_id'];
        $type = trim($_POST['type'] ?? '');
        $text = trim($_POST['question_text'] ?? '');
        $points = (int) ($_POST['points'] ?? 1);
        $explanation = $_POST['explanation'] ?? '';

        // --- Required Field Validation ---
        $errors = [];
        $validTypes = ['single_choice', 'multi_choice', 'true_false', 'short_text', 'fill_blank', 'matching', 'essay', 'video_submission'];

        if (empty($type)) {
            $errors[] = 'Question type is required.';
        } elseif (!in_array($type, $validTypes, true)) {
            $errors[] = 'Invalid question type: ' . htmlspecialchars($type);
        }

        if (empty($text) && !in_array($type, ['matching'], true)) {
            $errors[] = 'Question text is required.';
        }

        if ($points < 1) {
            $errors[] = 'Points must be at least 1.';
        }

        if ($quizId < 1) {
            $errors[] = 'Invalid quiz ID.';
        }

        // Type-specific validation
        if (in_array($type, ['single_choice', 'multi_choice'], true)) {
            $rawOptions = $_POST['option_text'] ?? [];
            $validOptions = array_filter($rawOptions, fn($o) => trim($o) !== '');
            if (count($validOptions) < 2) {
                $errors[] = 'Choice questions require at least 2 options.';
            }
        }

        if ($type === 'fill_blank') {
            $answers = trim($_POST['fill_blank_answers'] ?? '');
            if (empty($answers)) {
                $errors[] = 'Fill-in-the-blank questions require at least one correct answer.';
            }
        }

        if ($type === 'matching') {
            $left = array_filter($_POST['match_left'] ?? [], fn($v) => trim($v) !== '');
            $right = array_filter($_POST['match_right'] ?? [], fn($v) => trim($v) !== '');
            if (count($left) < 2 || count($right) < 2) {
                $errors[] = 'Matching questions require at least 2 pairs.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode(' ', $errors);
            header("Location: /instructor/lesson/$lessonId/quiz");
            exit;
        }
        // --- End Validation ---

        // Security Check
        $sqlCheck = "SELECT m.course_id FROM lessons l JOIN modules m ON l.module_id = m.id WHERE l.id = :lid";
        $res = $this->db->fetchAll($sqlCheck, [':lid' => $lessonId]);

        if (empty($res)) {
            http_response_code(403);
            exit;
        }
        try {
            Authorization::requireInstructor($_SESSION['user_id'], (int)$res[0]['course_id']);
        } catch (\Exception $e) {
            http_response_code(403);
            exit;
        }


        // Process Options & Answers based on Type
        $options = [];
        $correctAnswers = [];
        $gradingPolicy = $_POST['grading_policy'] ?? null;
        if ($type === 'fill_blank') {
            $gradingPolicy = $gradingPolicy ?: 'both';
        } elseif ($type === 'matching') {
            $gradingPolicy = 'strict';
        } elseif ($type === 'essay') {
            $gradingPolicy = $gradingPolicy ?: 'ai';
        } elseif ($type === 'video_submission') {
            $gradingPolicy = 'manual';
        } else {
            $gradingPolicy = $gradingPolicy ?: 'auto';
        }
        $aiRubric = $_POST['ai_rubric'] ?? null;

        if (in_array($type, ['single_choice', 'multi_choice'])) {
            // Expecting arrays from form: option_text[], is_correct[] OR correct_option (radio)
            $rawOptions = $_POST['option_text'] ?? [];
            $rawCorrect = $_POST['is_correct'] ?? []; // For checkboxes
            $radioCorrectIndex = isset($_POST['correct_option']) ? (int)$_POST['correct_option'] : -1; // For radio

            foreach ($rawOptions as $index => $optText) {
                if (trim($optText) === '') continue;

                $id = $index + 1; // Simple ID generation
                $options[] = ['id' => $id, 'text' => trim($optText)];

                // Check if this index was marked correct
                // Case 1: Multi-choice Checkbox
                if (isset($rawCorrect[$index])) {
                    $correctAnswers[] = $id;
                }
                // Case 2: Single-choice Radio
                elseif ($radioCorrectIndex === $index) {
                    $correctAnswers[] = $id;
                }
            }
        } elseif ($type === 'true_false') {
            $options = [
                ['id' => 1, 'text' => 'True'],
                ['id' => 0, 'text' => 'False']
            ];
            $correctAnswers[] = (int) ($_POST['tf_correct'] ?? 1);
        } elseif ($type === 'fill_blank') {
            $raw = $_POST['fill_blank_answers'] ?? '';
            $answers = array_filter(array_map('trim', explode(',', $raw)));
            $correctAnswers = array_values($answers);
        } elseif ($type === 'matching') {
            $left = $_POST['match_left'] ?? [];
            $right = $_POST['match_right'] ?? [];
            $pairs = [];
            $correct = [];
            foreach ($left as $index => $leftText) {
                $leftText = trim($leftText);
                $rightText = trim($right[$index] ?? '');
                if ($leftText === '' || $rightText === '') continue;
                $leftId = $index + 1;
                $rightId = $index + 1;
                $pairs[] = [
                    'left_id' => $leftId,
                    'left_text' => $leftText,
                    'right_id' => $rightId,
                    'right_text' => $rightText
                ];
                $correct[] = ['left_id' => $leftId, 'right_id' => $rightId];
            }
            $options = $pairs;
            $correctAnswers = $correct;
        } elseif (in_array($type, ['essay', 'video_submission'], true)) {
            $correctAnswers = [];
        }

        $sql = "INSERT INTO questions (quiz_id, type, question_text, points, options, correct_answers, explanation, grading_policy, ai_rubric) 
                VALUES (:qid, :type, :text, :points, :opts, :ans, :exp, :policy, :rubric)";

        $this->db->query($sql, [
            ':qid' => $quizId,
            ':type' => $type,
            ':text' => $text,
            ':points' => $points,
            ':opts' => json_encode($options),
            ':ans' => json_encode($correctAnswers),
            ':exp' => $explanation,
            ':policy' => $gradingPolicy,
            ':rubric' => $aiRubric
        ]);

        $_SESSION['flash_success'] = 'Question added.';
        header("Location: /instructor/lesson/$lessonId/quiz");
        exit;
    }

    public function updateQuestion(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $questionId = (int) $id;
        $quizId = (int) $_POST['quiz_id'];
        $lessonId = (int) $_POST['lesson_id'];

        // Security Check
        $sqlCheck = "SELECT m.course_id FROM lessons l JOIN modules m ON l.module_id = m.id WHERE l.id = :lid";
        $res = $this->db->fetchAll($sqlCheck, [':lid' => $lessonId]);
        if (empty($res)) {
            http_response_code(403);
            exit;
        }
        try {
            Authorization::requireInstructor($_SESSION['user_id'], (int)$res[0]['course_id']);
        } catch (\Exception $e) {
            http_response_code(403);
            exit;
        }

        $type = trim($_POST['type'] ?? '');
        $text = trim($_POST['question_text'] ?? '');
        $points = (int) ($_POST['points'] ?? 1);
        $explanation = $_POST['explanation'] ?? '';

        // --- Required Field Validation ---
        $errors = [];
        $validTypes = ['single_choice', 'multi_choice', 'true_false', 'short_text', 'fill_blank', 'matching', 'essay', 'video_submission'];

        if (empty($type) || !in_array($type, $validTypes, true)) {
            $errors[] = 'Invalid question type.';
        }

        if (empty($text) && !in_array($type, ['matching'], true)) {
            $errors[] = 'Question text is required.';
        }

        if ($points < 1) {
            $errors[] = 'Points must be at least 1.';
        }

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode(' ', $errors);
            header("Location: /instructor/lesson/$lessonId/quiz");
            exit;
        }
        // --- End Validation ---

        // Process Options & Answers based on Type
        $options = [];
        $correctAnswers = [];
        $gradingPolicy = $_POST['grading_policy'] ?? null;

        if ($type === 'fill_blank') {
            $gradingPolicy = $gradingPolicy ?: 'both';
        } elseif ($type === 'matching') {
            $gradingPolicy = 'strict';
        } elseif ($type === 'essay') {
            $gradingPolicy = $gradingPolicy ?: 'ai';
        } elseif ($type === 'video_submission') {
            $gradingPolicy = 'manual';
        } else {
            $gradingPolicy = $gradingPolicy ?: 'auto';
        }
        $aiRubric = $_POST['ai_rubric'] ?? null;

        if (in_array($type, ['single_choice', 'multi_choice'])) {
            $rawOptions = $_POST['option_text'] ?? [];
            $rawCorrect = $_POST['is_correct'] ?? [];
            $radioCorrectIndex = isset($_POST['correct_option']) ? (int)$_POST['correct_option'] : -1;

            foreach ($rawOptions as $index => $optText) {
                if (trim($optText) === '') continue;

                $id = $index + 1;
                $options[] = ['id' => $id, 'text' => trim($optText)];

                if (isset($rawCorrect[$index])) {
                    $correctAnswers[] = $id;
                } elseif ($radioCorrectIndex === $index) {
                    $correctAnswers[] = $id;
                }
            }
        } elseif ($type === 'true_false') {
            $options = [
                ['id' => 1, 'text' => 'True'],
                ['id' => 0, 'text' => 'False']
            ];
            $correctAnswers[] = (int) ($_POST['tf_correct'] ?? 1);
        } elseif ($type === 'fill_blank') {
            $raw = $_POST['fill_blank_answers'] ?? '';
            $answers = array_filter(array_map('trim', explode(',', $raw)));
            $correctAnswers = array_values($answers);
        } elseif ($type === 'matching') {
            $left = $_POST['match_left'] ?? [];
            $right = $_POST['match_right'] ?? [];
            $pairs = [];
            $correct = [];
            foreach ($left as $index => $leftText) {
                $leftText = trim($leftText);
                $rightText = trim($right[$index] ?? '');
                if ($leftText === '' || $rightText === '') continue;
                $leftId = $index + 1;
                $rightId = $index + 1;
                $pairs[] = [
                    'left_id' => $leftId,
                    'left_text' => $leftText,
                    'right_id' => $rightId,
                    'right_text' => $rightText
                ];
                $correct[] = ['left_id' => $leftId, 'right_id' => $rightId];
            }
            $options = $pairs;
            $correctAnswers = $correct;
        } elseif (in_array($type, ['essay', 'video_submission'], true)) {
            $correctAnswers = [];
        }

        $sql = "UPDATE questions SET 
                type = :type, 
                question_text = :text, 
                points = :points, 
                options = :opts, 
                correct_answers = :ans, 
                explanation = :exp, 
                grading_policy = :policy, 
                ai_rubric = :rubric
                WHERE id = :id";

        $this->db->query($sql, [
            ':type' => $type,
            ':text' => $text,
            ':points' => $points,
            ':opts' => json_encode($options),
            ':ans' => json_encode($correctAnswers),
            ':exp' => $explanation,
            ':policy' => $gradingPolicy,
            ':rubric' => $aiRubric,
            ':id' => $questionId
        ]);

        $_SESSION['flash_success'] = 'Question updated.';
        header("Location: /instructor/lesson/$lessonId/quiz");
        exit;
    }

    public function deleteQuestion(string $id): void
    {
        $questionId = (int) $id;

        // Fetch lesson ID to redirect back & Check security
        $sql = "SELECT q.lesson_id, m.course_id FROM quizzes q JOIN questions qu ON q.id = qu.quiz_id JOIN lessons l ON q.lesson_id = l.id JOIN modules m ON l.module_id = m.id WHERE qu.id = :id";
        $res = $this->db->fetchAll($sql, [':id' => $questionId]);

        if (empty($res)) {
            header("Location: /dashboard");
            exit;
        }

        $lessonId = $res[0]['lesson_id'];
        $courseId = $res[0]['course_id'];

        try {
            Authorization::requireInstructor($_SESSION['user_id'], (int)$courseId);
        } catch (\Exception $e) {
            header("Location: /dashboard");
            exit;
        }

        if ($lessonId) {
            $this->db->query("DELETE FROM questions WHERE id = :id", [':id' => $questionId]);
            $_SESSION['flash_success'] = 'Question deleted.';
            header("Location: /instructor/lesson/$lessonId/quiz");
        } else {
            header("Location: /dashboard");
        }
        exit;
    }

    public function importFromBank(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $quizId = (int) $_POST['quiz_id'];
        $lessonId = (int) $_POST['lesson_id'];
        $selectedIds = $_POST['bank_question_ids'] ?? [];

        // Security
        $sqlCheck = "SELECT m.course_id FROM lessons l JOIN modules m ON l.module_id = m.id WHERE l.id = :lid";
        $res = $this->db->fetchAll($sqlCheck, [':lid' => $lessonId]);
        if (empty($res)) {
            http_response_code(403);
            exit;
        }
        try {
            Authorization::requireInstructor($_SESSION['user_id'], (int)$res[0]['course_id']);
        } catch (\Exception $e) {
            http_response_code(403);
            exit;
        }

        if (empty($selectedIds)) {
            header("Location: /instructor/lesson/$lessonId/quiz");
            exit;
        }

        $bankQuestionModel = new \App\Domain\Quizzes\BankQuestion();

        foreach ($selectedIds as $bqId) {
            $bq = $bankQuestionModel->find((int)$bqId);
            if ($bq) {
                // Copy to questions table
                $gradingPolicy = 'auto';
                if ($bq['type'] === 'fill_blank') {
                    $gradingPolicy = 'both';
                } elseif ($bq['type'] === 'matching') {
                    $gradingPolicy = 'strict';
                } elseif ($bq['type'] === 'essay') {
                    $gradingPolicy = 'ai';
                } elseif ($bq['type'] === 'video_submission') {
                    $gradingPolicy = 'manual';
                }
                $sql = "INSERT INTO questions (quiz_id, type, question_text, options, correct_answers, explanation, grading_policy) 
                        VALUES (:qid, :type, :text, :opts, :correct, :exp, :policy)";

                $this->db->query($sql, [
                    ':qid' => $quizId,
                    ':type' => $bq['type'],
                    ':text' => $bq['question_text'],
                    ':opts' => $bq['options'],
                    ':correct' => $bq['correct_answers'],
                    ':exp' => $bq['explanation'],
                    ':policy' => $gradingPolicy
                ]);
            }
        }

        $_SESSION['flash_success'] = count($selectedIds) . ' questions imported from bank.';
        header("Location: /instructor/lesson/$lessonId/quiz");
        exit;
    }

    private function getOrCreateQuiz(int $lessonId): array
    {
        $sql = "SELECT * FROM quizzes WHERE lesson_id = :lid";
        $res = $this->db->fetchAll($sql, [':lid' => $lessonId]);

        if (!empty($res)) {
            return $res[0];
        }

        // Fetch parent lesson context first to fail fast if invalid
        $parentLesson = $this->db->fetchAll("SELECT l.module_id, m.course_id FROM lessons l JOIN modules m ON l.module_id = m.id WHERE l.id = :lid", [':lid' => $lessonId])[0] ?? null;

        if (!$parentLesson) {
            // Should theoretically not happen if UI is consistent, but safety check
            throw new \Exception("Cannot create quiz for invalid lesson ID: $lessonId");
        }

        $this->db->getConnection()->beginTransaction();
        try {
            // Create Default Quiz Payload
            $sqlInsert = "INSERT INTO quizzes (lesson_id) VALUES (:lid)";
            $this->db->query($sqlInsert, [':lid' => $lessonId]);
            $quizId = $this->db->getConnection()->lastInsertId();

            // Sync with ContentItems (Structure)
            $parentItem = $this->db->fetchAll("SELECT id, position, display_group_id FROM content_items WHERE item_type = 'lesson' AND item_id = :lid", [':lid' => $lessonId])[0] ?? null;

            $pos = ($parentItem['position'] ?? 0) + 1;
            $groupId = $parentItem['id'] ?? null;

            if ($parentItem) {
                if (empty($parentItem['display_group_id'])) {
                    // Create a new group ID based on parent item ID to ensure uniqueness but linkage
                    $newGroupId = (int) $parentItem['id'] + 900000;
                    $this->db->query("UPDATE content_items SET display_group_id = :gid WHERE id = :id", [':gid' => $newGroupId, ':id' => $parentItem['id']]);
                    $groupId = $newGroupId;
                } else {
                    $groupId = $parentItem['display_group_id'];
                }
            }

            $contentItemModel = new \App\Domain\Courses\ContentItem();
            $contentItemModel->createItem([
                'course_id' => $parentLesson['course_id'],
                'module_id' => $parentLesson['module_id'],
                'item_type' => 'quiz',
                'item_id' => $quizId,
                'position' => $pos,
                'display_group_id' => $groupId
            ]);

            $this->db->getConnection()->commit();
        } catch (\Exception $e) {
            $this->db->getConnection()->rollBack();
            throw $e;
        }

        // Fetch again
        return $this->db->fetchAll($sql, [':lid' => $lessonId])[0];
    }
}
