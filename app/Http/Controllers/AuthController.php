<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Domain\Users\User;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth.login');
    }

    public function login(): void
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['admin_flash_success'], $_SESSION['admin_flash_error']);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['can_switch_role'] = $user['can_switch_role'] ?? 0;
            
            // Fetch and store roles
            $db = \App\Core\Database::getInstance();
            $sqlRoles = "SELECT r.slug FROM roles r 
                         JOIN role_user ru ON r.id = ru.role_id 
                         WHERE ru.user_id = :uid";
            $roles = $db->fetchAll($sqlRoles, [':uid' => $user['id']]);
            $_SESSION['user_roles'] = array_column($roles, 'slug');

            // Check Onboarding Status
            $isOnboarded = $user['is_onboarded'] ?? 0;
            if (!$isOnboarded) {
                header('Location: /onboarding');
                exit;
            }

            header('Location: /');
            exit;
        }

        $this->view('auth.login', ['error' => 'Invalid credentials']);
    }

    public function showRegister(): void
    {
        $this->view('auth.register');
    }

    public function register(): void
    {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $this->view('auth.register', ['error' => 'All fields are required']);
            return;
        }

        $userModel = new User();
        if ($userModel->findByEmail($email)) {
             $this->view('auth.register', ['error' => 'Email already registered']);
             return;
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $userId = $userModel->create(['name' => $name, 'email' => $email, 'password' => $hashedPassword]);
        
        if ($userId) {
             // Assign 'student' role by default
             $db = \App\Core\Database::getInstance();
             $sqlRole = "SELECT id FROM roles WHERE slug = 'student'";
             $resRole = $db->fetchAll($sqlRole);
             $studentRoleId = $resRole[0]['id'] ?? 3; // Default to 3 (student) if not found

             $db->query("INSERT INTO role_user (user_id, role_id) VALUES (:uid, :rid)", [
                 ':uid' => $userId,
                 ':rid' => $studentRoleId
             ]);

             // Welcome Notification
             (new \App\Domain\Notifications\Notification())->create([
                 'user_id' => $userId,
                 'type' => 'system',
                 'title' => 'Welcome to ScratchLMS',
                 'message' => "We're glad you're here! Start exploring courses now.",
                 'link' => '/courses'
             ]);

             header('Location: /login');
             exit;
        }

        $this->view('auth.register', ['error' => 'Registration failed']);
    }
    
    public function logout(): void
    {
         if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['admin_flash_success'], $_SESSION['admin_flash_error']);
        session_destroy();
        header('Location: /login');
        exit;
    }
}
