<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Domain\Users\User;
use App\Http\Middleware\AuthMiddleware;
use App\Core\Database;

class ProfileController extends Controller
{
    public function __construct()
    {
        (new AuthMiddleware())->handle();
    }

    public function index(): void
    {
        $userModel = new User();
        $user = $userModel->find((int)$_SESSION['user_id']);

        if (!$user) {
            header('Location: /logout');
            exit;
        }

        // Fetch some stats for the professional look
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'];
        
        $stats = [
            'enrolled_courses' => $db->fetchAll("SELECT COUNT(*) as count FROM enrollments WHERE user_id = :uid", [':uid' => $userId])[0]['count'] ?? 0,
            'completed_lessons' => $db->fetchAll("SELECT COUNT(*) as count FROM completed_lessons WHERE user_id = :uid", [':uid' => $userId])[0]['count'] ?? 0,
        ];

        $this->view('profile', ['user' => $user, 'stats' => $stats]);
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /profile');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $userModel = new User();
        $user = $userModel->find($userId);

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $currentPassword = $_POST['current_password'] ?? '';

        $errors = [];

        // Basic validation
        if (empty($name)) $errors[] = "Name is required.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";

        // Check email uniqueness if changed
        if ($email !== $user['email']) {
            if ($userModel->findByEmail($email)) {
                $errors[] = "Email is already taken by another user.";
            }
        }

        // Password update logic
        $updateData = ['name' => $name, 'email' => $email];
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                $errors[] = "Current password is required to set a new one.";
            } elseif (!password_verify($currentPassword, $user['password'])) {
                $errors[] = "Incorrect current password.";
            } else {
                if (strlen($newPassword) < 6) {
                    $errors[] = "New password must be at least 6 characters.";
                } else {
                    $updateData['password'] = password_hash($newPassword, PASSWORD_BCRYPT);
                }
            }
        }

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode(' ', $errors);
            header('Location: /profile');
            exit;
        }

        if ($userModel->update($userId, $updateData)) {
            $_SESSION['user_name'] = $name;
            $_SESSION['flash_success'] = "Profile updated successfully!";
        } else {
            $_SESSION['flash_error'] = "Something went wrong. Please try again.";
        }

        header('Location: /profile');
        exit;
    }
}
