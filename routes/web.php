<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizRunnerController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController; // Import Home
use App\Http\Controllers\LearnController; // Import LearnController

// Home / Landing
$router->get('/', [HomeController::class, 'index']); // Changed from Auth/Login to Home

// Language Switcher
$router->get('/language/([^/]+)', [HomeController::class, 'setLanguage']);
$router->get('/flash-test', [HomeController::class, 'flashTest']);

// Auth Routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/logout', [AuthController::class, 'logout']);

// Notification API Routes
use App\Http\Controllers\NotificationController;
$router->get('/api/notifications', [NotificationController::class, 'index']);
$router->post('/api/notifications/read/([^/]+)', [NotificationController::class, 'markRead']);
$router->post('/api/notifications/read-all', [NotificationController::class, 'markAllRead']);

// Onboarding Routes
use App\Http\Controllers\OnboardingController;
$router->get('/onboarding', [OnboardingController::class, 'index']);
$router->post('/onboarding/save', [OnboardingController::class, 'save']);

// Dashboard Routes
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->post('/dashboard/switch-mode', [DashboardController::class, 'switchMode']);
$router->post('/dashboard/dismiss-tutorial', [DashboardController::class, 'dismissTutorial']);

// Instructor Routes
use App\Http\Controllers\QuestionBankController;
$router->get('/instructor/lessons', [InstructorController::class, 'lessonLibrary']);
$router->post('/instructor/lesson/create-library', [InstructorController::class, 'createLibraryLesson']);
$router->post('/instructor/lesson/import', [InstructorController::class, 'importLesson']);
$router->post('/instructor/lesson/add-to-course', [InstructorController::class, 'addToCourse']);
$router->get('/instructor/course/new', [InstructorController::class, 'create']);
$router->post('/instructor/course/save', [InstructorController::class, 'store']);
$router->get('/instructor/analytics', [InstructorController::class, 'analytics']);
$router->get('/instructor/question-banks', [QuestionBankController::class, 'index']);
$router->post('/instructor/question-banks/store', [QuestionBankController::class, 'store']);
$router->get('/instructor/question-bank/([^/]+)', [QuestionBankController::class, 'show']);
$router->post('/instructor/question-bank/delete/([^/]+)', [QuestionBankController::class, 'delete']);
$router->post('/instructor/question-bank/question/add', [QuestionBankController::class, 'storeQuestion']);
$router->post('/instructor/question-bank/question/delete/([^/]+)', [QuestionBankController::class, 'deleteQuestion']);
$router->get('/instructor/course/{id}/edit', [InstructorController::class, 'edit']);
$router->get('/instructor/course/{id}/settings', [InstructorController::class, 'settings']);
$router->get('/instructor/course/{id}/students', [InstructorController::class, 'students']);
$router->get('/instructor/course/([^/]+)/student/([^/]+)', [InstructorController::class, 'studentActivity']);
$router->post('/instructor/course/{id}/update', [InstructorController::class, 'update']);
$router->post('/instructor/module/add', [InstructorController::class, 'addModule']);
$router->post('/instructor/module/update/([^/]+)', [InstructorController::class, 'updateModule']);
$router->post('/instructor/lesson/add', [InstructorController::class, 'addLesson']);
$router->post('/instructor/module/delete/([^/]+)', [InstructorController::class, 'deleteModule']);
$router->post('/instructor/lesson/delete/([^/]+)', [InstructorController::class, 'deleteLesson']);
$router->post('/instructor/course/([^/]+)/enroll-student', [InstructorController::class, 'enrollStudent']);
$router->get('/instructor/requests', [InstructorController::class, 'pendingRequests']);
$router->post('/instructor/modules/reorder', [InstructorController::class, 'reorderModules']);
$router->post('/instructor/lessons/reorder', [InstructorController::class, 'reorderLessons']);
$router->get('/instructor/lesson/([^/]+)/edit', [InstructorController::class, 'editLesson']);
$router->post('/instructor/lesson/([^/]+)/update', [InstructorController::class, 'updateLesson']);
$router->get('/instructor/certificates/approvals', [InstructorController::class, 'certificateApprovals']);
$router->post('/instructor/certificates/issue', [InstructorController::class, 'issueCertificate']);

// Quiz Routes
$router->get('/instructor/lesson/([^/]+)/quiz', [QuizController::class, 'build']);
$router->post('/instructor/quiz/settings', [QuizController::class, 'updateSettings']);
$router->post('/instructor/quiz/import', [QuizController::class, 'importFromBank']);
$router->post('/instructor/question/add', [QuizController::class, 'addQuestion']);
$router->post('/instructor/question/delete/([^/]+)', [QuizController::class, 'deleteQuestion']);

// Upload Routes
$router->post('/upload/chunk', [UploadController::class, 'uploadChunk']);

