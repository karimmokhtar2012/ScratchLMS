<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Http\Middleware\AuthMiddleware;
use App\Core\Database;

class DashboardController extends Controller
{
    private Database $db;

    public function __construct()
    {
        (new AuthMiddleware())->handle();
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $userId = $_SESSION['user_id'];

        // Fetch Fresh Roles & Permissions
        $db = \App\Core\Database::getInstance();
        $sqlRoles = "SELECT r.slug FROM roles r 
                     JOIN role_user ru ON r.id = ru.role_id 
                     WHERE ru.user_id = :uid";
        $rolesRes = $db->fetchAll($sqlRoles, [':uid' => $userId]);
        $roles = array_column($rolesRes, 'slug');
        $_SESSION['user_roles'] = $roles; // Update session

        // Fetch Fresh Permission
        $userPerm = $db->fetchAll("SELECT can_switch_role FROM users WHERE id = :uid", [':uid' => $userId])[0] ?? [];
        $canSwitchDb = (bool) ($userPerm['can_switch_role'] ?? 0);
        $_SESSION['can_switch_role'] = $canSwitchDb;

        $isAdmin = in_array('admin', $roles);
        $isMaster = in_array('master_instructor', $roles);
        $isInstructor = in_array('instructor', $roles) || $isMaster || $isAdmin;
        $isStudent = in_array('student', $roles) || $isAdmin; // Admin can view as student


        // Check if strictly Viewer (no other roles)
        $isViewerOnly = in_array('viewer', $roles) && !$isStudent && !$isInstructor;

        if ($isViewerOnly) {
            $_SESSION['flash_error'] = 'You must enroll in a course to access the dashboard.';
            header('Location: /courses');
            exit;
        }

        $canSwitch = $isAdmin || ($_SESSION['can_switch_role'] ?? 0);

        // Determine Mode
        $defaultMode = $isInstructor ? 'instructor' : 'student';
        $mode = $_SESSION['dashboard_mode'] ?? $defaultMode;

        // Enforce Mode Permissions
        if ($isInstructor) {
            // If Instructor, check if they are allowed to be in Student mode
            if ($mode === 'student' && !$canSwitch) {
                $mode = 'instructor';
            }
        } else {
            // If not instructor, must be student (or viewer handled above)
            $mode = 'student';
        }

        // Save validated mode back to session to keep state consistent
        $_SESSION['dashboard_mode'] = $mode;

        $data = [
            'user_name' => $_SESSION['user_name'] ?? 'Guest',
            'roles' => $roles,
            'mode' => $mode,
            'is_instructor' => $isInstructor,
            'can_switch' => $canSwitch
        ];

        if ($mode === 'student') {
            // --- STUDENT DATA ---
            $data['stats'] = [
                'active_courses' => $this->countEnrollments($userId),
                'completed_courses' => $this->countCompletedEnrollments($userId),
                'certificates' => $this->countCertificates($userId),
                'hours_learned' => $this->calculateHoursLearned($userId),
            ];
            $data['recent_activity'] = $this->getRecentActivity($userId);
            $data['enrolled_courses'] = $this->getEnrolledCourses($userId);

            // Last Action for Resume
            $data['last_action'] = !empty($data['recent_activity']) ? $data['recent_activity'][0] : null;
        } else {
            // --- INSTRUCTOR DATA ---
            $data['created_courses'] = $this->getCreatedCourses($userId);
            $data['notifications'] = $this->getPendingRequests($userId);
            $data['pending_certificate_approvals'] = $this->countPendingCertificateApprovals($userId);

            // Calculate Instructor Stats
            $db = \App\Core\Database::getInstance();
            // Total Students across all my courses
            $sqlStudents = "SELECT COUNT(DISTINCT user_id) as count FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE c.instructor_id = :uid";
            $totalStudents = $db->fetchAll($sqlStudents, [':uid' => $userId])[0]['count'] ?? 0;

            // Total Content Items
            $sqlItems = "SELECT COUNT(*) as count FROM lessons l JOIN modules m ON l.module_id = m.id JOIN courses c ON m.course_id = c.id WHERE c.instructor_id = :uid";
            $totalItems = $db->fetchAll($sqlItems, [':uid' => $userId])[0]['count'] ?? 0;

            $data['stats'] = [
                'total_students' => $totalStudents,
                'courses_created' => count($data['created_courses']),
                'pending_requests' => count($data['notifications']),
                'total_content' => $totalItems
            ];
        }

        // Check for tutorial
        $sqlTut = "SELECT seen_tutorial FROM users WHERE id = :uid";
        $hasSeen = $this->db->fetchAll($sqlTut, [':uid' => $userId])[0]['seen_tutorial'] ?? 0;
        $data['show_tutorial'] = !$hasSeen;

        $this->view('dashboard', $data);
    }

