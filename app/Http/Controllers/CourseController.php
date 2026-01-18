<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Domain\Courses\Course;
use App\Domain\Courses\Module;
use App\Core\Database;

class CourseController extends Controller
{
    private Database $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        // 1. Capture Filters
        $search = trim($_GET['q'] ?? '');
        $priceFilter = $_GET['price'] ?? 'all'; // all, free, paid
        $sort = $_GET['sort'] ?? 'newest'; // newest, popular, price_asc, price_desc
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        // 2. Build Query
        $params = [];
        $whereClauses = ["c.status IN ('published', 'upcoming')"];

        // Search
        if ($search) {
            $whereClauses[] = "(c.title LIKE :q OR c.description LIKE :q)";
            $params[':q'] = "%$search%";
        }

        // Price Filter
        if ($priceFilter === 'free') {
            $whereClauses[] = "c.price = 0";
        } elseif ($priceFilter === 'paid') {
            $whereClauses[] = "c.price > 0";
        }

        // Enroll Status & User ID
        $userId = $_SESSION['user_id'] ?? 0;
        $enrollSelect = "";
        if ($userId) {
            $enrollSelect = ", (SELECT COUNT(*) FROM enrollments en WHERE en.course_id = c.id AND en.user_id = :uid) as is_enrolled";
            $params[':uid'] = $userId;
        }

        // 3. Sorting
        $orderBy = match ($sort) {
            'popular' => 'students_count DESC',
            'price_asc' => 'c.price ASC',
            'price_desc' => 'c.price DESC',
            default => 'c.created_at DESC'
        };

        $whereSql = implode(' AND ', $whereClauses);

        // 4. Fetch Data
        $sql = "SELECT c.*, u.name as instructor_name, 
                (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) as students_count,
                (SELECT COUNT(*) FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = c.id) as lessons_count
                $enrollSelect
                FROM courses c 
                JOIN users u ON c.instructor_id = u.id 
                WHERE $whereSql
                ORDER BY $orderBy
                LIMIT :limit OFFSET :offset";
        
        // Add pagination params
        $queryParams = $params; // Copy for query execution
        $queryParams[':limit'] = $limit;
        $queryParams[':offset'] = $offset;

        // Execute Main Query
        $courses = $this->db->fetchAll($sql, $queryParams);

        // 5. Pagination Count
        // Count total results for pagination
        $countSql = "SELECT COUNT(*) as total FROM courses c WHERE $whereSql";
        // Remove :uid from count params if not used in where clause (it is not used in where clause, but passed in main query params)
        // We need to clean params for count query if :uid is in it but not used? 
        // PDO is strict. :uid is only used in SELECT clause. 
        // So for the count query, we must NOT include :uid if it's not in WHERE.
        // But $params includes :uid. 
        // Filter params for count query: only :q if search exists.
        $countParams = [];
        if ($search) $countParams[':q'] = "%$search%";
        
        $totalRes = $this->db->fetchAll($countSql, $countParams);
        $totalCourses = $totalRes[0]['total'] ?? 0;
        $totalPages = ceil($totalCourses / $limit);

        // 6. Featured Course (Most Popular)
        $featuredCourse = null;
        // Only fetch featured if on first page and no search/filters active (optional, but requested layout implies it's always there or just on landing)
        // The prompt implies a persistent hero. I'll fetch it always for visual consistency, or just the top one.
        $featSql = "SELECT c.*, u.name as instructor_name, 
                    (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) as students_count
                    FROM courses c 
                    JOIN users u ON c.instructor_id = u.id 
                    WHERE c.status = 'published'
                    ORDER BY students_count DESC LIMIT 1";
        $featuredRes = $this->db->fetchAll($featSql);
        $featuredCourse = $featuredRes[0] ?? null;

