<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Domain\Users\User;
use App\Domain\Notifications\Notification;

/**
 * User Service
 * Handles user registration, role assignment, and user-related business logic
 */
class UserService
{
    private Database $db;
    private User $userModel;
    private RoleService $roleService;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->userModel = new User();
        $this->roleService = new RoleService();
        $this->notificationService = new NotificationService();
    }

    /**
     * Register a new user with default student role and welcome notification
     *
     * @param array $data User registration data (name, email, password)
     * @return int|false User ID on success, false on failure
     */
    public function register(array $data): int|false
    {
        // Validate required fields
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            return false;
        }

        // Check if email already exists
        if ($this->userModel->findByEmail($data['email'])) {
            return false;
        }

        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

        // Create user
        $userId = $this->userModel->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $hashedPassword
        ]);

        if (!$userId) {
            return false;
        }

        // Assign default student role
        $this->roleService->assignDefaultRole($userId);

        // Send welcome notification
        $this->notificationService->sendWelcome($userId);

        return $userId;
    }

    /**
     * Get user by ID with roles eager-loaded
     *
     * @param int $userId
     * @return array|null User data with roles array
     */
    public function getUserWithRoles(int $userId): ?array
    {
        // Get user data
        $user = $this->db->fetchOne("SELECT * FROM users WHERE id = :id", [':id' => $userId]);

        if (!$user) {
            return null;
        }

        // Get roles separately (cross-database compatible)
        $roles = $this->db->fetchAll(
            "SELECT r.slug FROM roles r 
             INNER JOIN role_user ru ON r.id = ru.role_id 
             WHERE ru.user_id = :user_id",
            [':user_id' => $userId]
        );

        $user['roles'] = array_column($roles, 'slug');

        return $user;
    }

    /**
     * Get user by email with roles eager-loaded (optimized for login)
     *
     * @param string $email
     * @return array|null User data with roles array
     */
    public function getUserByEmailWithRoles(string $email): ?array
    {
        // Get user data
        $user = $this->db->fetchOne("SELECT * FROM users WHERE email = :email", [':email' => $email]);

        if (!$user) {
            return null;
        }

        // Get roles separately (cross-database compatible)
        $roles = $this->db->fetchAll(
            "SELECT r.slug FROM roles r 
             INNER JOIN role_user ru ON r.id = ru.role_id 
             WHERE ru.user_id = :user_id",
            [':user_id' => $user['id']]
        );

        $user['roles'] = array_column($roles, 'slug');

        return $user;
    }

    /**
     * Update user profile data
     *
     * @param int $userId
     * @param array $data Data to update
     * @return bool Success status
     */
    public function updateProfile(int $userId, array $data): bool
    {
        return $this->userModel->update($userId, $data);
    }
}
