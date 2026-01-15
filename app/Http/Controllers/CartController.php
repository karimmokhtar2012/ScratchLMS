<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Domain\Courses\Course;
use App\Http\Middleware\AuthMiddleware;

use App\Policies\Authorization;

class CartController extends Controller
{
    public function __construct()
    {
        // Cart is available to everyone, but checkout requires auth
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Show Cart Page
     */
    public function index(): void
    {
        $cart = $_SESSION['cart'] ?? [];
        $courses = [];
        $total = 0;

        if (!empty($cart)) {
            $courseModel = new Course();
            $placeholders = implode(',', array_fill(0, count($cart), '?'));
            $db = \App\Core\Database::getInstance();
            $sql = "SELECT * FROM courses WHERE id IN ($placeholders)";
            $courses = $db->fetchAll($sql, array_values($cart));

            foreach ($courses as $course) {
                $total += $course['price'];
            }
        }

        $this->view('cart.index', [
            'courses' => $courses,
            'total' => $total
        ]);
    }

    /**
     * AJAX/Form: Add item to cart
     */
    public function add(string $id): void
    {
        $courseId = (int) $id;
        
        // 1. Validation
        $courseModel = new Course();
        $course = $courseModel->find($courseId);

        if (!$course || ($course['status'] ?? '') !== 'published') {
            $_SESSION['flash_error'] = 'Course is not available.';
            header('Location: /courses');
            exit;
        }

        // 2. Check Enrollment (if logged in)
        if (isset($_SESSION['user_id'])) {
            if (Authorization::isEnrolled($_SESSION['user_id'], $courseId)) {
                $_SESSION['flash_info'] = 'You are already enrolled in this course.';
                header('Location: /course/' . $courseId);
                exit;
            }
        }
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (!in_array($courseId, $_SESSION['cart'])) {
            $_SESSION['cart'][] = $courseId;
            $_SESSION['flash_success'] = 'Course added to cart.';
        } else {
            $_SESSION['flash_error'] = 'Course is already in your cart.';
        }

        // Redirect back or to cart
        $return = $_GET['return'] ?? '/courses';
        header('Location: ' . $return);
        exit;
    }

    /**
     * AJAX/Form: Remove item from cart
     */
    public function remove(string $id): void
    {
        $courseId = (int) $id;
        
        if (isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array_filter($_SESSION['cart'], fn($cid) => $cid != $courseId);
            $_SESSION['flash_success'] = 'Item removed from cart.';
        }

        header('Location: /cart');
        exit;
    }
}