// Learning Player Routes
$router->get('/learn/([^/]+)', [LearnController::class, 'show']); // Resume Course
$router->get('/learn/([^/]+)/lesson/([^/]+)', [LearnController::class, 'show']); // Specific Lesson
$router->post('/learn/track', [LearnController::class, 'track']);

// Quiz Runner (Student)
$router->get('/learn/quiz/intro/([^/]+)', [QuizRunnerController::class, 'intro']); // Quiz ID
$router->get('/learn/quiz/start/([^/]+)', [QuizRunnerController::class, 'start']); // Quiz ID
$router->get('/learn/quiz/attempt/([^/]+)', [QuizRunnerController::class, 'show']); // Attempt ID
$router->post('/learn/quiz/save', [QuizRunnerController::class, 'saveAnswer']);
$router->post('/learn/quiz/submit/([^/]+)', [QuizRunnerController::class, 'submit']);
$router->get('/learn/quiz/result/([^/]+)', [QuizRunnerController::class, 'result']);
$router->get('/learn/quiz/review/([^/]+)', [QuizRunnerController::class, 'review']);
$router->post('/learn/quiz/request-reset', [QuizRunnerController::class, 'requestReset']);

// Instructor Action Routes
$router->post('/instructor/request/([^/]+)/approve', [InstructorController::class, 'handleRequest', 'approve']);
$router->post('/instructor/request/([^/]+)/reject', [InstructorController::class, 'handleRequest', 'reject']);

// Public Course Routes
$router->get('/courses', [CourseController::class, 'index']);
$router->get('/my-courses', [CourseController::class, 'myCourses']);
$router->get('/course/([^/]+)', [CourseController::class, 'show']); // Regex for ID
$router->post('/course/([^/]+)/enroll', [CourseController::class, 'enroll']);

// Cart Routes
$router->get('/cart', [CartController::class, 'index']);
$router->post('/cart/add/([^/]+)', [CartController::class, 'add']);
$router->post('/cart/remove/([^/]+)', [CartController::class, 'remove']);

// Checkout Routes
$router->get('/checkout', [CheckoutController::class, 'index']);
$router->post('/checkout/process', [CheckoutController::class, 'process']);

// Certificate Routes
use App\Http\Controllers\CertificateController;
$router->get('/certificates', [CertificateController::class, 'index']);
$router->get('/certificate/([^/]+)', [CertificateController::class, 'show']);

// Note Routes
use App\Http\Controllers\NoteController;
$router->get('/notes', [NoteController::class, 'index']);
$router->get('/notes/lesson/([^/]+)', [NoteController::class, 'list']);
$router->post('/notes/store', [NoteController::class, 'store']);
$router->post('/notes/delete/([^/]+)', [NoteController::class, 'delete']);

// Admin Routes
use App\Http\Controllers\AdminController;
$router->get('/admin', [AdminController::class, 'index']);
$router->get('/admin/users', [AdminController::class, 'users']);
$router->post('/admin/users/update-role', [AdminController::class, 'updateUserRole']);
$router->post('/admin/users/toggle-switch', [AdminController::class, 'toggleSwitchPermission']);
$router->post('/admin/users/delete/([^/]+)', [AdminController::class, 'deleteUser']);
$router->get('/admin/users/enrollments/([^/]+)', [AdminController::class, 'getUserEnrollments']);
$router->post('/admin/users/enroll', [AdminController::class, 'enrollUser']);
$router->post('/admin/users/unenroll', [AdminController::class, 'unenrollUser']);
$router->post('/admin/users/reset-progress', [AdminController::class, 'resetProgress']);
$router->post('/admin/users/bulk-delete', [AdminController::class, 'bulkDeleteUsers']);
$router->post('/admin/users/bulk-role', [AdminController::class, 'bulkUpdateRole']);
$router->post('/admin/users/bulk-switch', [AdminController::class, 'bulkToggleSwitch']);
$router->post('/admin/users/bulk-enroll', [AdminController::class, 'bulkEnroll']);
$router->post('/admin/users/bulk-unenroll', [AdminController::class, 'bulkUnenroll']);
$router->post('/admin/users/bulk-reset', [AdminController::class, 'bulkResetProgress']);
$router->get('/admin/courses', [AdminController::class, 'courses']);
$router->post('/admin/courses/delete/([^/]+)', [AdminController::class, 'deleteCourse']);
$router->post('/admin/courses/bulk-delete', [AdminController::class, 'bulkDeleteCourses']);
$router->post('/admin/courses/bulk-publish', [AdminController::class, 'bulkPublishCourses']);
$router->post('/admin/courses/bulk-unpublish', [AdminController::class, 'bulkUnpublishCourses']);
$router->post('/admin/courses/bulk-price', [AdminController::class, 'bulkSetCoursePrice']);
$router->post('/admin/courses/bulk-instructor', [AdminController::class, 'bulkSetCourseInstructor']);
