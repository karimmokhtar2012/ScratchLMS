<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Policies\Authorization;

class CourseProgressService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function updateProgress(int $userId, int $courseId): void
    {
        // 0. Verify Enrollment Exists
        if (!Authorization::isEnrolled($userId, $courseId)) {
            return;
        }

        // Check if content_items table exists
        $tableCheck = $this->db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name='content_items'");
        $hasItems = !empty($tableCheck);

        if ($hasItems) {
            // 1. Count Total Content Items in Course
            $sqlTotal = "SELECT COUNT(*) as total FROM content_items WHERE course_id = :cid";
            $resTotal = $this->db->fetchAll($sqlTotal, [':cid' => $courseId]);
            $totalItems = $resTotal[0]['total'] ?? 0;

            // 2. Count Completed Items for User
            $sqlCompleted = "SELECT COUNT(*) as completed 
                             FROM content_progress cp
                             JOIN content_items ci ON cp.item_id = ci.id
                             WHERE cp.user_id = :uid AND ci.course_id = :cid AND cp.status = 'completed'";
            $resCompleted = $this->db->fetchAll($sqlCompleted, [':uid' => $userId, ':cid' => $courseId]);
            $completedCount = $resCompleted[0]['completed'] ?? 0;

            // Note: We might want to fall back to completed_lessons if content_progress is empty for some items
            // but for a clean "Wipe + Reseed" environment, this is perfect.
        } else {
            // Legacy Logic
            $sqlTotal = "SELECT COUNT(*) as total FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = :cid";
            $resTotal = $this->db->fetchAll($sqlTotal, [':cid' => $courseId]);
            $totalItems = $resTotal[0]['total'] ?? 0;

            $sqlCompleted = "SELECT COUNT(*) as completed FROM completed_lessons cl JOIN lessons l ON cl.lesson_id = l.id JOIN modules m ON l.module_id = m.id WHERE cl.user_id = :uid AND m.course_id = :cid";
            $resCompleted = $this->db->fetchAll($sqlCompleted, [':uid' => $userId, ':cid' => $courseId]);
            $completedCount = $resCompleted[0]['completed'] ?? 0;
        }

        // 3. Calculate Percentage
        $percent = $totalItems > 0 ? ($completedCount / $totalItems) * 100 : 0;
        $isCompleted = $percent >= 100 ? 1 : 0;

        // 4. Update Enrollment Record
        // Generate Certificate if newly completed and course supports it
        $certSql = "";
        $certParams = [];
        
        if ($isCompleted) {
            // Check Course Settings
            $course = $this->db->fetchAll("SELECT has_certificate, certificate_auto_issue FROM courses WHERE id = :cid", [':cid' => $courseId])[0] ?? null;
            
            if ($course && $course['has_certificate'] && $course['certificate_auto_issue']) {
                // Check if already certified to avoid overwriting
                $check = $this->db->fetchAll("SELECT certificate_id FROM enrollments WHERE user_id = :uid AND course_id = :cid", [':uid' => $userId, ':cid' => $courseId])[0];
                
                if (empty($check['certificate_id'])) {
                    $certId = 'CERT-' . strtoupper(uniqid());
                    $certSql = ", certificate_id = :certId, certified_at = CURRENT_TIMESTAMP";
                    $certParams = [':certId' => $certId];
                }
            }
        }

        $sqlUpdate = "UPDATE enrollments SET progress_percent = :pct, is_completed = :comp $certSql
                      WHERE user_id = :uid AND course_id = :cid";
        
        $params = array_merge([
            ':pct' => $percent,
            ':comp' => $isCompleted,
            ':uid' => $userId,
            ':cid' => $courseId
        ], $certParams);

        $this->db->query($sqlUpdate, $params);
    }

    public function isLessonLocked(int $userId, int $courseId, int $lessonId): bool
    {
        // For now, redirect to isItemLocked if we can find the item
        $item = $this->db->fetchAll("SELECT id FROM content_items WHERE item_type = 'lesson' AND item_id = :lid", [':lid' => $lessonId])[0] ?? null;
        if ($item) {
            return $this->isItemLocked($userId, $courseId, (int) $item['id']);
        }

        // Legacy fallback logic
        return $this->legacyIsLocked($userId, $courseId, $lessonId);
    }

    public function isItemLocked(int $userId, int $courseId, int $itemId): bool
    {
        // 1. Get Course Flow
        $course = $this->db->fetchAll("SELECT content_flow FROM courses WHERE id = :cid", [':cid' => $courseId])[0] ?? null;
        if (!$course || $course['content_flow'] === 'free') return false;

        // 2. Linear Flow
        if ($course['content_flow'] === 'linear') {
            $items = $this->db->fetchAll("SELECT id FROM content_items WHERE course_id = :cid ORDER BY position ASC", [':cid' => $courseId]);
            $itemIds = array_column($items, 'id');
            $idx = array_search($itemId, $itemIds);
            
            if ($idx === 0 || $idx === false) return false;
            
            $prevItemId = $itemIds[$idx - 1];
            $check = $this->db->fetchAll("SELECT id FROM content_progress WHERE user_id = :uid AND item_id = :iid AND status = 'completed'", [
                ':uid' => $userId,
                ':iid' => $prevItemId
            ]);
            return empty($check);
        }

        // 3. Drip rules (item-level)
        $rules = $this->db->fetchAll("SELECT * FROM content_item_rules WHERE item_id = :iid", [':iid' => $itemId]);
        if (empty($rules)) return false;

        // TODO: Implement complex rule evaluation
        return false;
    }

    private function legacyIsLocked(int $userId, int $courseId, int $lessonId): bool
    {
        // Original logic preserved
        $course = $this->db->fetchAll("SELECT content_flow FROM courses WHERE id = :cid", [':cid' => $courseId])[0] ?? null;
        if (!$course || $course['content_flow'] === 'free') return false;
        
        // ... (rest of old logic)

        // 2. Linear Flow Logic: Must complete previous lesson
        if ($course['content_flow'] === 'linear') {
            // Get all lessons in order
            $sqlLessons = "SELECT l.id 
                           FROM lessons l 
                           JOIN modules m ON l.module_id = m.id 
                           WHERE m.course_id = :cid 
                           ORDER BY m.sort_order ASC, l.sort_order ASC";
            $lessons = $this->db->fetchAll($sqlLessons, [':cid' => $courseId]);
            
            $lessonIds = array_column($lessons, 'id');
            $targetIndex = array_search($lessonId, $lessonIds);

            if ($targetIndex === false || $targetIndex === 0) {
                return false; // First lesson is always open
            }

            $prevLessonId = $lessonIds[$targetIndex - 1];

            // Check if previous lesson is completed
            $check = $this->db->fetchAll("SELECT lesson_id FROM completed_lessons WHERE user_id = :uid AND lesson_id = :lid", [
                ':uid' => $userId,
                ':lid' => $prevLessonId
            ]);

            return empty($check);
        }

        // 3. Drip logic (time/progress/combined)
        $rule = $this->db->fetchAll("SELECT unlock_after_days, unlock_after_lesson_id, unlock_after_percent FROM lesson_drip_rules WHERE lesson_id = :lid", [
            ':lid' => $lessonId
        ])[0] ?? null;

        if (!$rule) {
            return false;
        }

        $timeLocked = false;
        $progressLocked = false;

        if (in_array($course['content_flow'], ['drip_time', 'drip_combined'], true)) {
            if (!empty($rule['unlock_after_days'])) {
                $enroll = $this->db->fetchAll("SELECT created_at FROM enrollments WHERE user_id = :uid AND course_id = :cid", [
                    ':uid' => $userId,
                    ':cid' => $courseId
                ])[0] ?? null;
                if ($enroll) {
                    $unlockAt = strtotime($enroll['created_at']) + ((int) $rule['unlock_after_days'] * 86400);
                    $timeLocked = time() < $unlockAt;
                }
            }
        }

        if (in_array($course['content_flow'], ['drip_progress', 'drip_combined'], true)) {
            if (!empty($rule['unlock_after_lesson_id'])) {
                $check = $this->db->fetchAll("SELECT lesson_id FROM completed_lessons WHERE user_id = :uid AND lesson_id = :lid", [
                    ':uid' => $userId,
                    ':lid' => (int) $rule['unlock_after_lesson_id']
                ]);
                $progressLocked = empty($check);
            }
            if (!empty($rule['unlock_after_percent'])) {
                $total = $this->db->fetchAll("SELECT COUNT(*) as total FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = :cid", [
                    ':cid' => $courseId
                ])[0]['total'] ?? 0;
                $completed = $this->db->fetchAll("SELECT COUNT(*) as completed FROM completed_lessons cl JOIN lessons l ON cl.lesson_id = l.id JOIN modules m ON l.module_id = m.id WHERE cl.user_id = :uid AND m.course_id = :cid", [
                    ':uid' => $userId,
                    ':cid' => $courseId
                ])[0]['completed'] ?? 0;
                $percent = $total > 0 ? ($completed / $total) * 100 : 0;
                if ($percent < (int) $rule['unlock_after_percent']) {
                    $progressLocked = true;
                }
            }
        }

        if ($course['content_flow'] === 'drip_time') {
            return $timeLocked;
        }
        if ($course['content_flow'] === 'drip_progress') {
            return $progressLocked;
        }

        return $timeLocked || $progressLocked;
    }
}
