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
        $this->loadPlayer((int) $courseId, $lessonId ? (int) $lessonId : null, 'lesson');
    }

    public function showItem(string $courseId, string $itemId): void
    {
        $this->loadPlayer((int) $courseId, (int) $itemId, 'item');
    }

    private function loadPlayer(int $courseId, ?int $targetId, string $targetType): void
    {
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
        
        // 3. Fetch Content Items (The New Model)
        $contentItems = $courseModel->contentItems($courseId);
        
        // Build Playlist Hierarchy & Flat List
        $flatItems = [];
        $playlist = [];
        
        if (!empty($contentItems)) {
            // New Item-Centric Logic
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
            // Legacy Lesson-Centric Logic Fallback
            $moduleModel = new Module();
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

        // 4. Determine Current Item
        $currentItem = null;
        if ($targetId) {
            foreach ($flatItems as $item) {
                if ($targetType === 'item' && isset($item['id']) && $item['id'] == $targetId) {
                    $currentItem = $item;
                    break;
                } elseif ($targetType === 'lesson' && $item['item_type'] === 'lesson' && $item['item_id'] == $targetId) {
                    $currentItem = $item;
                    break;
                }
            }
        }

        // Resume logic if no target or not found
        if (!$currentItem) {
            $completedIds = $this->getCompletedItemIds($userId, $courseId);
            foreach ($flatItems as $item) {
                $isCompleted = false;
                foreach ($completedIds as $c) {
                    if ($c['item_type'] === $item['item_type'] && $c['item_id'] === $item['item_id']) {
                        $isCompleted = true;
                        break;
                    }
                }
                if (!$isCompleted) {
                    $currentItem = $item;
                    break;
                }
            }
            if (!$currentItem && !empty($flatItems)) {
                $currentItem = $flatItems[0];
            }
        }

        if (!$currentItem) {
            header('Location: /course/' . $courseId);
            exit;
        }

        // 5. Locking Logic (Placeholder for updated service)
        $progressService = new \App\Services\CourseProgressService();
        $isLocked = false;
        if ($currentItem['item_type'] === 'lesson') {
            $isLocked = $progressService->isLessonLocked($userId, $courseId, (int) $currentItem['item_id']);
        }
        
        if ($isLocked) {
            $_SESSION['flash_error'] = 'This item is locked.';
            header('Location: /learn/' . $courseId);
            exit;
        }

        // 6. Calculate Prev/Next
        $prevItem = null;
        $nextItem = null;
        $found = false;
        foreach ($flatItems as $item) {
            if ($found) {
                $nextItem = $item;
                break;
            }
            if ($item['item_type'] === $currentItem['item_type'] && $item['item_id'] === $currentItem['item_id']) {
                $found = true;
            } else {
                $prevItem = $item;
            }
        }

        // 7. Get Progress for UI
        $completedItemIds = $this->getCompletedItemIds($userId, $courseId);
        
        // Fetch underlying lesson data for rendering if it's a lesson or quiz
        $currentLesson = null;
        $quizId = null;
        if ($currentItem['item_type'] === 'lesson') {
            $currentLesson = $this->db->fetchAll("SELECT * FROM lessons WHERE id = :id", [':id' => $currentItem['item_id']])[0] ?? null;
        } elseif ($currentItem['item_type'] === 'quiz') {
            $quiz = $this->db->fetchAll("SELECT * FROM quizzes WHERE id = :id", [':id' => $currentItem['item_id']])[0] ?? null;
            if ($quiz) {
                $quizId = $quiz['id'];
                $currentLesson = $this->db->fetchAll("SELECT * FROM lessons WHERE id = :id", [':id' => $quiz['lesson_id']])[0] ?? null;
            }
        }

        $this->view('learn.player', [
            'course' => $course,
            'modules' => $playlist,
            'currentItem' => $currentItem,
            'currentLesson' => $currentLesson,
            'quizId' => $quizId,
            'prevItem' => $prevItem,
            'nextItem' => $nextItem,
            'completedItems' => $completedItemIds,
            'lockedItemIds' => [] // TODO: Implement item-level locking
        ]);
    }

    private function getCompletedItemIds(int $userId, int $courseId): array
    {
        // Legacy check for lessons
        $sqlLessons = "SELECT 'lesson' as item_type, lesson_id as item_id FROM completed_lessons WHERE user_id = :uid";
        $lessons = $this->db->fetchAll($sqlLessons, [':uid' => $userId]);
        
        // New model check (content_progress)
        $tableCheck = $this->db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name='content_progress'");
        if (!empty($tableCheck)) {
            $sqlProgress = "SELECT ci.item_type, ci.item_id 
                            FROM content_progress cp 
                            JOIN content_items ci ON cp.item_id = ci.id 
                            WHERE cp.user_id = :uid AND cp.status = 'completed'";
            $progress = $this->db->fetchAll($sqlProgress, [':uid' => $userId]);
            return array_merge($lessons, $progress);
        }
        
        return $lessons;
    }

    public function track(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $input = json_decode(file_get_contents('php://input'), true);
        $lessonId = (int) ($input['lesson_id'] ?? 0);
        $itemId = (int) ($input['item_id'] ?? 0);
        $verb = $input['verb'] ?? '';
        $userId = $_SESSION['user_id'];

        if ((!$lessonId && !$itemId) || !$verb) {
            http_response_code(400);
            return;
        }

        // 1. Security Check: Find Course and Verify Enrollment
        $courseId = 0;
        if ($itemId) {
            $res = $this->db->fetchAll("SELECT course_id FROM content_items WHERE id = :id", [':id' => $itemId]);
            $courseId = (int) ($res[0]['course_id'] ?? 0);
        } elseif ($lessonId) {
            $sqlCourse = "SELECT m.course_id FROM lessons l JOIN modules m ON l.module_id = m.id WHERE l.id = :lid";
            $res = $this->db->fetchAll($sqlCourse, [':lid' => $lessonId]);
            $courseId = (int) ($res[0]['course_id'] ?? 0);
        }
        
        if (!$courseId) {
            http_response_code(404);
            echo json_encode(['error' => 'Item/Lesson not found in any course']);
            exit;
        }

        try {
            Authorization::requireEnrollment($userId, $courseId);
        } catch (\Exception $e) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied: not enrolled']);
            exit;
        }

        // 2. Authorized: Log xAPI (Legacy support)
        if ($lessonId) {
            $sqlLog = "INSERT INTO xapi_logs (user_id, lesson_id, verb, meta_data) VALUES (:uid, :lid, :verb, :meta)";
            $this->db->query($sqlLog, [
                ':uid' => $userId, 
                ':lid' => $lessonId, 
                ':verb' => $verb, 
                ':meta' => json_encode($input['meta'] ?? [])
            ]);
        }

        // 3. Authorized: Mark Completed
        if ($verb === 'completed') {
            // New model progress
            if ($itemId) {
                $this->db->query("INSERT OR REPLACE INTO content_progress (user_id, item_id, status, completed_at) 
                                  VALUES (:uid, :iid, 'completed', CURRENT_TIMESTAMP)", [
                    ':uid' => $userId,
                    ':iid' => $itemId
                ]);
            }
            
            // Legacy progress
            if ($lessonId) {
                $sqlComp = "INSERT OR IGNORE INTO completed_lessons (user_id, lesson_id) VALUES (:uid, :lid)";
                $this->db->query($sqlComp, [':uid' => $userId, ':lid' => $lessonId]);
            }
            
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
