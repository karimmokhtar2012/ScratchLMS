<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\Database;

class Authorization
{
    private static ?Database $db = null;

    private static function getDb(): Database
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }

    public static function isEnrolled(int $userId, int $courseId): bool
    {
        $db = self::getDb();
        $sql = "SELECT id FROM enrollments WHERE user_id = :uid AND course_id = :cid";
        $res = $db->fetchAll($sql, [':uid' => $userId, ':cid' => $courseId]);
        return !empty($res);
    }

    public static function isInstructor(int $userId, int $courseId): bool
    {
        $db = self::getDb();
        $sql = "SELECT id FROM courses WHERE id = :cid AND instructor_id = :uid";
        $res = $db->fetchAll($sql, [':uid' => $userId, ':cid' => $courseId]);
        
        if (!empty($res)) return true;

        // Check if admin or master_instructor
        // (Assuming roles are in session for performance, otherwise query DB)
        $roles = $_SESSION['user_roles'] ?? [];
        return in_array('admin', $roles) || in_array('master_instructor', $roles);
    }

    /**
     * @throws \Exception if not enrolled
     */
    public static function requireEnrollment(int $userId, int $courseId): void
    {
        if (!self::isEnrolled($userId, $courseId)) {
            // Check if instructor (instructors can view their own course content)
            if (self::isInstructor($userId, $courseId)) {
                return;
            }
            throw new \Exception("ACCESS_DENIED_NOT_ENROLLED");
        }
    }

    /**
     * @throws \Exception if not instructor
     */
    public static function requireInstructor(int $userId, int $courseId): void
    {
        if (!self::isInstructor($userId, $courseId)) {
            throw new \Exception("ACCESS_DENIED_NOT_INSTRUCTOR");
        }
    }
}
