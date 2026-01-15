<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Domain\Courses\Course;
use App\Domain\Courses\Module;
use App\Core\Database;
use App\Http\Middleware\AuthMiddleware;
use App\Policies\Authorization;

class LearnController extends Controller
{
    private Database $db;

    public function __construct()
    {
        (new AuthMiddleware())->handle();
        $this->db = Database::getInstance();
    }

    public function show(string $courseId, ?string $lessonId = null): void
    {
        $courseId = (int) $courseId;
        $userId = $_SESSION['user_id'];

        // 1. Security Check: Enrollment
        try {
            Authorization::requireEnrollment($userId, $courseId);
        } catch (\Exception $e) {
            header('Location: /course/' . $courseId);
            exit;
        }

        // 2. Fetch Course Data
        $courseModel = new Course();
        $course = $courseModel->find($courseId);
        
        $modules = $courseModel->modules($courseId);
        $moduleModel = new Module();
        
        // Build Playlist & Find Current Lesson
        $flatLessons = [];
        foreach ($modules as &$module) {
            $module['lessons'] = $moduleModel->lessons($module['id']);
            foreach ($module['lessons'] as $l) {
                $flatLessons[] = $l;
            }
        }

        // 3. Determine Lesson to Play
        $currentLesson = null;
        if ($lessonId) {
            // Find specific lesson
            foreach ($flatLessons as $l) {
                if ($l['id'] == $lessonId) {
                    $currentLesson = $l;
                    break;
                }
            }
        } else {
            // Resume logic: Find first uncompleted lesson
            $completedIds = $this->getCompletedLessonIds($userId);
            foreach ($flatLessons as $l) {
                if (!in_array($l['id'], $completedIds)) {
                    $currentLesson = $l;
                    break;
                }
            }
            // If all completed, or none found, default to first
            if (!$currentLesson && !empty($flatLessons)) {
                $currentLesson = $flatLessons[0];
            }
        }

        if (!$currentLesson) {
            // Empty course? Redirect back
            header('Location: /course/' . $courseId);
            exit;
        }

        // 3.5 Check Content Flow (Linear vs Free)
        $progressService = new \App\Services\CourseProgressService();
        if ($progressService->isLessonLocked($userId, $courseId, $currentLesson['id'])) {
            $_SESSION['flash_error'] = 'This lesson is locked. Please complete the previous lesson first.';
            
            // Redirect to the last completed lesson or the first one if none
            // Simple fallback: just refresh page without lesson ID (which defaults to first/resume logic)
            // But we already calculated resume logic above. If we are here, user specifically requested a locked lesson.
            // So we redirect to the "Resume" lesson calculated in step 3 (if no ID was passed) or re-calc.
            // Let's just redirect to course home for simplicity, or re-run resume logic.
            header('Location: /learn/' . $courseId);
            exit;
        }

        // 4. Calculate Prev/Next
        $prevLesson = null;
        $nextLesson = null;
        $found = false;
        foreach ($flatLessons as $l) {
            if ($found) {
                $nextLesson = $l;
                break;
            }
            if ($l['id'] === $currentLesson['id']) {
                $found = true;
            } else {
                $prevLesson = $l;
            }
        }

        // 5. Get Completed Status for Playlist UI
        $completedLessonIds = $this->getCompletedLessonIds($userId);

        // 6. If it's a quiz, fetch the Quiz ID
        $quizId = null;
        if ($currentLesson['type'] === 'quiz') {
            $sqlQuiz = "SELECT id FROM quizzes WHERE lesson_id = :lid";
            $resQuiz = $this->db->fetchAll($sqlQuiz, [':lid' => $currentLesson['id']]);
            $quizId = $resQuiz[0]['id'] ?? null;
        }

        $this->view('learn.player', [
            'course' => $course,
            'modules' => $modules,
            'currentLesson' => $currentLesson,
            'quizId' => $quizId,
            'prevLesson' => $prevLesson,
            'nextLesson' => $nextLesson,
            'completedLessonIds' => $completedLessonIds
        ]);
    }

    public function track(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $input = json_decode(file_get_contents('php://input'), true);

        $input = json_decode(file_get_contents('php://input'), true);
        $lessonId = (int) ($input['lesson_id'] ?? 0);
        $verb = $input['verb'] ?? '';
        $userId = $_SESSION['user_id'];

        if (!$lessonId || !$verb) {
            http_response_code(400);
            return;
        }

        // 1. Security Check: Find Course and Verify Enrollment
        $sqlCourse = "SELECT m.course_id FROM lessons l JOIN modules m ON l.module_id = m.id WHERE l.id = :lid";
        $res = $this->db->fetchAll($sqlCourse, [':lid' => $lessonId]);
        
        if (empty($res)) {
            // Lesson not found or not assigned to a course
            http_response_code(404);
            echo json_encode(['error' => 'Lesson not found in any course']);
            exit;
        }

        $courseId = (int) $res[0]['course_id'];

        try {
            Authorization::requireEnrollment($userId, $courseId);
        } catch (\Exception $e) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied: not enrolled']);
            exit;
        }

        // 2. Authorized: Log xAPI
        $sqlLog = "INSERT INTO xapi_logs (user_id, lesson_id, verb, meta_data) VALUES (:uid, :lid, :verb, :meta)";
        $this->db->query($sqlLog, [
            ':uid' => $userId, 
            ':lid' => $lessonId, 
            ':verb' => $verb, 
            ':meta' => json_encode($input['meta'] ?? [])
        ]);

        // 3. Authorized: Mark Completed
        if ($verb === 'completed') {
            $sqlComp = "INSERT OR IGNORE INTO completed_lessons (user_id, lesson_id) VALUES (:uid, :lid)";
            $this->db->query($sqlComp, [':uid' => $userId, ':lid' => $lessonId]);
            
            // Trigger Course Progress Update
            $progressService = new \App\Services\CourseProgressService();
            $progressService->updateProgress($userId, $courseId);
        }

        echo json_encode(['status' => 'success']);
    }

    private function getCompletedLessonIds(int $userId): array
    {
        $sql = "SELECT lesson_id FROM completed_lessons WHERE user_id = :uid";
        $rows = $this->db->fetchAll($sql, [':uid' => $userId]);
        return array_column($rows, 'lesson_id');
    }
}