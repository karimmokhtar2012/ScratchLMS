<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Domain\Orders\Order;
use App\Domain\Orders\OrderItem;
use App\Domain\Courses\Course;
use App\Domain\Orders\Coupon;
use App\Http\Middleware\AuthMiddleware;
use App\Services\CourseProgressService;

class CheckoutController extends Controller
{
    public function __construct()
    {
        (new AuthMiddleware())->handle();
    }

    public function index(): void
    {
        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) {
            header('Location: /courses');
            exit;
        }

        $courseModel = new Course();
        $placeholders = implode(',', array_fill(0, count($cart), '?'));
        $db = \App\Core\Database::getInstance();
        $sql = "SELECT * FROM courses WHERE id IN ($placeholders)";
        $courses = $db->fetchAll($sql, array_values($cart));

        $total = 0;
        foreach ($courses as $course) {
            $total += $course['price'];
        }

        $this->view('checkout.index', [
            'courses' => $courses,
            'total' => $total
        ]);
    }

    public function process(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /checkout');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $cart = $_SESSION['cart'] ?? [];

        if (empty($cart)) {
            header('Location: /courses');
            exit;
        }

        $db = \App\Core\Database::getInstance();
        $courseModel = new Course();
        $orderModel = new Order();
        $orderItemModel = new OrderItem();

        // 1. Calculate Total & Validate
        $placeholders = implode(',', array_fill(0, count($cart), '?'));
        $courses = $db->fetchAll("SELECT * FROM courses WHERE id IN ($placeholders)", array_values($cart));
        
        $total = 0;
        foreach ($courses as $c) {
            // Validation: Must be published
            if ($c['status'] !== 'published') {
                $_SESSION['flash_error'] = "One or more courses in your cart are not available for purchase. Please update your cart.";
                header('Location: /cart');
                exit;
            }
            $total += $c['price'];
        }

        try {
            $db->getConnection()->beginTransaction();

            // 2. Create Order
            $orderId = $orderModel->create([
                'user_id' => $userId,
                'total_amount' => $total,
                'status' => 'paid', // Simulate immediate success for now
                'payment_method' => 'Credit Card',
                'transaction_id' => 'TXN_' . uniqid()
            ]);

            // 3. Create Order Items & Trigger Enrollments
            foreach ($courses as $course) {
                $orderItemModel->create([
                    'order_id' => $orderId,
                    'course_id' => $course['id'],
                    'price_at_purchase' => $course['price']
                ]);

                // Enroll User (Directly since order is 'paid') - database-agnostic
                $db->insertOrIgnore('enrollments', [
                    'user_id' => $userId,
                    'course_id' => $course['id']
                ]);
            }

            $db->getConnection()->commit();

            // 4. Clear Cart
            unset($_SESSION['cart']);
            $_SESSION['flash_success'] = 'Payment successful! Welcome to your new courses. 🎉';
            header('Location: /my-courses');
            exit;

        } catch (\Exception $e) {
            $db->getConnection()->rollBack();
            $_SESSION['flash_error'] = 'Payment processing failed: ' . $e->getMessage();
            header('Location: /checkout');
            exit;
        }
    }
}