    public function dismissTutorial(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) return;

        $this->db->query("UPDATE users SET seen_tutorial = true WHERE id = :uid", [':uid' => $userId]);
        echo json_encode(['success' => true]);
        exit;
    }

    public function switchMode(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $roles = $_SESSION['user_roles'] ?? [];
        $isAdmin = in_array('admin', $roles);
        $canSwitch = $isAdmin || ($_SESSION['can_switch_role'] ?? 0);

        if (!$canSwitch) {
            header('Location: /dashboard');
            exit;
        }

        $current = $_SESSION['dashboard_mode'] ?? 'student';
        $_SESSION['dashboard_mode'] = ($current === 'student') ? 'instructor' : 'student';

        header('Location: /dashboard');
        exit;
    }

    private function isInstructor(int $userId): bool
    {
        $roles = $_SESSION['user_roles'] ?? [];
        return in_array('instructor', $roles) || in_array('admin', $roles);
    }

    private function getPendingRequests(int $userId): array
    {
        // Get requests for quizzes in courses owned by this instructor
        $sql = "SELECT ar.*, u.name as student_name, l.title as quiz_title, c.id as course_id
                FROM attempt_requests ar
                JOIN users u ON ar.user_id = u.id
                JOIN quizzes q ON ar.quiz_id = q.id
                JOIN lessons l ON q.lesson_id = l.id
                JOIN modules m ON l.module_id = m.id
                JOIN courses c ON m.course_id = c.id
                WHERE c.instructor_id = :uid AND ar.status = 'pending'
                ORDER BY ar.created_at DESC";
        return $this->db->fetchAll($sql, [':uid' => $userId]);
    }

    private function countEnrollments(int $userId): int
    {
        $sql = "SELECT COUNT(*) as count FROM enrollments WHERE user_id = :uid AND is_completed = false";
        $res = $this->db->fetchAll($sql, [':uid' => $userId]);
        return $res[0]['count'] ?? 0;
    }

    private function countCompletedEnrollments(int $userId): int
    {
        $sql = "SELECT COUNT(*) as count FROM enrollments WHERE user_id = :uid AND is_completed = true";
        $res = $this->db->fetchAll($sql, [':uid' => $userId]);
        return $res[0]['count'] ?? 0;
    }

    private function countCertificates(int $userId): int
    {
        $sql = "SELECT COUNT(*) as count FROM enrollments WHERE user_id = :uid AND certificate_id IS NOT NULL";
        $res = $this->db->fetchAll($sql, [':uid' => $userId]);
        return $res[0]['count'] ?? 0;
    }

    private function countPendingCertificateApprovals(int $userId): int
    {
        $sql = "SELECT COUNT(*) as count
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                WHERE c.instructor_id = :uid
                  AND c.has_certificate = true
                  AND c.certificate_auto_issue = false
                  AND e.is_completed = true
                  AND e.certificate_id IS NULL";
        $res = $this->db->fetchAll($sql, [':uid' => $userId]);
        return (int) ($res[0]['count'] ?? 0);
    }

    private function calculateHoursLearned(int $userId): int
    {
        // Sum duration of completed video lessons
        $sql = "SELECT SUM(l.duration) as seconds 
                FROM completed_lessons cl
                JOIN lessons l ON cl.lesson_id = l.id
                WHERE cl.user_id = :uid";
        $res = $this->db->fetchAll($sql, [':uid' => $userId]);
        return (int) floor(($res[0]['seconds'] ?? 0) / 3600);
    }

    private function getRecentActivity(int $userId): array
    {
        $sql = "SELECT xl.verb, xl.created_at, l.title as lesson_title, xl.lesson_id, c.title as course_title, c.id as course_id
                FROM xapi_logs xl
                JOIN lessons l ON xl.lesson_id = l.id
                JOIN modules m ON l.module_id = m.id
                JOIN courses c ON m.course_id = c.id
                WHERE xl.user_id = :uid
                ORDER BY xl.created_at DESC
                LIMIT 5";
        return $this->db->fetchAll($sql, [':uid' => $userId]);
    }

    private function getEnrolledCourses(int $userId): array
    {
        $sql = "SELECT c.*, e.progress_percent 
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                WHERE e.user_id = :uid
                ORDER BY e.created_at DESC";
        return $this->db->fetchAll($sql, [':uid' => $userId]);
    }

    private function getCreatedCourses(int $userId): array
    {
        $sql = "SELECT * FROM courses WHERE instructor_id = :uid ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [':uid' => $userId]);
    }
}