        $this->view('courses.index', [
            'courses' => $courses,
            'featuredCourse' => $featuredCourse,
            'filters' => [
                'q' => $search,
                'price' => $priceFilter,
                'sort' => $sort,
                'page' => $page,
                'total_pages' => $totalPages,
                'total_courses' => $totalCourses
            ]
        ]);
    }

    public function myCourses(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = $_SESSION['user_id'];
        
        $sql = "SELECT c.*, e.progress_percent, e.is_completed, u.name as instructor_name 
                FROM enrollments e 
                JOIN courses c ON e.course_id = c.id 
                JOIN users u ON c.instructor_id = u.id
                WHERE e.user_id = :uid 
                ORDER BY e.created_at DESC";
        
        $enrolledCourses = $this->db->fetchAll($sql, [':uid' => $userId]);

        $this->view('courses.my_learning', ['courses' => $enrolledCourses]);
    }

    public function show(string $id): void
    {
        $courseId = (int) $id;
        $courseModel = new Course();
        $course = $courseModel->find($courseId);

        if (!$course) {
            header('Location: /courses');
            exit;
        }

        // Access Control for Drafts
        if (($course['status'] ?? 'draft') === 'draft') {
            $currentUserId = $_SESSION['user_id'] ?? 0;
            $roles = $_SESSION['user_roles'] ?? [];
            $isAdmin = in_array('admin', $roles);
            
            if ($course['instructor_id'] != $currentUserId && !$isAdmin) {
                header('Location: /courses');
                exit;
            }
        }

        // Fetch Modules & Lessons & Calculate Stats
        $modules = $courseModel->modules($courseId);
        $moduleModel = new Module();
        
        $totalDuration = 0;
        $totalLessons = 0;

        foreach ($modules as &$module) {
            $module['lessons'] = $moduleModel->lessons($module['id']);
            $totalLessons += count($module['lessons']);
            foreach ($module['lessons'] as $l) {
                $totalDuration += ($l['duration'] ?? 0);
            }
        }

        // Check Enrollment Status if logged in
        $isEnrolled = false;
        $progressPercent = 0;
        
        if (isset($_SESSION['user_id'])) {
            $sql = "SELECT id, progress_percent FROM enrollments WHERE user_id = :uid AND course_id = :cid";
            $enrollment = $this->db->fetchAll($sql, [':uid' => $_SESSION['user_id'], ':cid' => $courseId]);
            if (!empty($enrollment)) {
                $isEnrolled = true;
                $progressPercent = $enrollment[0]['progress_percent'] ?? 0;
            }
        }
        $progressPercent = (int) max(0, min(100, round((float) $progressPercent)));

        // Instructor Details
        $sql = "SELECT id, name, field_of_work, email FROM users WHERE id = :id";
        $instructorRes = $this->db->fetchAll($sql, [':id' => $course['instructor_id']]);
        $instructor = $instructorRes[0] ?? ['name' => 'Unknown Instructor', 'field_of_work' => 'Educator'];

        // More from Instructor
        $sqlMore = "SELECT id, title, thumbnail, price FROM courses WHERE instructor_id = :iid AND id != :cid AND status = 'published' LIMIT 2";
        $moreCourses = $this->db->fetchAll($sqlMore, [':iid' => $course['instructor_id'], ':cid' => $courseId]);

        // Find first lesson for "Start/Continue" link
        $firstLessonId = null;
        if (!empty($modules) && !empty($modules[0]['lessons'])) {
            $firstLessonId = $modules[0]['lessons'][0]['id'];
        }

        $this->view('courses.show', [
            'course' => $course, 
            'modules' => $modules, 
            'isEnrolled' => $isEnrolled,
            'progressPercent' => $progressPercent,
            'firstLessonId' => $firstLessonId,
            'instructor' => $instructor,
            'moreCourses' => $moreCourses,
            'totalLessons' => $totalLessons,
            'totalDuration' => $totalDuration
        ]);
    }

    public function enroll(string $id): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            // Redirect to login with return url
            header('Location: /login?return=/course/' . $id);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $courseId = (int) $id;
        $courseModel = new Course();
        $course = $courseModel->find($courseId);

        // 1. Validation: Course exists & is published
        if (!$course || $course['status'] !== 'published') {
            $_SESSION['flash_error'] = 'Course not found or not available for enrollment.';
            header('Location: /courses');
            exit;
        }

        // Check if already enrolled
        $sql = "SELECT id FROM enrollments WHERE user_id = :uid AND course_id = :cid";
        $exists = $this->db->fetchAll($sql, [':uid' => $userId, ':cid' => $courseId]);

        if (!empty($exists)) {
            $_SESSION['flash_info'] = 'You are already enrolled in this course.';
            $this->redirectEmrollment($courseId);
            return;
        }

        // 2. Logic: Paid vs Free
        if ($course['price'] > 0) {
            // It's a paid course. Redirect to Cart/Checkout.
            // Add to cart if not already there
            if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
            if (!in_array($courseId, $_SESSION['cart'])) {
                $_SESSION['cart'][] = $courseId;
                $_SESSION['flash_success'] = 'Course added to cart. Please complete checkout.';
            } else {
                $_SESSION['flash_info'] = 'Course is already in your cart.';
            }
            header('Location: /cart');
            exit;
        }

        // 3. Free Course Enrollment
        $sql = "INSERT INTO enrollments (user_id, course_id) VALUES (:uid, :cid)";
        $this->db->query($sql, [':uid' => $userId, ':cid' => $courseId]);
        $_SESSION['flash_success'] = 'Enrolled successfully! Enjoy learning.';

        // Upgrade Role from Viewer to Student if necessary
        $userRoles = $_SESSION['user_roles'] ?? [];
        if (in_array('viewer', $userRoles) && !in_array('student', $userRoles)) {
            // Get Student Role ID
            $roleRes = $this->db->fetchAll("SELECT id FROM roles WHERE slug = 'student'");
            $studentRoleId = $roleRes[0]['id'] ?? 3;

            // Assign Student Role safely
            $this->db->insertIgnore('role_user', [
                'user_id' => $userId,
                'role_id' => $studentRoleId
            ]);

            // Update Session
            $_SESSION['user_roles'][] = 'student';
        }

        // Notify Instructor
        $studentName = $_SESSION['user_name'] ?? 'A student';
        (new \App\Domain\Notifications\Notification())->create([
            'user_id' => $course['instructor_id'],
            'type' => 'enrollment',
            'title' => 'New Student Enrolled',
            'message' => "$studentName enrolled in {$course['title']}",
            'link' => "/instructor/course/{$courseId}/students"
        ]);

        $this->redirectEmrollment($courseId);
    }

    private function redirectEmrollment(int $courseId): void
    {
        // Find the first lesson to redirect to
        $courseModel = new Course();
        $modules = $courseModel->modules($courseId);
        
        $moduleModel = new Module();
        $firstLessonId = null;
        
        if (!empty($modules)) {
            $lessons = $moduleModel->lessons($modules[0]['id']);
            if (!empty($lessons)) {
                $firstLessonId = $lessons[0]['id'];
            }
        }

        if ($firstLessonId) {
            header('Location: /learn/' . $courseId . '/lesson/' . $firstLessonId);
        } else {
             // Fallback if course has no lessons yet
             header('Location: /course/' . $courseId);
        }
        exit;
    }
}
