<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Policies\Authorization;
use App\Services\ContentRuleService;

class CourseProgressService
{
    private Database $db;
    private ContentRuleService $ruleService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ruleService = new ContentRuleService();
    }

    public function updateProgress(int $userId, int $courseId): void
    {
        // 0. Verify Enrollment Exists
        if (!Authorization::isEnrolled($userId, $courseId)) {
            return;
        }

        $percent = $this->calculateProgressPercent($userId, $courseId);
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
        if (!$this->hasTable('content_items')) {
            return $this->legacyIsLocked($userId, $courseId, $lessonId);
        }

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
        if (!$this->hasTable('content_items')) {
            return false;
        }

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
        $rules = [];
        if ($this->hasTable('content_item_rules')) {
            $rules = $this->db->fetchAll("SELECT * FROM content_item_rules WHERE item_id = :iid", [':iid' => $itemId]);
        }

        // Fallback: If no item rules, check legacy drip rules if this item is a lesson
        if (empty($rules)) {
            $item = $this->db->fetchAll("SELECT item_type, item_id FROM content_items WHERE id = :id", [':id' => $itemId])[0] ?? null;
            if ($item && $item['item_type'] === 'lesson') {
                return $this->legacyIsLocked($userId, $courseId, (int)$item['item_id']);
            }
            return false;
        }

        $hasTimeRule = false;
        $timeLocked = false;
        $hasProgressRule = false;
        $progressLocked = false;

        foreach ($rules as $rule) {
            [$isValid, $errors] = $this->ruleService->validateRuleRecord($rule);
            if (!$isValid) {
                // Fail-closed: invalid rule definitions should not unlock content.
                return true;
            }

            $params = json_decode($rule['params_json'], true);
            $ruleType = $rule['rule_type'];

            if ($ruleType === 'unlock_after_days') {
                $hasTimeRule = true;
                $days = (int) ($params['days'] ?? 0);
                $enroll = $this->db->fetchAll("SELECT created_at FROM enrollments WHERE user_id = :uid AND course_id = :cid", [
                    ':uid' => $userId,
                    ':cid' => $courseId
                ])[0] ?? null;
                if (!$enroll) {
                    $timeLocked = true;
                    continue;
                }
                $unlockAt = strtotime($enroll['created_at']) + ($days * 86400);
                if (time() < $unlockAt) {
                    $timeLocked = true;
                }
            }

            if ($ruleType === 'unlock_after_item_completion') {
                $hasProgressRule = true;
                $unlockItemId = (int) ($params['item_id'] ?? 0);
                if ($unlockItemId <= 0) {
                    $progressLocked = true;
                    continue;
                }
                $check = $this->db->fetchAll("SELECT id FROM content_progress WHERE user_id = :uid AND item_id = :iid AND status = 'completed'", [
                    ':uid' => $userId,
                    ':iid' => $unlockItemId
                ]);
                if (empty($check)) {
                    $progressLocked = true;
                }
            }

            if ($ruleType === 'unlock_after_percent') {
                $hasProgressRule = true;
                $threshold = (int) ($params['percent'] ?? 0);
                $percent = $this->calculateProgressPercent($userId, $courseId);
                if ($percent < $threshold) {
                    $progressLocked = true;
                }
            }
        }

        if ($course['content_flow'] === 'drip_time') {
            return $hasTimeRule ? $timeLocked : false;
        }

        if ($course['content_flow'] === 'drip_progress') {
            return $hasProgressRule ? $progressLocked : false;
        }

        if ($course['content_flow'] === 'drip_combined') {
            $timeSatisfied = !$hasTimeRule || !$timeLocked;
            $progressSatisfied = !$hasProgressRule || !$progressLocked;
            return !($timeSatisfied && $progressSatisfied);
        }

        return false;
    }

    public function calculateProgressPercent(int $userId, int $courseId): float
    {
        if ($this->hasTable('content_items') && $this->hasTable('content_progress')) {
            $sqlTotal = "SELECT COUNT(*) as total FROM content_items WHERE course_id = :cid";
            $resTotal = $this->db->fetchAll($sqlTotal, [':cid' => $courseId]);
            $totalItems = (int) ($resTotal[0]['total'] ?? 0);

            $sqlCompleted = "SELECT COUNT(DISTINCT cp.item_id) as completed
                             FROM content_progress cp
                             JOIN content_items ci ON cp.item_id = ci.id
                             WHERE cp.user_id = :uid AND ci.course_id = :cid AND cp.status = 'completed'";
            $resCompleted = $this->db->fetchAll($sqlCompleted, [':uid' => $userId, ':cid' => $courseId]);
            $completedCount = (int) ($resCompleted[0]['completed'] ?? 0);
            return $totalItems > 0 ? ($completedCount / $totalItems) * 100 : 0.0;
        }

        $sqlTotal = "SELECT COUNT(*) as total FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = :cid";
        $resTotal = $this->db->fetchAll($sqlTotal, [':cid' => $courseId]);
        $totalItems = (int) ($resTotal[0]['total'] ?? 0);

        $sqlCompleted = "SELECT COUNT(*) as completed FROM completed_lessons cl JOIN lessons l ON cl.lesson_id = l.id JOIN modules m ON l.module_id = m.id WHERE cl.user_id = :uid AND m.course_id = :cid";
        $resCompleted = $this->db->fetchAll($sqlCompleted, [':uid' => $userId, ':cid' => $courseId]);
        $completedCount = (int) ($resCompleted[0]['completed'] ?? 0);

        return $totalItems > 0 ? ($completedCount / $totalItems) * 100 : 0.0;
    }

    private function hasTable(string $table): bool
    {
        $driver = $this->db->getConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $tableCheck = $this->db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name = :table", [
                ':table' => $table
            ]);
        } elseif ($driver === 'pgsql') {
            // PostgreSQL uses current_schema() instead of DATABASE()
            $tableCheck = $this->db->fetchAll("SELECT table_name FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = :table", [
                ':table' => $table
            ]);
        } else {
            // MySQL
            $tableCheck = $this->db->fetchAll("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table", [
                ':table' => $table
            ]);
        }

        return !empty($tableCheck);
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

        // drip_combined: Lesson unlocks only when BOTH conditions are satisfied
        // If a rule type has no rule defined, it's considered satisfied
        $hasTimeRule = !empty($rule['unlock_after_days']);
        $hasProgressRule = !empty($rule['unlock_after_lesson_id']) || !empty($rule['unlock_after_percent']);

        $timeSatisfied = !$hasTimeRule || !$timeLocked;
        $progressSatisfied = !$hasProgressRule || !$progressLocked;

        return !($timeSatisfied && $progressSatisfied);
    }
}
