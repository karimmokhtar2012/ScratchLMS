<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Domain\Users\User;
use App\Domain\Courses\Course;
use App\Http\Middleware\AuthMiddleware;
use App\Core\Database;

class AdminController extends Controller
{
    private Database $db;

    public function __construct()
    {
        (new AuthMiddleware())->handle(['admin']);
        $this->db = Database::getInstance();
    }

    /**
     * Admin Dashboard Overview
     */
    public function index(): void
    {
        // 1. User Stats
        $totalUsers = $this->db->fetchAll("SELECT COUNT(*) as count FROM users")[0]['count'];
        $newUsers = $this->db->fetchAll("SELECT COUNT(*) as count FROM users WHERE created_at > date('now', '-7 days')")[0]['count'];
        
        // 2. Course Stats
        $totalCourses = $this->db->fetchAll("SELECT COUNT(*) as count FROM courses")[0]['count'];
        $publishedCourses = $this->db->fetchAll("SELECT COUNT(*) as count FROM courses WHERE status = 'published'")[0]['count'];
        
        // 3. Financials
        $totalRevenue = $this->db->fetchAll("SELECT SUM(total_amount) as total FROM orders WHERE status = 'paid'")[0]['total'] ?? 0;

        $stats = [
            'total_users' => $totalUsers,
            'new_users' => $newUsers,
            'total_courses' => $totalCourses,
            'published_courses' => $publishedCourses,
            'total_revenue' => $totalRevenue
        ];

        // Recent Signups
        $recentUsers = $this->db->fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

        $this->view('admin.index', ['stats' => $stats, 'recent_users' => $recentUsers]);
    }

    /**
     * User Management Page
     */
    public function users(): void
    {
        $userModel = new User();
        // Fetch all users with their roles concatenated
        $sql = "SELECT u.*, GROUP_CONCAT(r.name) as role_names, GROUP_CONCAT(r.slug) as role_slugs
                FROM users u
                LEFT JOIN role_user ru ON u.id = ru.user_id
                LEFT JOIN roles r ON ru.role_id = r.id
                GROUP BY u.id
                ORDER BY u.created_at DESC";
        
        $users = $this->db->fetchAll($sql);

        // Fetch all available roles for the dropdown
        $roles = $this->db->fetchAll("SELECT * FROM roles ORDER BY id ASC");

        $courses = $this->db->fetchAll("SELECT id, title FROM courses ORDER BY title ASC");

        $this->view('admin.users', [
            'users' => $users,
            'roles' => $roles,
            'courses' => $courses
        ]);
    }

