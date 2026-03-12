<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\SecurityLogger;

/**
 * Middleware
 * 
 * Centralized authorization checks for controllers.
 * Provides consistent authentication and role verification.
 */
class Middleware
{
    /**
     * Require authenticated user
     * Redirects to login if not authenticated
     */
    public static function auth(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            // Store intended URL for redirect after login
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: /login');
            exit;
        }
    }

    /**
     * Require guest (not authenticated)
     * Redirects to dashboard if already authenticated
     */
    public static function guest(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }
    }

    /**
     * Require specific role
     * 
     * @param string $role Required role slug
     * @param bool $strict If true, user must have EXACTLY this role (not higher)
     */
    public static function role(string $role, bool $strict = false): void
    {
        self::auth();

        $userRoles = $_SESSION['user_roles'] ?? [];

        // Role hierarchy: admin > master_instructor > instructor > student
        $roleHierarchy = ['admin', 'master_instructor', 'instructor', 'student'];

        if ($strict) {
            // Strict mode: must have exact role
            if (!in_array($role, $userRoles, true)) {
                self::denyAccess("Required role: {$role}");
            }
        } else {
            // Hierarchy mode: check if user has required role or higher
            $requiredIndex = array_search($role, $roleHierarchy);

            if ($requiredIndex === false) {
                // Unknown role, fall back to exact match
                if (!in_array($role, $userRoles, true)) {
                    self::denyAccess("Required role: {$role}");
                }
            } else {
                $hasAccess = false;
                foreach ($userRoles as $userRole) {
                    $userIndex = array_search($userRole, $roleHierarchy);
                    if ($userIndex !== false && $userIndex <= $requiredIndex) {
                        $hasAccess = true;
                        break;
                    }
                }

                if (!$hasAccess) {
                    self::denyAccess("Required role: {$role}");
                }
            }
        }
    }

    /**
     * Require admin role
     */
    public static function admin(): void
    {
        self::auth();

        $userRoles = $_SESSION['user_roles'] ?? [];

        if (!in_array('admin', $userRoles, true)) {
            self::denyAccess('Admin access required');
        }
    }

    /**
     * Require instructor role (or higher)
     */
    public static function instructor(): void
    {
        self::role('instructor');
    }

    /**
     * Require master instructor role (or higher)
     */
    public static function masterInstructor(): void
    {
        self::role('master_instructor');
    }

    /**
     * Verify resource ownership
     * 
     * @param int $ownerId The owner ID of the resource
     * @param string $resource Description of the resource (for logging)
     */
    public static function owner(int $ownerId, string $resource = 'resource'): void
    {
        self::auth();

        $userId = $_SESSION['user_id'];

        // Admins can access any resource
        $userRoles = $_SESSION['user_roles'] ?? [];
        if (in_array('admin', $userRoles, true)) {
            return;
        }

        if ($userId !== $ownerId) {
            self::denyAccess("You do not own this {$resource}");
        }
    }

    /**
     * Deny access with 403 response
     * 
     * @param string $reason Reason for denial (for logging)
     */
    private static function denyAccess(string $reason): void
    {
        // Log the access denial
        $securityLogger = new SecurityLogger();
        $securityLogger->logAccessDenied(
            $_SERVER['REQUEST_URI'] ?? 'unknown',
            $_SESSION['user_id'] ?? null,
            ['reason' => $reason]
        );

        http_response_code(403);

        if (file_exists(__DIR__ . '/../../resources/views/errors/403.php')) {
            require __DIR__ . '/../../resources/views/errors/403.php';
        } else {
            echo '<h1>403 Forbidden</h1><p>You do not have permission to access this resource.</p>';
        }

        exit;
    }

    /**
     * Check if current user has a specific role (without denying access)
     * 
     * @param string $role Role to check
     * @return bool True if user has the role
     */
    public static function hasRole(string $role): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userRoles = $_SESSION['user_roles'] ?? [];
        return in_array($role, $userRoles, true);
    }

    /**
     * Check if current user is authenticated (without redirecting)
     * 
     * @return bool True if authenticated
     */
    public static function isAuthenticated(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user ID (or null if not authenticated)
     * 
     * @return int|null User ID or null
     */
    public static function userId(): ?int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['user_id'] ?? null;
    }
}
