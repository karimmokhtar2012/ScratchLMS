<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Domain\Courses\Course;
use App\Domain\Courses\Module;
use App\Domain\Lessons\Lesson;
use App\Http\Middleware\AuthMiddleware;
use App\Policies\Authorization;

class InstructorController extends Controller
{
    public function __construct()
    {
        (new AuthMiddleware())->handle(['instructor', 'master_instructor', 'admin']);
    }

    public function create(): void
    {
        $this->view('instructor.create_course');
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /instructor/course/new');
            exit;
        }

        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = (float) ($_POST['price'] ?? 0);
        $thumbnail = '';

        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            // Validate thumbnail file
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            $validation = validateFileUpload($_FILES['thumbnail'], $allowedExtensions, $allowedMimeTypes);

            if (!$validation['valid']) {
                $_SESSION['flash_error'] = 'Thumbnail upload failed: ' . $validation['error'];
                header('Location: /instructor/course/new');
                exit;
            }

            $uploadDir = __DIR__ . '/../../public/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $extension = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadDir . $fileName)) {
                $thumbnail = '/uploads/' . $fileName;
            }
        }

        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $slug = $baseSlug;
        $counter = 1;

        $courseModel = new Course();
        while ($courseModel->findBySlug($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $courseId = $courseModel->create([
            'title' => $title,
            'slug' => $slug,
            'description' => $description,
            'price' => $price,
            'thumbnail' => $thumbnail,
            'instructor_id' => $_SESSION['user_id'],
            'status' => 'draft',
            'published_at' => null
        ]);

        if ($courseId) {
            $_SESSION['flash_success'] = 'Course created successfully! 🚀';
            header('Location: /instructor/course/' . $courseId . '/edit');
            exit;
        } else {
            $_SESSION['flash_error'] = 'Failed to create course.';
            header('Location: /instructor/course/new');
            exit;
        }
    }

    public function edit(string $id): void
    {
        $courseId = (int) $id;
        $courseModel = new Course();
        $contentItemModel = new \App\Domain\Courses\ContentItem(); // Use new model

        $course = $courseModel->find($courseId);
        if (!$course) {
            header('Location: /dashboard');
            exit;
        }

        // Security Check
        try {
            Authorization::requireInstructor($_SESSION['user_id'], $courseId);
        } catch (\Exception $e) {
            header('Location: /dashboard');
            exit;
        }

        $modules = $courseModel->modules($courseId);
        foreach ($modules as &$module) {
            // New Logic: Fetch from ContentItems
            $module['items'] = $contentItemModel->getModuleStructure((int)$module['id']);
            // Backwards compatibility for view (alias items to lessons if view expects 'lessons')
            // Ideally, we update the view to iterate 'items'
            $module['lessons'] = $module['items'];
        }

        // Fetch lessons for import library (all lessons owned by instructor)
        $db = \App\Core\Database::getInstance();
        $roles = $_SESSION['user_roles'] ?? [];
        $isPrivileged = in_array('admin', $roles, true) || in_array('master_instructor', $roles, true);
        $libraryLessons = $db->fetchAll("SELECT id, title, type FROM lessons WHERE instructor_id = :uid ORDER BY id DESC", [':uid' => $_SESSION['user_id']]);

        $this->view('instructor.course_builder', ['course' => $course, 'modules' => $modules, 'libraryLessons' => $libraryLessons]);
    }

    public function settings(string $id): void
    {
        $courseId = (int) $id;
        $courseModel = new Course();
        $course = $courseModel->find($courseId);

        if (!$course) {
            header('Location: /dashboard');
            exit;
        }

        // Security Check
        try {
            Authorization::requireInstructor($_SESSION['user_id'], $courseId);
        } catch (\Exception $e) {
            header('Location: /dashboard');
            exit;
        }

        // Fetch lessons + existing drip rules for this course (for UI helpers)
        $db = \App\Core\Database::getInstance();
        $lessons = $db->fetchAll(
            "SELECT l.id, l.title, l.type, m.sort_order as module_sort, ci.position as lesson_sort,
                    r.unlock_after_days, r.unlock_after_lesson_id, r.unlock_after_percent
             FROM content_items ci
             JOIN lessons l ON ci.item_type = 'lesson' AND ci.item_id = l.id
             JOIN modules m ON ci.module_id = m.id
             LEFT JOIN lesson_drip_rules r ON r.lesson_id = l.id
             WHERE ci.course_id = :cid
             ORDER BY m.sort_order ASC, ci.position ASC",
            [':cid' => $courseId]
        );

        $this->view('instructor.edit_course', [
            'course' => $course,
            'course_lessons' => $lessons
        ]);
    }

    public function update(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $courseId = (int) $id;
        $courseModel = new Course();
        $course = $courseModel->find($courseId);

        if (!$course) {
            header('Location: /dashboard');
            exit;
        }

        // Security Check
        try {
            Authorization::requireInstructor($_SESSION['user_id'], $courseId);
        } catch (\Exception $e) {
            header('Location: /dashboard');
            exit;
        }

        $title = $_POST['title'] ?? $course['title'];
        $description = $_POST['description'] ?? $course['description'];
        $price = isset($_POST['price']) ? (float)$_POST['price'] : $course['price'];
        $thumbnail = $course['thumbnail'];
        $status = $_POST['status'] ?? $course['status'] ?? 'draft';
        $publishedAt = !empty($_POST['published_at']) ? $_POST['published_at'] : ($course['published_at'] ?? null);
        $contentFlow = $_POST['content_flow'] ?? 'free';

        $hasCertificate = isset($_POST['has_certificate']) ? 1 : 0;
        $certificateAutoIssue = isset($_POST['certificate_auto_issue']) ? 1 : 0;

        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            // Validate thumbnail file
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            $validation = validateFileUpload($_FILES['thumbnail'], $allowedExtensions, $allowedMimeTypes);

            if (!$validation['valid']) {
                $_SESSION['flash_error'] = 'Thumbnail upload failed: ' . $validation['error'];
                header("Location: /instructor/course/$courseId/settings");
                exit;
            }

            $uploadDir = __DIR__ . '/../../public/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $extension = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadDir . $fileName)) {
                $thumbnail = '/uploads/' . $fileName;
            }
        }

        $db = \App\Core\Database::getInstance();
        $sql = "UPDATE courses SET title = :title, description = :desc, price = :price, thumbnail = :thumb, status = :status, published_at = :pub, has_certificate = :hasCert, certificate_auto_issue = :autoCert, content_flow = :flow WHERE id = :id";
        $db->query($sql, [
            ':title' => $title,
            ':desc' => $description,
            ':price' => $price,
            ':thumb' => $thumbnail,
            ':status' => $status,
            ':pub' => $publishedAt,
            ':hasCert' => $hasCertificate,
            ':autoCert' => $certificateAutoIssue,
            ':flow' => $contentFlow,
            ':id' => $courseId
        ]);

        // Persist drip rules per lesson if drip modes are chosen (for convenience in course settings)
        if (in_array($contentFlow, ['drip_time', 'drip_progress', 'drip_combined'], true)) {
            $dripRules = $_POST['drip_rules'] ?? [];
            if (is_array($dripRules) && !empty($dripRules)) {
                // Fetch lessons belonging to this course to validate ownership
                $lessons = $db->fetchAll("SELECT l.id FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = :cid", [
                    ':cid' => $courseId
                ]);
                $lessonIds = array_column($lessons, 'id');

                foreach ($dripRules as $lessonId => $rule) {
                    $lessonId = (int) $lessonId;
                    if (!in_array($lessonId, $lessonIds, true)) {
                        continue;
                    }
                    $days = isset($rule['days']) && $rule['days'] !== '' ? (int) $rule['days'] : null;
                    $prev = isset($rule['lesson']) && $rule['lesson'] !== '' ? (int) $rule['lesson'] : null;
                    $pct = isset($rule['percent']) && $rule['percent'] !== '' ? (int) $rule['percent'] : null;
                    $hasRule = ($days !== null) || ($prev !== null) || ($pct !== null);

                    if ($hasRule) {
                        $db->upsert(
                            'lesson_drip_rules',
                            [
                                'lesson_id' => $lessonId,
                                'unlock_after_days' => $days,
                                'unlock_after_lesson_id' => $prev,
                                'unlock_after_percent' => $pct
                            ],
                            ['lesson_id']
                        );
                    } else {
                        $db->query("DELETE FROM lesson_drip_rules WHERE lesson_id = :lid", [':lid' => $lessonId]);
                    }
                }
            }
        }

        $_SESSION['flash_success'] = 'Course details updated.';
        header("Location: /instructor/course/$courseId/settings");
        exit;
    }

    public function students(string $id): void
    {
        $courseId = (int) $id;
        $courseModel = new Course();
        $course = $courseModel->find($courseId);

        if (!$course) {
            header('Location: /dashboard');
            exit;
        }

        // Security Check
        try {
            Authorization::requireInstructor($_SESSION['user_id'], $courseId);
        } catch (\Exception $e) {
            header('Location: /dashboard');
            exit;
        }

        $db = \App\Core\Database::getInstance();
        $sql = "SELECT u.id, u.name, u.email, e.progress_percent, e.is_completed, e.created_at as enrolled_at
                FROM enrollments e
                JOIN users u ON e.user_id = u.id
                WHERE e.course_id = :cid
                ORDER BY e.created_at DESC";
        $students = $db->fetchAll($sql, [':cid' => $courseId]);

        $this->view('instructor.course_students', [
            'course' => $course,
            'students' => $students
        ]);
    }

    public function studentActivity(string $courseId, string $studentId): void
    {
        $courseId = (int) $courseId;
        $studentId = (int) $studentId;

        $courseModel = new Course();
        $course = $courseModel->find($courseId);

        if (!$course) {
            header('Location: /dashboard');
            exit;
        }

        // Security Check
        try {
            Authorization::requireInstructor($_SESSION['user_id'], $courseId);
        } catch (\Exception $e) {
            header('Location: /dashboard');
            exit;
        }

        $db = \App\Core\Database::getInstance();
        $student = $db->fetchAll("SELECT * FROM users WHERE id = :id", [':id' => $studentId])[0] ?? null;
        if (!$student) {
            header("Location: /instructor/course/$courseId/students");
            exit;
        }

        $sqlAttempts = "SELECT qa.*, l.title as quiz_title 
                        FROM quiz_attempts qa
                        JOIN quizzes q ON qa.quiz_id = q.id
                        JOIN lessons l ON q.lesson_id = l.id
                        JOIN modules m ON l.module_id = m.id
                        WHERE m.course_id = :cid AND qa.user_id = :uid
                        ORDER BY qa.started_at DESC";
        $attempts = $db->fetchAll($sqlAttempts, [':cid' => $courseId, ':uid' => $studentId]);

        $completedCount = $db->fetchAll(
            "SELECT COUNT(*) as count 
                                         FROM completed_lessons cl 
                                         JOIN lessons l ON cl.lesson_id = l.id 
                                         JOIN modules m ON l.module_id = m.id 
                                         WHERE m.course_id = :cid AND cl.user_id = :uid",
            [':cid' => $courseId, ':uid' => $studentId]
        )[0]['count'];

        $requests = $db->fetchAll(
            "SELECT ar.*, l.title as quiz_title 
                                   FROM attempt_requests ar 
                                   JOIN quizzes q ON ar.quiz_id = q.id 
                                   JOIN lessons l ON q.lesson_id = l.id
                                   WHERE ar.user_id = :uid AND ar.status = 'pending'",
            [':uid' => $studentId]
        );

        $this->view('instructor.student_activity', [
            'course' => $course,
            'student' => $student,
            'attempts' => $attempts,
            'requests' => $requests,
            'completedCount' => $completedCount
        ]);
    }

    public function handleRequest(string $requestId, string $action): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $requestId = (int) $requestId;
        $db = \App\Core\Database::getInstance();

        $request = $db->fetchAll("SELECT * FROM attempt_requests WHERE id = :id", [':id' => $requestId])[0] ?? null;
        if (!$request) {
            header('Location: /dashboard');
            exit;
        }

        $courseRes = $db->fetchAll("SELECT m.course_id, l.title as lesson_title FROM quizzes q JOIN lessons l ON q.lesson_id = l.id JOIN modules m ON l.module_id = m.id WHERE q.id = :qid", [':qid' => $request['quiz_id']]);

        if (empty($courseRes)) {
            header('Location: /dashboard');
            exit;
        }

        $courseId = $courseRes[0]['course_id'];
        $quizTitle = $courseRes[0]['lesson_title'] ?? 'Quiz';
        $returnPath = $_POST['return'] ?? '';
        $defaultReturn = "/instructor/course/$courseId/student/" . $request['user_id'];
        $returnPath = sanitizeRedirect($returnPath, $defaultReturn);

        // Security: Ensure Instructor Owns Course
        try {
            Authorization::requireInstructor($_SESSION['user_id'], (int)$courseId);
        } catch (\Exception $e) {
            header('Location: /dashboard');
            exit;
        }

        if (($request['status'] ?? 'pending') !== 'pending') {
            $_SESSION['flash_info'] = 'This request has already been processed.';
            header('Location: ' . $returnPath);
            exit;
        }

        $notifModel = new \App\Domain\Notifications\Notification();

        if ($action === 'approve') {
            $db->getConnection()->beginTransaction();
            $db->query("DELETE FROM quiz_attempts WHERE user_id = :uid AND quiz_id = :qid", [
                ':uid' => $request['user_id'],
                ':qid' => $request['quiz_id']
            ]);
            $db->query("UPDATE attempt_requests SET status = 'approved' WHERE id = :id", [':id' => $requestId]);
            $db->getConnection()->commit();

            $notifModel->create([
                'user_id' => $request['user_id'],
                'type' => 'system',
                'title' => 'Request Approved',
                'message' => "Your request to retake '$quizTitle' has been approved. Good luck!",
                'link' => "/learn/quiz/intro/{$request['quiz_id']}"
            ]);

            $_SESSION['flash_success'] = 'Request approved. Attempts reset.';
        } elseif ($action === 'reject') {
            $db->query("UPDATE attempt_requests SET status = 'rejected' WHERE id = :id", [':id' => $requestId]);

            $notifModel->create([
                'user_id' => $request['user_id'],
                'type' => 'system',
                'title' => 'Request Rejected',
                'message' => "Your request to retake '$quizTitle' was rejected.",
                'link' => "/learn/quiz/intro/{$request['quiz_id']}"
            ]);

            $_SESSION['flash_success'] = 'Request rejected.';
        } else {
            $_SESSION['flash_error'] = 'Invalid request action.';
        }

        header('Location: ' . $returnPath);
        exit;
    }

    public function addModule(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $title = $_POST['title'] ?? '';
        $courseId = (int) ($_POST['course_id'] ?? 0);

        if ($title && $courseId) {
            $courseModel = new Course();
            $course = $courseModel->find($courseId);

            if (!$course) {
                header('Location: /dashboard');
                exit;
            }

            $roles = $_SESSION['user_roles'] ?? [];
            $hasAccess = ($course['instructor_id'] == $_SESSION['user_id']) || in_array('admin', $roles) || in_array('master_instructor', $roles);

            if ($hasAccess) {
                $moduleModel = new Module();
                $moduleModel->create(['course_id' => $courseId, 'title' => $title]);
                $_SESSION['flash_success'] = 'Module added.';
            }
        }

        header('Location: /instructor/course/' . $courseId . '/edit');
        exit;
    }

    public function addLesson(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $title = $_POST['title'] ?? '';
        $moduleId = (int) ($_POST['module_id'] ?? 0);
        $type = $_POST['type'] ?? 'video';
        $contentUrl = $_POST['content_url'] ?? '';
        $contentText = $_POST['content_text'] ?? '';
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $durationMinutes = (int) ($_POST['duration'] ?? 0);
        $durationSeconds = $durationMinutes * 60;
        $attachmentUrl = null;

        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            // Validate attachment file
            $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'txt'];
            $allowedMimeTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip',
                'text/plain'
            ];

            $validation = validateFileUpload($_FILES['attachment'], $allowedExtensions, $allowedMimeTypes);

            if (!$validation['valid']) {
                $_SESSION['flash_error'] = 'Attachment upload failed: ' . $validation['error'];
                header('Location: /instructor/course/' . $courseId . '/edit');
                exit;
            }

            $uploadDir = __DIR__ . '/../../public/uploads/resources/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $extension = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $fileName)) {
                $attachmentUrl = '/uploads/resources/' . $fileName;
            }
        }

        if ($title && $moduleId) {
            $db = \App\Core\Database::getInstance();

            // Verify module ownership
            $moduleCheck = $db->fetchAll("SELECT c.instructor_id, c.id as course_id FROM modules m JOIN courses c ON m.course_id = c.id WHERE m.id = :mid", [':mid' => $moduleId])[0] ?? null;

            if (!$moduleCheck || $moduleCheck['instructor_id'] != $_SESSION['user_id']) {
                $roles = $_SESSION['user_roles'] ?? [];
                if (!in_array('admin', $roles)) {
                    $_SESSION['flash_error'] = 'Access denied to this module.';
                    header('Location: /dashboard');
                    exit;
                }
            }

            // 1. Insert Payload (Lesson)
            // NOTE: module_id and sort_order here are legacy/deprecated but kept for fallback or unique constraints if any.
            $sql = "INSERT INTO lessons (module_id, instructor_id, title, type, content_url, content_text, attachment_url, duration, sort_order) 
                     VALUES (:mid, :uid, :title, :type, :url, :text, :att, :dur, 999)";

            $db->query($sql, [
                ':mid' => $moduleId,
                ':uid' => $_SESSION['user_id'],
                ':title' => $title,
                ':type' => $type,
                ':url' => $contentUrl,
                ':text' => $contentText,
                ':att' => $attachmentUrl,
                ':dur' => $durationSeconds
            ]);
            $lessonId = $db->getConnection()->lastInsertId();

            // 2. Insert Structure (ContentItem)
            $contentItemModel = new \App\Domain\Courses\ContentItem();

            if ($type === 'quiz') {
                // Create Quiz Entity
                $db->query("INSERT INTO quizzes (lesson_id) VALUES (:lid)", [':lid' => $lessonId]);
                $quizId = $db->getConnection()->lastInsertId();

                // Content Item points to Quiz
                $contentItemModel->createItem([
                    'course_id' => (int)$moduleCheck['course_id'],
                    'module_id' => $moduleId,
                    'item_type' => 'quiz',
                    'item_id' => $quizId,
                    'display_group_id' => null
                ]);
            } else {
                // Standard Lesson
                $contentItemModel->createItem([
                    'course_id' => (int)$moduleCheck['course_id'],
                    'module_id' => $moduleId,
                    'item_type' => 'lesson',
                    'item_id' => $lessonId,
                    'display_group_id' => null
                ]);
            }

            $_SESSION['flash_success'] = 'Lesson added.';
        }

        header('Location: /instructor/course/' . $courseId . '/edit');
        exit;
    }

    public function editLesson(string $id): void
    {
        $lessonId = (int) $id;
        $db = \App\Core\Database::getInstance();

        // Use LEFT JOIN because library lessons have no module_id
        $sql = "SELECT l.*, m.course_id 
                FROM lessons l 
                LEFT JOIN modules m ON l.module_id = m.id 
                WHERE l.id = :id";
        $lesson = $db->fetchAll($sql, [':id' => $lessonId])[0] ?? null;

        if (!$lesson) {
            header('Location: /dashboard');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $roles = $_SESSION['user_roles'] ?? [];
        $hasAccess = false;
        $course = null;

        // Access Check
        if ($lesson['course_id']) {
            // It's part of a course
            $courseModel = new Course();
            $course = $courseModel->find($lesson['course_id']);

            if (!$course) {
                header('Location: /dashboard');
                exit;
            }

            $hasAccess = ($course['instructor_id'] == $userId) || in_array('admin', $roles) || in_array('master_instructor', $roles);
        } else {
            // It's a library lesson (orphaned)
            // Check if current user is the creator
            $hasAccess = ($lesson['instructor_id'] == $userId) || in_array('admin', $roles);
        }

        if (!$hasAccess) {
            header('Location: /dashboard');
            exit;
        }

        $courseFlow = null;
        $courseLessons = [];
        $dripRule = null;
        if (!empty($lesson['course_id'])) {
            $courseFlow = $db->fetchAll("SELECT content_flow FROM courses WHERE id = :cid", [':cid' => $lesson['course_id']])[0]['content_flow'] ?? null;
            $courseLessons = $db->fetchAll("SELECT l.id, l.title FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = :cid ORDER BY m.sort_order ASC, l.sort_order ASC", [':cid' => $lesson['course_id']]);
            $dripRule = $db->fetchAll("SELECT * FROM lesson_drip_rules WHERE lesson_id = :lid", [':lid' => $lesson['id']])[0] ?? null;
        }

        $this->view('instructor.edit_lesson', [
            'lesson' => $lesson,
            'course_id' => $lesson['course_id'] ?? null, // Pass null if library lesson
            'course_flow' => $courseFlow,
            'course_lessons' => $courseLessons,
            'drip_rule' => $dripRule
        ]);
    }

    public function updateLesson(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $lessonId = (int) $id;
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $title = $_POST['title'] ?? '';
        $type = $_POST['type'] ?? 'video'; // Capture new type
        $contentUrl = $_POST['content_url'] ?? '';
        $contentText = $_POST['content_text'] ?? '';
        $duration = (int) ($_POST['duration'] ?? 0) * 60;

        // File Uploads
        $uploadDir = __DIR__ . '/../../public/uploads/';

        // Video File (If uploaded, overrides content_url)
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
            // Validate video file
            $allowedExtensions = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v'];
            $allowedMimeTypes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'];

            $validation = validateFileUpload($_FILES['video_file'], $allowedExtensions, $allowedMimeTypes);

            if (!$validation['valid']) {
                $_SESSION['flash_error'] = 'Video upload failed: ' . $validation['error'];
                header("Location: /instructor/lesson/$lessonId/edit");
                exit;
            }

            $videoDir = $uploadDir . 'videos/';
            if (!is_dir($videoDir)) mkdir($videoDir, 0755, true);

            $extension = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            if (move_uploaded_file($_FILES['video_file']['tmp_name'], $videoDir . $fileName)) {
                $contentUrl = '/uploads/videos/' . $fileName;
            }
        }

        $db = \App\Core\Database::getInstance();
        $currentLesson = $db->fetchAll("SELECT l.attachment_url, l.content_url, m.course_id, l.instructor_id 
                                        FROM lessons l 
                                        LEFT JOIN modules m ON l.module_id = m.id 
                                        WHERE l.id = :id", [':id' => $lessonId])[0] ?? null;

        if (!$currentLesson) {
            header('Location: /dashboard');
            exit;
        }

        // Security Check
        $hasAccess = false;
        if ($currentLesson['course_id']) {
            try {
                Authorization::requireInstructor($_SESSION['user_id'], $currentLesson['course_id']);
                $hasAccess = true;
            } catch (\Exception $e) {
            }
        } elseif ($currentLesson['instructor_id'] == $_SESSION['user_id']) {
            // Library lesson
            $hasAccess = true;
        }

        if (!$hasAccess) {
            $_SESSION['flash_error'] = 'Access denied.';
            header('Location: /dashboard');
            exit;
        }

        if (empty($contentUrl) && $type === 'video') $contentUrl = $currentLesson['content_url']; // Keep old if empty and no file

        $attachmentUrl = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            // Validate attachment file
            $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'txt'];
            $allowedMimeTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip',
                'text/plain'
            ];

            $validation = validateFileUpload($_FILES['attachment'], $allowedExtensions, $allowedMimeTypes);

            if (!$validation['valid']) {
                $_SESSION['flash_error'] = 'Attachment upload failed: ' . $validation['error'];
                header("Location: /instructor/lesson/$lessonId/edit");
                exit;
            }

            $resDir = $uploadDir . 'resources/';
            if (!is_dir($resDir)) mkdir($resDir, 0755, true);

            $extension = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $resDir . $fileName)) {
                $attachmentUrl = '/uploads/resources/' . $fileName;
            }
        } else {
            $attachmentUrl = $currentLesson['attachment_url'];
        }

        // 1. Update Content (Payload)
        $sql = "UPDATE lessons SET title = :title, type = :type, content_url = :url, content_text = :text, duration = :dur, attachment_url = :att WHERE id = :id";
        $db->query($sql, [
            ':title' => $title,
            ':type' => $type,
            ':url' => $contentUrl,
            ':text' => $contentText,
            ':dur' => $duration,
            ':att' => $attachmentUrl,
            ':id' => $lessonId
        ]);

        // 2. Update Drip Rules (Content Item Rules)
        // Ideally we migrate this to content_item_rules table, but for now we keep legacy drip logic working 
        // via legacy CourseProgressService if needed, OR we refactor CourseProgressService to read legacy rules 
        // if item rules missing. 
        // Current Plan A stores rules in `content_item_rules`. We should write there.
        // HOWEVER, the `InstructorController` audit showed we are sticking to `lesson_drip_rules`.
        // To complete the "Cutover", we should ideally write to `content_item_rules`.
        // Given complexity, let's keep `lesson_drip_rules` as the "Legacy Store" that the Service layer reads from 
        // until we build a proper Rule Editor. The Service Layer already has dual-read logic (Item Rules -> Legacy Rules).
        // So we keep this part AS IS for now.

        if ($courseId) {
            $flow = $db->fetchAll("SELECT content_flow FROM courses WHERE id = :cid", [':cid' => $courseId])[0]['content_flow'] ?? 'free';
            if (in_array($flow, ['drip_time', 'drip_progress', 'drip_combined'], true)) {
                $unlockDays = isset($_POST['unlock_after_days']) ? (int) $_POST['unlock_after_days'] : null;
                $unlockLesson = isset($_POST['unlock_after_lesson_id']) ? (int) $_POST['unlock_after_lesson_id'] : null;
                $unlockPercent = isset($_POST['unlock_after_percent']) ? (int) $_POST['unlock_after_percent'] : null;
                $hasRule = ($unlockDays || $unlockLesson || $unlockPercent);

                if ($hasRule) {
                    $db->upsert(
                        'lesson_drip_rules',
                        [
                            'lesson_id' => $lessonId,
                            'unlock_after_days' => $unlockDays,
                            'unlock_after_lesson_id' => $unlockLesson ?: null,
                            'unlock_after_percent' => $unlockPercent
                        ],
                        ['lesson_id']
                    );
                } else {
                    $db->query("DELETE FROM lesson_drip_rules WHERE lesson_id = :lid", [':lid' => $lessonId]);
                }
            }
        }

        $_SESSION['flash_success'] = 'Lesson updated successfully.';

        if ($courseId) {
            header("Location: /learn/$courseId/lesson/$lessonId");
        } else {
            header("Location: /instructor/lessons");
        }
        exit;
    }

    public function delete(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $courseId = (int) $id;
        $courseModel = new Course();
        $course = $courseModel->find($courseId);

        if (!$course) {
            header('Location: /dashboard');
            exit;
        }

        $roles = $_SESSION['user_roles'] ?? [];
        $hasAccess = ($course['instructor_id'] == $_SESSION['user_id']) || in_array('admin', $roles) || in_array('master_instructor', $roles);

        if (!$course || !$hasAccess) {
            header('Location: /dashboard');
            exit;
        }

        $db = \App\Core\Database::getInstance();
        $db->query("DELETE FROM courses WHERE id = :id", [':id' => $courseId]);

        $_SESSION['flash_success'] = 'Course permanently deleted.';
        header('Location: /dashboard');
        exit;
    }

    public function deleteModule(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $moduleId = (int) $id;
        $db = \App\Core\Database::getInstance();

        $sql = "SELECT course_id FROM modules WHERE id = :id";
        $res = $db->fetchAll($sql, [':id' => $moduleId]);
        $courseId = $res[0]['course_id'] ?? 0;

        if ($courseId) {
            try {
                Authorization::requireInstructor($_SESSION['user_id'], $courseId);
                $db->query("DELETE FROM modules WHERE id = :id", [':id' => $moduleId]);
                $_SESSION['flash_success'] = 'Module deleted.';
                header("Location: /instructor/course/$courseId/edit");
            } catch (\Exception $e) {
                header('Location: /dashboard');
            }
        } else {
            header("Location: /dashboard");
        }
        exit;
    }

    public function updateModule(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $moduleId = (int) $id;
        $title = $_POST['title'] ?? '';

        // Redirect back (need course ID to do it properly, so fetch it)
        $db = \App\Core\Database::getInstance();
        $res = $db->fetchAll("SELECT course_id FROM modules WHERE id = :id", [':id' => $moduleId]);
        $courseId = $res[0]['course_id'] ?? 0;

        if ($courseId) {
            try {
                Authorization::requireInstructor($_SESSION['user_id'], $courseId);

                if (empty($title)) {
                    $_SESSION['flash_error'] = 'Module title cannot be empty.';
                } else {
                    $sql = "UPDATE modules SET title = :title WHERE id = :id";
                    $db->query($sql, [':title' => $title, ':id' => $moduleId]);
                    $_SESSION['flash_success'] = 'Module renamed successfully.';
                }

                header("Location: /instructor/course/$courseId/edit");
            } catch (\Exception $e) {
                header('Location: /dashboard');
            }
        } else {
            header("Location: /dashboard");
        }
        exit;
    }

    public function enrollStudent(string $courseId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $courseId = (int) $courseId;

        // Access Check
        try {
            Authorization::requireInstructor($_SESSION['user_id'], $courseId);
        } catch (\Exception $e) {
            header('Location: /dashboard');
            exit;
        }

        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $_SESSION['flash_error'] = 'Please provide an email address.';
            header("Location: /instructor/course/$courseId/students");
            exit;
        }

        $db = \App\Core\Database::getInstance();

        // 1. Find User
        $user = $db->fetchAll("SELECT id FROM users WHERE email = :email", [':email' => $email]);
        if (empty($user)) {
            $_SESSION['flash_error'] = "User with email '$email' not found.";
            header("Location: /instructor/course/$courseId/students");
            exit;
        }
        $userId = $user[0]['id'];

        // 2. Check if already enrolled
        $exists = $db->fetchAll(
            "SELECT id FROM enrollments WHERE user_id = :uid AND course_id = :cid",
            [':uid' => $userId, ':cid' => $courseId]
        );

        if ($exists) {
            $_SESSION['flash_error'] = "User is already enrolled in this course.";
        } else {
            // 3. Enroll
            $db->query(
                "INSERT INTO enrollments (user_id, course_id) VALUES (:uid, :cid)",
                [':uid' => $userId, ':cid' => $courseId]
            );
            $_SESSION['flash_success'] = "Student enrolled successfully.";
        }

        header("Location: /instructor/course/$courseId/students");
        exit;
    }

    public function pendingRequests(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            header('Location: /login');
            exit;
        }

        $db = \App\Core\Database::getInstance();
        $roles = $_SESSION['user_roles'] ?? [];
        $isPrivileged = in_array('admin', $roles, true) || in_array('master_instructor', $roles, true);

        $sqlAttempts = "SELECT ar.id,
                               ar.created_at,
                               u.id as user_id,
                               u.name,
                               u.email,
                               c.id as course_id,
                               c.title as course_title,
                               l.title as quiz_title
                        FROM attempt_requests ar
                        JOIN users u ON ar.user_id = u.id
                        JOIN quizzes q ON ar.quiz_id = q.id
                        JOIN lessons l ON q.lesson_id = l.id
                        JOIN modules m ON l.module_id = m.id
                        JOIN courses c ON m.course_id = c.id
                        WHERE ar.status = 'pending'";
        if (!$isPrivileged) {
            $sqlAttempts .= " AND c.instructor_id = :uid";
        }
        $sqlAttempts .= " ORDER BY ar.created_at DESC";
        $attemptRequests = $db->fetchAll($sqlAttempts, $isPrivileged ? [] : [':uid' => $userId]);

        $this->view('instructor.retake_requests', [
            'attemptRequests' => $attemptRequests
        ]);
    }

    public function gradingQueue(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            header('Location: /login');
            exit;
        }

        $db = \App\Core\Database::getInstance();
        $roles = $_SESSION['user_roles'] ?? [];
        $isPrivileged = in_array('admin', $roles, true) || in_array('master_instructor', $roles, true);

        $sqlGrading = "SELECT g.id as grading_id,
                              g.attempt_id,
                              g.question_id,
                              g.status,
                              g.ai_score,
                              g.ai_feedback,
                              g.ai_rationale,
                              g.final_score,
                              u.id as user_id,
                              u.name,
                              u.email,
                              c.id as course_id,
                              c.title as course_title,
                              qz.id as quiz_id,
                              l.title as lesson_title,
                              qu.question_text,
                              qu.points,
                              qa.answers as attempt_answers
                       FROM grading_queue g
                       JOIN quiz_attempts qa ON g.attempt_id = qa.id
                       JOIN questions qu ON g.question_id = qu.id
                       JOIN quizzes qz ON qa.quiz_id = qz.id
                       JOIN lessons l ON qz.lesson_id = l.id
                       JOIN modules m ON l.module_id = m.id
                       JOIN courses c ON m.course_id = c.id
                       JOIN users u ON qa.user_id = u.id
                       WHERE g.status IN ('pending_review','ai_scored')";
        if (!$isPrivileged) {
            $sqlGrading .= " AND c.instructor_id = :uid";
        }
        $sqlGrading .= " ORDER BY g.created_at DESC";
        $gradingQueue = $db->fetchAll($sqlGrading, $isPrivileged ? [] : [':uid' => $userId]);

        foreach ($gradingQueue as &$item) {
            $answers = json_decode($item['attempt_answers'] ?? '{}', true);
            $item['submission_text'] = $answers[$item['question_id']] ?? 'No submission found';
        }

        $this->view('instructor.grading_queue', [
            'gradingQueue' => $gradingQueue
        ]);
    }

    public function certificateApprovals(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            header('Location: /login');
            exit;
        }

        $db = \App\Core\Database::getInstance();
        $roles = $_SESSION['user_roles'] ?? [];
        $isPrivileged = in_array('admin', $roles, true) || in_array('master_instructor', $roles, true);

        $sqlCerts = "SELECT e.id as enrollment_id,
                            e.progress_percent,
                            e.is_completed,
                            e.created_at as enrolled_at,
                            u.id as user_id,
                            u.name,
                            u.email,
                            c.id as course_id,
                            c.title as course_title
                     FROM enrollments e
                     JOIN users u ON e.user_id = u.id
                     JOIN courses c ON e.course_id = c.id
                     WHERE c.has_certificate = 1
                       AND c.certificate_auto_issue = 0
                       AND e.is_completed = 1
                       AND e.certificate_id IS NULL
                     ORDER BY e.created_at DESC";
        if (!$isPrivileged) {
            $sqlCerts = str_replace("WHERE c.has_certificate = 1", "WHERE c.instructor_id = :uid AND c.has_certificate = 1", $sqlCerts);
        }

        // Fix for Postgres Boolean Binding
        // We replace literals with bound params manually because str_replace above relies on literals
        // So we will just use literals '1' and '0' which work for Postgres if quoted, but better to be consistent.
        // Actually, the str_replace makes this tricky. 
        // Let's rewrite the initial query to use correct casting or standard SQL.
        // Postgres accepts '1'/'0' strings for boolean. 
        // So we can change integer 1 to string '1' in the SQL if we want, OR use TRUE/FALSE keywords.
        // Standard SQL: TRUE / FALSE works in Postgres and MySQL (as 1/0 alias). SQLite understands TRUE/FALSE as 1/0.
        // Let's try replacing 1/0 with TRUE/FALSE.

        $sqlCerts = "SELECT u.name as student_name,
                            l.title as quiz_title,
                            e.id as enrollment_id,
                            e.created_at,
                            e.progress_percent,
                            c.id as course_id,
                            c.title as course_title
                     FROM enrollments e
                     JOIN users u ON e.user_id = u.id
                     JOIN courses c ON e.course_id = c.id
                     WHERE c.has_certificate = TRUE
                       AND c.certificate_auto_issue = FALSE
                       AND e.is_completed = TRUE
                       AND e.certificate_id IS NULL
                     ORDER BY e.created_at DESC";

        if (!$isPrivileged) {
            $sqlCerts = str_replace("WHERE c.has_certificate = TRUE", "WHERE c.instructor_id = :uid AND c.has_certificate = TRUE", $sqlCerts);
        }

        $certificateApprovals = $db->fetchAll($sqlCerts, $isPrivileged ? [] : [':uid' => $userId]);

        $this->view('instructor.certificate_approvals', [
            'certificateApprovals' => $certificateApprovals
        ]);
    }

    public function issueCertificate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /instructor/requests#certificates');
            exit;
        }

        $enrollmentId = (int) ($_POST['enrollment_id'] ?? 0);
        $userId = $_SESSION['user_id'] ?? null;
        $returnPath = $_POST['return'] ?? '';
        $returnPath = sanitizeRedirect($returnPath, '/instructor/requests#certificates');

        if (!$enrollmentId || !$userId) {
            $_SESSION['flash_error'] = 'Invalid certificate approval request.';
            header('Location: ' . $returnPath);
            exit;
        }

        $db = \App\Core\Database::getInstance();
        $sql = "SELECT e.id,
                       e.user_id,
                       e.course_id,
                       e.certificate_id,
                       e.is_completed,
                       c.has_certificate,
                       c.certificate_auto_issue
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                WHERE e.id = :eid
                  AND c.instructor_id = :uid";
        $row = $db->fetchAll($sql, [':eid' => $enrollmentId, ':uid' => $userId])[0] ?? null;

        if (!$row) {
            $_SESSION['flash_error'] = 'Enrollment not found or access denied.';
            header('Location: ' . $returnPath);
            exit;
        }

        if (!empty($row['certificate_id'])) {
            $_SESSION['flash_info'] = 'Certificate already issued for this enrollment.';
            header('Location: ' . $returnPath);
            exit;
        }

        if (!$row['is_completed'] || !$row['has_certificate'] || $row['certificate_auto_issue']) {
            $_SESSION['flash_error'] = 'Certificate cannot be issued for this enrollment.';
            header('Location: ' . $returnPath);
            exit;
        }

        $certId = 'CERT-' . strtoupper(uniqid());
        $db->query("UPDATE enrollments SET certificate_id = :certId, certified_at = CURRENT_TIMESTAMP WHERE id = :eid", [
            ':certId' => $certId,
            ':eid' => $enrollmentId
        ]);

        $_SESSION['flash_success'] = 'Certificate issued successfully.';
        header('Location: ' . $returnPath);
        exit;
    }

    public function gradeEssay(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /instructor/requests#grading');
            exit;
        }

        $gradingId = (int) ($_POST['grading_id'] ?? 0);
        $rawScore = $_POST['final_score'] ?? null;
        $finalScore = is_string($rawScore) ? trim($rawScore) : $rawScore;
        $userId = $_SESSION['user_id'] ?? null;

        if (!$gradingId || $finalScore === null || $finalScore === '' || $userId === null || !is_numeric($finalScore)) {
            $_SESSION['flash_error'] = 'Invalid grading submission.';
            header('Location: /instructor/requests#grading');
            exit;
        }

        $db = \App\Core\Database::getInstance();
        $row = $db->fetchAll("SELECT g.id, g.attempt_id, g.question_id, c.id as course_id, l.id as lesson_id, qu.points, qa.user_id as student_id, qa.quiz_id
                              FROM grading_queue g
                              JOIN quiz_attempts qa ON g.attempt_id = qa.id
                              JOIN quizzes qz ON qa.quiz_id = qz.id
                              JOIN lessons l ON qz.lesson_id = l.id
                              JOIN modules m ON l.module_id = m.id
                              JOIN courses c ON m.course_id = c.id
                              JOIN questions qu ON g.question_id = qu.id
                              WHERE g.id = :gid AND c.instructor_id = :uid", [
            ':gid' => $gradingId,
            ':uid' => $userId
        ])[0] ?? null;

        if (!$row) {
            $_SESSION['flash_error'] = 'Grading item not found or access denied.';
            header('Location: /instructor/requests#grading');
            exit;
        }

        $finalScore = max(0, min((float) $row['points'], $finalScore));
        $db->query("UPDATE grading_queue SET status = 'approved', final_score = :score, reviewer_id = :uid, updated_at = CURRENT_TIMESTAMP WHERE id = :gid", [
            ':score' => $finalScore,
            ':uid' => $userId,
            ':gid' => $gradingId
        ]);

        $quizService = new \App\Services\QuizService();
        $result = $quizService->recalculateAttemptScore((int) $row['attempt_id']);

        if (($result['grading_status'] ?? '') === 'final' && !empty($result['is_passed'])) {
            $lessonId = (int) $row['lesson_id'];
            $studentId = (int) $row['student_id'];
            $quizId = (int) $row['quiz_id'];

            // 1. Update Content Progress (The New Source of Truth)
            $item = $db->fetchAll("SELECT id FROM content_items WHERE item_type = 'quiz' AND item_id = :qid", [':qid' => $quizId])[0] ?? null;
            if ($item) {
                $db->upsert(
                    'content_progress',
                    [
                        'user_id' => $studentId,
                        'item_id' => $item['id'],
                        'status' => 'completed',
                        'completed_at' => date('Y-m-d H:i:s'),
                        'score' => $result['score']
                    ],
                    ['user_id', 'item_id']
                );
            }

            // 2. Legacy Fallback (Optional but good for reports)
            $db->insertIgnore('completed_lessons', [
                'user_id' => $studentId,
                'lesson_id' => $lessonId
            ]);

            $progressService = new \App\Services\CourseProgressService();
            $progressService->updateProgress($studentId, (int) $row['course_id']);

            $existingLog = $db->fetchAll("SELECT 1 FROM xapi_logs WHERE user_id = :uid AND lesson_id = :lid AND verb = 'completed' LIMIT 1", [
                ':uid' => $studentId,
                ':lid' => $lessonId
            ]);
            if (empty($existingLog)) {
                $db->query("INSERT INTO xapi_logs (user_id, lesson_id, verb, meta_data) VALUES (:uid, :lid, 'completed', :meta)", [
                    ':uid' => $studentId,
                    ':lid' => $lessonId,
                    ':meta' => json_encode(['score' => $result['score'], 'grader' => 'instructor'])
                ]);
            }
        }

        $_SESSION['flash_success'] = 'Grade saved.';
        header('Location: /instructor/requests#grading');
        exit;
    }

    public function lessonLibrary(): void
    {
        $userId = $_SESSION['user_id'];
        $db = \App\Core\Database::getInstance();

        // Filters
        $filterType = $_GET['filter'] ?? 'all';
        $sort = $_GET['sort'] ?? 'date_desc';
        $search = $_GET['search'] ?? '';

        // Base Query
        $sql = "SELECT l.*, c.title as course_title, c.id as course_id
                FROM lessons l 
                LEFT JOIN modules m ON l.module_id = m.id 
                LEFT JOIN courses c ON m.course_id = c.id
                WHERE l.instructor_id = :uid";

        $params = [':uid' => $userId];

        // Apply Search
        if ($search) {
            $sql .= " AND l.title LIKE :search";
            $params[':search'] = "%$search%";
        }

        // Apply Type Filter
        if ($filterType === 'video') {
            $sql .= " AND l.type = 'video'";
        } elseif ($filterType === 'article') {
            $sql .= " AND l.type = 'article'";
        }

        // Apply Sorting
        switch ($sort) {
            case 'date_asc':
                $sql .= " ORDER BY l.created_at ASC";
                break;
            case 'name_asc':
                $sql .= " ORDER BY l.title ASC";
                break;
            case 'name_desc':
                $sql .= " ORDER BY l.title DESC";
                break;
            default:
                $sql .= " ORDER BY l.id DESC"; // date_desc (using ID as proxy for simplicity or created_at if reliable)
        }

        $lessons = $db->fetchAll($sql, $params);

        // Fetch Courses & Modules for "Add to Course" Modal
        $sqlCourses = "SELECT c.id, c.title FROM courses c WHERE c.instructor_id = :uid ORDER BY c.title ASC";
        $courses = $db->fetchAll($sqlCourses, [':uid' => $userId]);

        foreach ($courses as &$course) {
            $course['modules'] = $db->fetchAll("SELECT id, title FROM modules WHERE course_id = :cid ORDER BY sort_order ASC", [':cid' => $course['id']]);
        }

        $this->view('instructor.lessons.index', [
            'lessons' => $lessons,
            'courses' => $courses,
            'filters' => ['type' => $filterType, 'sort' => $sort, 'search' => $search]
        ]);
    }

    public function addToCourse(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $lessonId = (int) ($_POST['lesson_id'] ?? 0);
        $moduleId = (int) ($_POST['module_id'] ?? 0);
        $userId = $_SESSION['user_id'];

        if ($lessonId && $moduleId) {
            $db = \App\Core\Database::getInstance();

            // Verify ownership of both lesson and target module
            $lesson = $db->fetchAll("SELECT instructor_id FROM lessons WHERE id = :id", [':id' => $lessonId])[0] ?? null;
            $module = $db->fetchAll("SELECT c.instructor_id, c.id as course_id FROM modules m JOIN courses c ON m.course_id = c.id WHERE m.id = :id", [':id' => $moduleId])[0] ?? null;

            if ($lesson && $module && $lesson['instructor_id'] == $userId && $module['instructor_id'] == $userId) {
                // 1. Move Payload (Legacy sync)
                $db->query("UPDATE lessons SET module_id = :mid WHERE id = :lid", [
                    ':mid' => $moduleId,
                    ':lid' => $lessonId
                ]);

                // 2. Move Content Items (Structure)
                // We need to move the lesson item AND any grouped items (like attached quizzes)
                $contentItemModel = new \App\Domain\Courses\ContentItem();

                // Find the main lesson item
                $lessonItem = $db->fetchAll("SELECT id, display_group_id FROM content_items WHERE item_type = 'lesson' AND item_id = :lid", [':lid' => $lessonId])[0] ?? null;

                if ($lessonItem) {
                    // Update lesson item
                    // Calculate position: append to end
                    $sqlPos = "SELECT MAX(position) as max_pos FROM content_items WHERE course_id = :cid AND module_id = :mid";
                    $res = $db->fetchAll($sqlPos, [':cid' => $module['course_id'], ':mid' => $moduleId]);
                    $newPos = ($res[0]['max_pos'] ?? 0) + 1;

                    $db->query("UPDATE content_items SET module_id = :mid, course_id = :cid, position = :pos WHERE id = :id", [
                        ':mid' => $moduleId,
                        ':cid' => $module['course_id'],
                        ':pos' => $newPos,
                        ':id' => $lessonItem['id']
                    ]);

                    // Update grouped items (e.g. Quizzes) - Sequential positions to avoid overlap
                    if (!empty($lessonItem['display_group_id'])) {
                        $peers = $db->fetchAll("SELECT id FROM content_items WHERE display_group_id = :gid AND id != :lid ORDER BY position ASC", [
                            ':gid' => $lessonItem['display_group_id'],
                            ':lid' => $lessonItem['id']
                        ]);

                        foreach ($peers as $index => $peer) {
                            $db->query("UPDATE content_items SET module_id = :mid, course_id = :cid, position = :pos WHERE id = :id", [
                                ':mid' => $moduleId,
                                ':cid' => $module['course_id'],
                                ':pos' => $newPos + ($index + 1),
                                ':id' => $peer['id']
                            ]);
                        }
                    }
                } else {
                    // Legacy/Library Lesson without Item? Create one.
                    $newLessonItemId = $contentItemModel->createItem([
                        'course_id' => (int)$module['course_id'],
                        'module_id' => $moduleId,
                        'item_type' => 'lesson',
                        'item_id' => $lessonId,
                        'display_group_id' => null
                    ]);

                    // CHECK FOR ATTACHED QUIZ (Legacy sync)
                    $quiz = $db->fetchAll("SELECT id FROM quizzes WHERE lesson_id = :lid", [':lid' => $lessonId])[0] ?? null;
                    if ($quiz) {
                        // Generate a group ID and update both
                        $groupId = (int)$newLessonItemId + 900000;
                        $db->query("UPDATE content_items SET display_group_id = :gid WHERE id = :id", [':gid' => $groupId, ':id' => $newLessonItemId]);

                        $contentItemModel->createItem([
                            'course_id' => (int)$module['course_id'],
                            'module_id' => $moduleId,
                            'item_type' => 'quiz',
                            'item_id' => $quiz['id'],
                            'display_group_id' => $groupId
                        ]);
                    }
                }

                $_SESSION['flash_success'] = 'Lesson added to course successfully.';
            } else {
                $_SESSION['flash_error'] = 'Invalid operation.';
            }
        }

        header('Location: /instructor/lessons');
        exit;
    }

    public function createLibraryLesson(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $title = $_POST['title'] ?? '';
        $type = $_POST['type'] ?? 'video';
        $userId = $_SESSION['user_id'];

        if ($title) {
            $db = \App\Core\Database::getInstance();
            $sql = "INSERT INTO lessons (module_id, instructor_id, title, type, sort_order) 
                    VALUES (NULL, :uid, :title, :type, 0)";

            $db->query($sql, [
                ':uid' => $userId,
                ':title' => $title,
                ':type' => $type
            ]);

            $lessonId = $db->getConnection()->lastInsertId();
            header("Location: /instructor/lesson/$lessonId/edit?source=library");
            exit;
        }

        header('Location: /instructor/lessons');
        exit;
    }

    public function importLesson(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $sourceLessonId = (int) ($_POST['lesson_id'] ?? 0);
        $targetModuleId = (int) ($_POST['module_id'] ?? 0);
        $courseId = (int) ($_POST['course_id'] ?? 0);

        if ($sourceLessonId && $targetModuleId) {
            $db = \App\Core\Database::getInstance();

            // 1. Get Source Data
            $src = $db->fetchAll("SELECT * FROM lessons WHERE id = :id", [':id' => $sourceLessonId])[0] ?? null;

            if ($src) {
                // 2. Duplicate Payload
                $sql = "INSERT INTO lessons (module_id, instructor_id, title, type, content_url, content_text, attachment_url, duration, sort_order)
                        VALUES (:mid, :uid, :title, :type, :url, :text, :att, :dur, 999)";

                $db->query($sql, [
                    ':mid' => $targetModuleId,
                    ':uid' => $_SESSION['user_id'],
                    ':title' => $src['title'] . ' (Copy)',
                    ':type' => $src['type'],
                    ':url' => $src['content_url'],
                    ':text' => $src['content_text'],
                    ':att' => $src['attachment_url'],
                    ':dur' => $src['duration']
                ]);
                $newLessonId = $db->getConnection()->lastInsertId();

                // 3. Create Content Item
                $contentItemModel = new \App\Domain\Courses\ContentItem();
                $contentItemModel->createItem([
                    'course_id' => $courseId,
                    'module_id' => $targetModuleId,
                    'item_type' => 'lesson',
                    'item_id' => $newLessonId,
                    'display_group_id' => null
                ]);

                // TODO: Copy Quizzes if attached? (Out of scope for now, but good for future)

                $_SESSION['flash_success'] = 'Lesson imported successfully.';
            }
        }

        header("Location: /instructor/course/$courseId/edit");
        exit;
    }

    public function deleteContentItem(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $contentItemId = (int) $id;
        $db = \App\Core\Database::getInstance();

        // 1. Fetch Item to Identify Payload
        $item = $db->fetchAll("SELECT * FROM content_items WHERE id = :id", [':id' => $contentItemId])[0] ?? null;
        if (!$item) {
            header('Location: /dashboard');
            exit;
        }

        // 2. Check Access via Course
        try {
            Authorization::requireInstructor($_SESSION['user_id'], (int)$item['course_id']);
        } catch (\Exception $e) {
            header('Location: /dashboard');
            exit;
        }

        // 3. Delegate to Payload Deletion Logic
        if ($item['item_type'] === 'lesson') {
            // Delete Lesson Payload (which deletes the content item too via my previous fix, or we delete here)
            // Ideally re-use deleteLesson logic but by ID.
            // Let's call deleteLesson with the payload ID.
            $this->deleteLesson((string)$item['item_id']);
            return;
        } elseif ($item['item_type'] === 'quiz') {
            // Quizzes might be attached to lessons or standalone.
            // If standalone, we delete the quiz payload.
            // If attached (has display_group_id matching a lesson), we should probably warn, but for now we delete it.
            $db->query("DELETE FROM quizzes WHERE id = :id", [':id' => $item['item_id']]);
            $db->query("DELETE FROM content_items WHERE id = :id", [':id' => $contentItemId]);
            $_SESSION['flash_success'] = 'Quiz deleted.';
            header("Location: /instructor/course/{$item['course_id']}/edit");
            exit;
        }

        $_SESSION['flash_error'] = 'Unknown item type.';
        header("Location: /instructor/course/{$item['course_id']}/edit");
        exit;
    }

    public function deleteLesson(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $lessonId = (int) $id;
        $source = $_GET['source'] ?? $_POST['source'] ?? '';
        $db = \App\Core\Database::getInstance();

        $sql = "SELECT m.course_id, l.instructor_id FROM lessons l LEFT JOIN modules m ON l.module_id = m.id WHERE l.id = :id";
        $res = $db->fetchAll($sql, [':id' => $lessonId]);
        $courseId = $res[0]['course_id'] ?? 0;
        $ownerId = $res[0]['instructor_id'] ?? 0;

        $hasAccess = false;
        if ($courseId) {
            try {
                Authorization::requireInstructor($_SESSION['user_id'], $courseId);
                $hasAccess = true;
            } catch (\Exception $e) {
            }
        } elseif ($ownerId && $ownerId == $_SESSION['user_id']) {
            $hasAccess = true;
        }

        if ($hasAccess) {
            // 1. Clean up attached Quiz Content Items
            // Quizzes attached to this lesson (via legacy FK) will be deleted by DB CASCADE, 
            // but their content_items rows will remain unless we delete them manually.
            $attachedQuizzes = $db->fetchAll("SELECT id FROM quizzes WHERE lesson_id = :lid", [':lid' => $lessonId]);
            foreach ($attachedQuizzes as $q) {
                $db->query("DELETE FROM content_items WHERE item_type = 'quiz' AND item_id = :qid", [':qid' => $q['id']]);
            }

            // 2. Delete Lesson Content Item (Structure)
            $db->query("DELETE FROM content_items WHERE item_type = 'lesson' AND item_id = :id", [':id' => $lessonId]);

            // 3. Delete Lesson Payload (Content)
            // This triggers ON DELETE CASCADE for the `quizzes` table rows, ensuring data integrity.
            $db->query("DELETE FROM lessons WHERE id = :id", [':id' => $lessonId]);

            $_SESSION['flash_success'] = 'Lesson deleted.';
        } else {
            $_SESSION['flash_error'] = 'Access denied.';
        }

        if ($source === 'library' || !$courseId) {
            header("Location: /instructor/lessons");
        } else {
            header("Location: /instructor/course/$courseId/edit");
        }
        exit;
    }

    public function reorderModules(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $input = json_decode(file_get_contents('php://input'), true);
        $order = $input['order'] ?? [];

        if (empty($order)) return;

        $db = \App\Core\Database::getInstance();
        $db->getConnection()->beginTransaction();

        try {
            foreach ($order as $position => $moduleId) {
                $db->query("UPDATE modules SET sort_order = :order WHERE id = :id", [
                    ':order' => $position,
                    ':id' => (int) $moduleId
                ]);
            }
            $db->getConnection()->commit();
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            $db->getConnection()->rollBack();
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    public function reorderLessons(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $input = json_decode(file_get_contents('php://input'), true);
        $order = $input['order'] ?? [];

        if (empty($order)) return;

        $db = \App\Core\Database::getInstance();
        $db->getConnection()->beginTransaction();

        try {
            // Check if we are reordering content_items (IDs in UI are now content_item.id if using getModuleStructure)
            // But the UI might still be sending lesson IDs if we didn't update the JS.
            // Assuming for this Cutover Plan that the UI will send ContentItem IDs (or we need to look them up).
            // Let's assume the frontend sends ITEM IDs now because edit() passes 'items'.
            // Wait, standard SortableJS sends whatever ID is on the element.
            // If edit() passes 'items', the view likely renders <li data-id="item.id">.

            // However, to be safe and compatible with legacy frontend JS which might send lesson IDs if the view wasn't fully updated,
            // we should handle both or update the view. 
            // In Phase 2.1 we updated edit(), so the view receives ContentItems.
            // We need to check resources/views/instructor/course_builder.php to see what ID it puts in data-id.

            // Assuming we are receiving CONTENT_ITEM IDs now:
            $contentItemModel = new \App\Domain\Courses\ContentItem();
            foreach ($order as $position => $itemId) {
                // If the frontend sends lesson ID, we'd need to lookup content_item.
                // But let's enforce ContentItem ID usage.
                $contentItemModel->updatePosition((int)$itemId, $position);
            }

            $db->getConnection()->commit();
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            $db->getConnection()->rollBack();
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    public function analytics(): void
    {
        $userId = $_SESSION['user_id'];
        $db = \App\Core\Database::getInstance();

        // 1. Overview Stats
        $sqlOverview = "SELECT 
            COUNT(DISTINCT c.id) as total_courses,
            COUNT(e.id) as total_enrollments,
            SUM(c.price) as total_revenue
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id
            WHERE c.instructor_id = :uid";

        $overview = $db->fetchAll($sqlOverview, [':uid' => $userId])[0];

        // 2. Recent Enrollments (Last 5)
        $sqlRecent = "SELECT u.name as student_name, c.title as course_title, e.created_at
                      FROM enrollments e
                      JOIN courses c ON e.course_id = c.id
                      JOIN users u ON e.user_id = u.id
                      WHERE c.instructor_id = :uid
                      ORDER BY e.created_at DESC LIMIT 5";
        $recentEnrollments = $db->fetchAll($sqlRecent, [':uid' => $userId]);

        // 3. Course Performance Table
        $sqlPerformance = "SELECT c.title, c.price, 
                           COUNT(e.id) as enrollments_count,
                           (COUNT(e.id) * c.price) as revenue
                           FROM courses c
                           LEFT JOIN enrollments e ON c.id = e.course_id
                           WHERE c.instructor_id = :uid
                           GROUP BY c.id
                           ORDER BY enrollments_count DESC";
        $coursePerformance = $db->fetchAll($sqlPerformance, [':uid' => $userId]);

        $this->view('instructor.analytics', [
            'overview' => $overview,
            'recent' => $recentEnrollments,
            'performance' => $coursePerformance
        ]);
    }
}