    /**
     * Courses Management Page
     */
    public function courses(): void
    {
        $sql = "SELECT c.*, u.name as instructor_name, 
                (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) as students_count
                FROM courses c
                LEFT JOIN users u ON c.instructor_id = u.id
                ORDER BY c.created_at DESC";
        
        $courses = $this->db->fetchAll($sql);

        $instructors = $this->db->fetchAll("SELECT u.id, u.name
                                            FROM users u
                                            JOIN role_user ru ON u.id = ru.user_id
                                            JOIN roles r ON ru.role_id = r.id
                                            WHERE r.slug IN ('instructor', 'master_instructor', 'admin')
                                            GROUP BY u.id
                                            ORDER BY u.name ASC");

        $this->view('admin.courses', [
            'courses' => $courses,
            'instructors' => $instructors
        ]);
    }

    /**
     * Admin Delete Course
     */
    public function deleteCourse(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $courseId = (int) $id;
        $this->db->query("DELETE FROM courses WHERE id = :id", [':id' => $courseId]);
        
        $_SESSION['admin_flash_success'] = 'Course deleted by admin.';
        header('Location: /admin/courses');
        exit;
    }

    /**
     * AJAX: Update User Role
     * For simplicity, this acts as a "Set Primary Role" logic. 
     * It clears existing roles and sets the new one.
     */
    public function updateUserRole(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $userId = (int) $_POST['user_id'];
        $roleSlug = $_POST['role_slug'];

        // Security: Prevent editing self (to avoid locking out admin)
        if ($userId == $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot change your own role']);
            exit;
        }

        // Get Role ID
        $role = $this->db->fetchAll("SELECT id FROM roles WHERE slug = :slug", [':slug' => $roleSlug])[0] ?? null;
        if (!$role) {
            http_response_code(400);
            exit;
        }

        $this->db->getConnection()->beginTransaction();
        try {
            // Remove old roles
            $this->db->query("DELETE FROM role_user WHERE user_id = :uid", [':uid' => $userId]);
            
            // Add new role
            $this->db->query("INSERT INTO role_user (user_id, role_id) VALUES (:uid, :rid)", [
                ':uid' => $userId,
                ':rid' => $role['id']
            ]);
            
            $this->db->getConnection()->commit();
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            $this->db->getConnection()->rollBack();
            http_response_code(500);
        }
    }

    /**
     * Delete User
     */
    public function deleteUser(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $userId = (int) $id;

        // Security: Prevent deleting self
        if ($userId == $_SESSION['user_id']) {
            $_SESSION['admin_flash_error'] = 'You cannot delete your own account.';
            header('Location: /admin/users');
            exit;
        }

        $this->db->query("DELETE FROM users WHERE id = :id", [':id' => $userId]);
        
        $_SESSION['admin_flash_success'] = 'User permanently deleted.';
        header('Location: /admin/users');
        exit;
    }

    /**
     * AJAX: Get User Enrollments & Available Courses
     */
    public function getUserEnrollments(string $id): void
    {
        $userId = (int) $id;

        // 1. Get Enrolled Courses
        $sqlEnrolled = "SELECT c.id, c.title, e.progress_percent, e.created_at as enrolled_at
                        FROM enrollments e
                        JOIN courses c ON e.course_id = c.id
                        WHERE e.user_id = :uid
                        ORDER BY e.created_at DESC";
        $enrolled = $this->db->fetchAll($sqlEnrolled, [':uid' => $userId]);

        // 2. Get Available Courses (Not Enrolled)
        $sqlAvailable = "SELECT id, title FROM courses 
                         WHERE id NOT IN (SELECT course_id FROM enrollments WHERE user_id = :uid)
                         AND status IN ('published', 'upcoming')
                         ORDER BY title ASC";
        $available = $this->db->fetchAll($sqlAvailable, [':uid' => $userId]);

        header('Content-Type: application/json');
        echo json_encode([
            'enrolled' => $enrolled,
            'available' => $available
        ]);
        exit;
    }

    /**
     * AJAX: Enroll User
     */
    public function enrollUser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $userId = (int) $_POST['user_id'];
        $courseId = (int) $_POST['course_id'];

        $this->db->query("INSERT OR IGNORE INTO enrollments (user_id, course_id) VALUES (:uid, :cid)", [
            ':uid' => $userId,
            ':cid' => $courseId
        ]);

        echo json_encode(['status' => 'success']);
        exit;
    }

    /**
     * AJAX: Unenroll User
     */
    public function unenrollUser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $userId = (int) $_POST['user_id'];
        $courseId = (int) $_POST['course_id'];

        $this->db->query("DELETE FROM enrollments WHERE user_id = :uid AND course_id = :cid", [
            ':uid' => $userId,
            ':cid' => $courseId
        ]);

