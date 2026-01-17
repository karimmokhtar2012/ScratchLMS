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
            $uploadDir = __DIR__ . '/../../public/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $fileName = uniqid() . '_' . basename($_FILES['thumbnail']['name']);
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
        $moduleModel = new Module();

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
            $module['lessons'] = $moduleModel->lessons($module['id']);
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

        $this->view('instructor.edit_course', ['course' => $course]);
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
            $uploadDir = __DIR__ . '/../../public/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $fileName = uniqid() . '_' . basename($_FILES['thumbnail']['name']);
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

        $completedCount = $db->fetchAll("SELECT COUNT(*) as count 
                                         FROM completed_lessons cl 
                                         JOIN lessons l ON cl.lesson_id = l.id 
                                         JOIN modules m ON l.module_id = m.id 
                                         WHERE m.course_id = :cid AND cl.user_id = :uid", 
                                         [':cid' => $courseId, ':uid' => $studentId])[0]['count'];

        $requests = $db->fetchAll("SELECT ar.*, l.title as quiz_title 
                                   FROM attempt_requests ar 
                                   JOIN quizzes q ON ar.quiz_id = q.id 
                                   JOIN lessons l ON q.lesson_id = l.id
                                   WHERE ar.user_id = :uid AND ar.status = 'pending'", 
                                   [':uid' => $studentId]);

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
        $returnPath = (is_string($returnPath) && str_starts_with($returnPath, '/')) ? $returnPath : $defaultReturn;

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
            $uploadDir = __DIR__ . '/../../public/uploads/resources/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $fileName = uniqid() . '_' . basename($_FILES['attachment']['name']);
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $fileName)) {
                $attachmentUrl = '/uploads/resources/' . $fileName;
            }
        }

        if ($title && $moduleId) {
             $db = \App\Core\Database::getInstance();
             
             // Verify module ownership (optional but good for strict security if not relying on UI trust)
             // Check if module belongs to a course owned by user
             $moduleCheck = $db->fetchAll("SELECT c.instructor_id FROM modules m JOIN courses c ON m.course_id = c.id WHERE m.id = :mid", [':mid' => $moduleId])[0] ?? null;
             
             if (!$moduleCheck || $moduleCheck['instructor_id'] != $_SESSION['user_id']) {
                 // Check admin role if necessary, or just fail safe
                 $roles = $_SESSION['user_roles'] ?? [];
                 if (!in_array('admin', $roles)) {
                     $_SESSION['flash_error'] = 'Access denied to this module.';
                     header('Location: /dashboard');
                     exit;
                 }
             }

             $sql = "INSERT INTO lessons (module_id, instructor_id, title, type, content_url, content_text, attachment_url, duration) 
                     VALUES (:mid, :uid, :title, :type, :url, :text, :att, :dur)";
             
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
            $videoDir = $uploadDir . 'videos/';
            if (!is_dir($videoDir)) mkdir($videoDir, 0755, true);
            $fileName = uniqid() . '_' . basename($_FILES['video_file']['name']);
            if (move_uploaded_file($_FILES['video_file']['tmp_name'], $videoDir . $fileName)) {
                $contentUrl = '/uploads/videos/' . $fileName;
            }
        }

        $db = \App\Core\Database::getInstance();
        $currentLesson = $db->fetchAll("SELECT attachment_url, content_url FROM lessons WHERE id = :id", [':id' => $lessonId])[0] ?? null;
        
        if (!$currentLesson) {
            header('Location: /dashboard');
            exit;
        }
        
        if (empty($contentUrl) && $type === 'video') $contentUrl = $currentLesson['content_url']; // Keep old if empty and no file

        $attachmentUrl = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $resDir = $uploadDir . 'resources/';
            if (!is_dir($resDir)) mkdir($resDir, 0755, true);
            $fileName = uniqid() . '_' . basename($_FILES['attachment']['name']);
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $resDir . $fileName)) {
                $attachmentUrl = '/uploads/resources/' . $fileName;
            }
        } else {
            $attachmentUrl = $currentLesson['attachment_url'];
        }

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

        if ($courseId) {
            $flow = $db->fetchAll("SELECT content_flow FROM courses WHERE id = :cid", [':cid' => $courseId])[0]['content_flow'] ?? 'free';
            if (in_array($flow, ['drip_time', 'drip_progress', 'drip_combined'], true)) {
                $unlockDays = isset($_POST['unlock_after_days']) ? (int) $_POST['unlock_after_days'] : null;
                $unlockLesson = isset($_POST['unlock_after_lesson_id']) ? (int) $_POST['unlock_after_lesson_id'] : null;
                $unlockPercent = isset($_POST['unlock_after_percent']) ? (int) $_POST['unlock_after_percent'] : null;
                $hasRule = ($unlockDays || $unlockLesson || $unlockPercent);

                if ($hasRule) {
                    $db->query("INSERT INTO lesson_drip_rules (lesson_id, unlock_after_days, unlock_after_lesson_id, unlock_after_percent)
                                VALUES (:lid, :days, :prev, :pct)
                                ON CONFLICT(lesson_id) DO UPDATE SET unlock_after_days = :days, unlock_after_lesson_id = :prev, unlock_after_percent = :pct", [
                        ':lid' => $lessonId,
                        ':days' => $unlockDays,
                        ':prev' => $unlockLesson ?: null,
                        ':pct' => $unlockPercent
                    ]);
                } else {
                    $db->query("DELETE FROM lesson_drip_rules WHERE lesson_id = :lid", [':lid' => $lessonId]);
                }
            }
        }

        $_SESSION['flash_success'] = 'Lesson updated successfully.';
        
        // Redirect based on context
        if ($courseId) {
            header("Location: /learn/$courseId/lesson/$lessonId"); // Return to Player (or builder?)
        } else {
            // Library lesson
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
        $exists = $db->fetchAll("SELECT id FROM enrollments WHERE user_id = :uid AND course_id = :cid", 
                                [':uid' => $userId, ':cid' => $courseId]);
        
        if ($exists) {
            $_SESSION['flash_error'] = "User is already enrolled in this course.";
        } else {
            // 3. Enroll
            $db->query("INSERT INTO enrollments (user_id, course_id) VALUES (:uid, :cid)", 
                       [':uid' => $userId, ':cid' => $courseId]);
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
        $certificateApprovals = $db->fetchAll($sqlCerts, $isPrivileged ? [] : [':uid' => $userId]);

        $this->view('instructor.pending_requests', [
            'attemptRequests' => $attemptRequests,
            'certificateApprovals' => $certificateApprovals
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
        header('Location: /instructor/requests#certificates');
        exit;
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
        $returnPath = (is_string($returnPath) && str_starts_with($returnPath, '/')) ? $returnPath : '/instructor/requests#certificates';

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
        $row = $db->fetchAll("SELECT g.id, g.attempt_id, g.question_id, c.id as course_id, l.id as lesson_id, qu.points, qa.user_id as student_id
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
            $alreadyCompleted = $db->fetchAll("SELECT 1 FROM completed_lessons WHERE user_id = :uid AND lesson_id = :lid LIMIT 1", [
                ':uid' => $studentId,
                ':lid' => $lessonId
            ]);
            if (empty($alreadyCompleted)) {
                $db->query("INSERT OR IGNORE INTO completed_lessons (user_id, lesson_id) VALUES (:uid, :lid)", [
                    ':uid' => $studentId,
                    ':lid' => $lessonId
                ]);
            }

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
            case 'date_asc': $sql .= " ORDER BY l.created_at ASC"; break;
            case 'name_asc': $sql .= " ORDER BY l.title ASC"; break;
            case 'name_desc': $sql .= " ORDER BY l.title DESC"; break;
            default: $sql .= " ORDER BY l.id DESC"; // date_desc (using ID as proxy for simplicity or created_at if reliable)
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
            
            $module = $db->fetchAll("SELECT c.instructor_id FROM modules m JOIN courses c ON m.course_id = c.id WHERE m.id = :id", [':id' => $moduleId])[0] ?? null;

            if ($lesson && $module && $lesson['instructor_id'] == $userId && $module['instructor_id'] == $userId) {
                $db->query("UPDATE lessons SET module_id = :mid WHERE id = :lid", [
                    ':mid' => $moduleId,
                    ':lid' => $lessonId
                ]);
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
                // 2. Duplicate
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
                
                $_SESSION['flash_success'] = 'Lesson imported successfully.';
            }
        }

        header("Location: /instructor/course/$courseId/edit");
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
            } catch (\Exception $e) {}
        } elseif ($ownerId && $ownerId == $_SESSION['user_id']) {
            $hasAccess = true;
        }

        if ($hasAccess) {
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
            foreach ($order as $position => $lessonId) {
                $db->query("UPDATE lessons SET sort_order = :order WHERE id = :id", [
                    ':order' => $position,
                    ':id' => (int) $lessonId
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
