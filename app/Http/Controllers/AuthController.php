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
        // Rate limiting: 5 attempts per 15 minutes per IP
        $rateLimiter = new \App\Services\RateLimiter();
        $rateLimitKey = \App\Services\RateLimiter::key('login');
        $maxAttempts = 5;
        $decayMinutes = 15;

        // Check if already locked out
        if ($rateLimiter->tooManyAttempts($rateLimitKey, $maxAttempts, $decayMinutes)) {
            $retryAfter = $rateLimiter->availableIn($rateLimitKey, $decayMinutes);
            $minutes = ceil($retryAfter / 60);

            // Log blocked attempt
            $securityLogger = new \App\Services\SecurityLogger();
            $securityLogger->logAuthEvent('login_blocked', null, [
                'email' => $_POST['email'] ?? 'unknown',
                'lockout_minutes' => $minutes
            ]);

            $this->view('auth.login', [
                'error' => "Too many login attempts. Please try again in {$minutes} minute(s).",
                'locked' => true
            ]);
            return;
        }

        // Validate CSRF token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!hash_equals(\App\Core\Csrf::getToken(), $csrfToken)) {
            $this->view('auth.login', ['error' => 'Invalid security token. Please try again.']);
            return;
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Use UserService to get user with roles in single query (eliminates N+1)
        $userService = new \App\Services\UserService();
        $user = $userService->getUserByEmailWithRoles($email);

        if ($user && password_verify($password, $user['password'])) {
            // Success - clear rate limit
            $rateLimiter->clear($rateLimitKey);

            // Log successful login
            $securityLogger = new \App\Services\SecurityLogger();
            $securityLogger->logAuthEvent('login_success', $user['id'], ['email' => $email]);

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['admin_flash_success'], $_SESSION['admin_flash_error']);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['can_switch_role'] = $user['can_switch_role'] ?? 0;
            $_SESSION['user_roles'] = $user['roles']; // Already loaded via single query

            // Check Onboarding Status
            $isOnboarded = $user['is_onboarded'] ?? 0;
            if (!$isOnboarded) {
                header('Location: /onboarding');
                exit;
            }

            header('Location: /');
            exit;
        }

        // Failed login - record attempt
        $rateLimiter->attempt($rateLimitKey, $maxAttempts, $decayMinutes);
        $remaining = $rateLimiter->remainingAttempts($rateLimitKey, $maxAttempts, $decayMinutes);

        // Log failed login
        $securityLogger = new \App\Services\SecurityLogger();
        $securityLogger->logAuthEvent('login_failed', null, [
            'email' => $email,
            'attempts_remaining' => $remaining
        ]);

        $error = 'Invalid credentials';
        if ($remaining > 0 && $remaining <= 2) {
            $error .= ". {$remaining} attempt(s) remaining before lockout.";
        }

        $this->view('auth.login', ['error' => $error]);
    }

    public function showRegister(): void
    {
        $this->view('auth.register');
    }

    public function register(): void
    {
        // Rate limiting: 3 attempts per 60 minutes per IP
        $rateLimiter = new \App\Services\RateLimiter();
        $rateLimitKey = \App\Services\RateLimiter::key('register');
        $maxAttempts = 3;
        $decayMinutes = 60;

        // Check if already locked out
        if ($rateLimiter->tooManyAttempts($rateLimitKey, $maxAttempts, $decayMinutes)) {
            $retryAfter = $rateLimiter->availableIn($rateLimitKey, $decayMinutes);
            $minutes = ceil($retryAfter / 60);
            $this->view('auth.register', [
                'error' => "Too many registration attempts. Please try again in {$minutes} minute(s).",
                'locked' => true
            ]);
            return;
        }

        // Record attempt
        $rateLimiter->attempt($rateLimitKey, $maxAttempts, $decayMinutes);

        // Validate CSRF token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!hash_equals(\App\Core\Csrf::getToken(), $csrfToken)) {
            $this->view('auth.register', ['error' => 'Invalid security token. Please try again.']);
            return;
        }

        // Use UserService for registration (handles validation, role assignment, notifications)
        $userService = new \App\Services\UserService();
        $userId = $userService->register([
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? ''
        ]);

        if ($userId) {
            header('Location: /login');
            exit;
        }

        $this->view('auth.register', ['error' => 'Registration failed. Please check your input and try again.']);
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