        echo json_encode(['status' => 'success']);
        exit;
    }

    /**
     * AJAX: Reset Course Progress
     */
    public function resetProgress(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $userId = (int) $_POST['user_id'];
        $courseId = (int) $_POST['course_id'];

        $this->db->getConnection()->beginTransaction();
        try {
            // 1. Reset Enrollment Record
            $this->db->query("UPDATE enrollments SET progress_percent = 0, is_completed = 0, certificate_id = NULL, certified_at = NULL WHERE user_id = :uid AND course_id = :cid", [
                ':uid' => $userId,
                ':cid' => $courseId
            ]);

            // 2. Delete Completed Lessons Log
            // Need to find lessons belonging to this course
            $sqlLessons = "DELETE FROM completed_lessons 
                           WHERE user_id = :uid 
                           AND lesson_id IN (
                               SELECT l.id FROM lessons l 
                               JOIN modules m ON l.module_id = m.id 
                               WHERE m.course_id = :cid
                           )";
            $this->db->query($sqlLessons, [':uid' => $userId, ':cid' => $courseId]);

            // 3. Delete Quiz Attempts
            $sqlAttempts = "DELETE FROM quiz_attempts 
                            WHERE user_id = :uid 
                            AND quiz_id IN (
                                SELECT q.id FROM quizzes q
                                JOIN lessons l ON q.lesson_id = l.id
                                JOIN modules m ON l.module_id = m.id
                                WHERE m.course_id = :cid
                            )";
            $this->db->query($sqlAttempts, [':uid' => $userId, ':cid' => $courseId]);

            $this->db->getConnection()->commit();
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            $this->db->getConnection()->rollBack();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    public function bulkDeleteUsers(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $ids = array_map('intval', $input['user_ids'] ?? []);
        $ids = array_values(array_filter($ids));

        if (empty($ids)) {
            http_response_code(400);
            echo json_encode(['error' => 'No users selected']);
            exit;
        }

        if (in_array($_SESSION['user_id'], $ids, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete your own account']);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $this->db->query("DELETE FROM users WHERE id IN ($placeholders)", $ids);
        echo json_encode(['status' => 'success']);
        exit;
    }

    public function bulkUpdateRole(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $ids = array_map('intval', $input['user_ids'] ?? []);
        $ids = array_values(array_filter($ids));
        $roleSlug = $input['role_slug'] ?? '';

        if (empty($ids) || !$roleSlug) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            exit;
        }

        if (in_array($_SESSION['user_id'], $ids, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot change your own role']);
            exit;
        }

        $role = $this->db->fetchAll("SELECT id FROM roles WHERE slug = :slug", [':slug' => $roleSlug])[0] ?? null;
        if (!$role) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid role']);
            exit;
        }

        $this->db->getConnection()->beginTransaction();
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $this->db->query("DELETE FROM role_user WHERE user_id IN ($placeholders)", $ids);
            foreach ($ids as $uid) {
                $this->db->query("INSERT INTO role_user (user_id, role_id) VALUES (:uid, :rid)", [
                    ':uid' => $uid,
                    ':rid' => $role['id']
                ]);
            }
            $this->db->getConnection()->commit();
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            $this->db->getConnection()->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Bulk update failed']);
        }
        exit;
    }

    public function bulkToggleSwitch(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $ids = array_map('intval', $input['user_ids'] ?? []);
        $ids = array_values(array_filter($ids));
        $canSwitch = (int) ($input['can_switch'] ?? 0);

        if (empty($ids)) {
            http_response_code(400);
            echo json_encode(['error' => 'No users selected']);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$canSwitch], $ids);
        $this->db->query("UPDATE users SET can_switch_role = ? WHERE id IN ($placeholders)", $params);
        echo json_encode(['status' => 'success']);
        exit;
    }

    public function bulkEnroll(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $ids = array_map('intval', $input['user_ids'] ?? []);
        $ids = array_values(array_filter($ids));
        $courseId = (int) ($input['course_id'] ?? 0);

        if (empty($ids) || !$courseId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            exit;
        }

        foreach ($ids as $uid) {
            $this->db->query("INSERT OR IGNORE INTO enrollments (user_id, course_id) VALUES (:uid, :cid)", [
                ':uid' => $uid,
                ':cid' => $courseId
            ]);
        }

        echo json_encode(['status' => 'success']);
        exit;
    }

    public function bulkUnenroll(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $ids = array_map('intval', $input['user_ids'] ?? []);
        $ids = array_values(array_filter($ids));
        $courseId = (int) ($input['course_id'] ?? 0);

        if (empty($ids) || !$courseId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            exit;
        }

        foreach ($ids as $uid) {
            $this->db->query("DELETE FROM enrollments WHERE user_id = :uid AND course_id = :cid", [
                ':uid' => $uid,
                ':cid' => $courseId
            ]);
        }

        echo json_encode(['status' => 'success']);
        exit;
    }

    public function bulkResetProgress(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $ids = array_map('intval', $input['user_ids'] ?? []);
        $ids = array_values(array_filter($ids));
        $courseId = (int) ($input['course_id'] ?? 0);

        if (empty($ids) || !$courseId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            exit;
        }

        $this->db->getConnection()->beginTransaction();
        try {
            foreach ($ids as $uid) {
                $this->db->query("UPDATE enrollments SET progress_percent = 0, is_completed = 0, certificate_id = NULL, certified_at = NULL WHERE user_id = :uid AND course_id = :cid", [
                    ':uid' => $uid,
                    ':cid' => $courseId
                ]);

                $sqlLessons = "DELETE FROM completed_lessons 
                               WHERE user_id = :uid 
                               AND lesson_id IN (
                                   SELECT l.id FROM lessons l 
                                   JOIN modules m ON l.module_id = m.id 
                                   WHERE m.course_id = :cid
                               )";
                $this->db->query($sqlLessons, [':uid' => $uid, ':cid' => $courseId]);

                $sqlAttempts = "DELETE FROM quiz_attempts 
                                WHERE user_id = :uid 
                                AND quiz_id IN (
                                    SELECT q.id FROM quizzes q
                                    JOIN lessons l ON q.lesson_id = l.id
                                    JOIN modules m ON l.module_id = m.id
                                    WHERE m.course_id = :cid
                                )";
                $this->db->query($sqlAttempts, [':uid' => $uid, ':cid' => $courseId]);
            }
            $this->db->getConnection()->commit();
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            $this->db->getConnection()->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Bulk reset failed']);
        }
        exit;
    }

    public function bulkDeleteCourses(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $ids = array_map('intval', $input['course_ids'] ?? []);
        $ids = array_values(array_filter($ids));

        if (empty($ids)) {
            http_response_code(400);
            echo json_encode(['error' => 'No courses selected']);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $this->db->query("DELETE FROM courses WHERE id IN ($placeholders)", $ids);
        echo json_encode(['status' => 'success']);
        exit;
    }

    public function bulkPublishCourses(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $ids = array_map('intval', $input['course_ids'] ?? []);
        $ids = array_values(array_filter($ids));

        if (empty($ids)) {
            http_response_code(400);
            echo json_encode(['error' => 'No courses selected']);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $this->db->query("UPDATE courses SET status = 'published', published_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)", $ids);
        echo json_encode(['status' => 'success']);
        exit;
    }

    public function bulkUnpublishCourses(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $ids = array_map('intval', $input['course_ids'] ?? []);
        $ids = array_values(array_filter($ids));

        if (empty($ids)) {
            http_response_code(400);
            echo json_encode(['error' => 'No courses selected']);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $this->db->query("UPDATE courses SET status = 'draft' WHERE id IN ($placeholders)", $ids);
        echo json_encode(['status' => 'success']);
        exit;
    }

    public function bulkSetCoursePrice(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $ids = array_map('intval', $input['course_ids'] ?? []);
        $ids = array_values(array_filter($ids));
        $price = isset($input['price']) ? (float) $input['price'] : null;

        if (empty($ids) || $price === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$price], $ids);
        $this->db->query("UPDATE courses SET price = ? WHERE id IN ($placeholders)", $params);
        echo json_encode(['status' => 'success']);
        exit;
    }

    public function bulkSetCourseInstructor(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $ids = array_map('intval', $input['course_ids'] ?? []);
        $ids = array_values(array_filter($ids));
        $instructorId = (int) ($input['instructor_id'] ?? 0);

        if (empty($ids) || !$instructorId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$instructorId], $ids);
        $this->db->query("UPDATE courses SET instructor_id = ? WHERE id IN ($placeholders)", $params);
        echo json_encode(['status' => 'success']);
        exit;
    }
}
