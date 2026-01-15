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
            // Enrollment record must exist to track progress. 
            // We do not auto-create enrollments here to enforce strict commerce/access rules.
            return;
        }

        // 1. Count Total Lessons in Course
        $sqlTotal = "SELECT COUNT(*) as total 
                     FROM lessons l 
                     JOIN modules m ON l.module_id = m.id 
                     WHERE m.course_id = :cid";
        $resTotal = $this->db->fetchAll($sqlTotal, [':cid' => $courseId]);
        $totalLessons = $resTotal[0]['total'] ?? 0;

        // 2. Count Completed Lessons for User in this Course
        $sqlCompleted = "SELECT COUNT(*) as completed 
                         FROM completed_lessons cl
                         JOIN lessons l ON cl.lesson_id = l.id
                         JOIN modules m ON l.module_id = m.id
                         WHERE cl.user_id = :uid AND m.course_id = :cid";
        $resCompleted = $this->db->fetchAll($sqlCompleted, [':uid' => $userId, ':cid' => $courseId]);
        $completedCount = $resCompleted[0]['completed'] ?? 0;

        // 3. Calculate Percentage
        $percent = $totalLessons > 0 ? ($completedCount / $totalLessons) * 100 : 0;
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
        // 1. Get Course Flow Setting
        $course = $this->db->fetchAll("SELECT content_flow FROM courses WHERE id = :cid", [':cid' => $courseId])[0] ?? null;
        
        if (!$course || $course['content_flow'] === 'free') {
            return false;
        }

        // 2. Linear Flow Logic: Must complete previous lesson
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
}
