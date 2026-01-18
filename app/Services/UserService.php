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
        $sql = "SELECT u.*, GROUP_CONCAT(r.slug) as roles
                FROM users u
                LEFT JOIN role_user ru ON u.id = ru.user_id
                LEFT JOIN roles r ON ru.role_id = r.id
                WHERE u.id = :id
                GROUP BY u.id";

        $result = $this->db->fetchAll($sql, [':id' => $userId]);

        if (empty($result)) {
            return null;
        }

        $user = $result[0];
        $user['roles'] = $user['roles'] ? explode(',', $user['roles']) : [];

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
        $sql = "SELECT u.*, GROUP_CONCAT(r.slug) as roles
                FROM users u
                LEFT JOIN role_user ru ON u.id = ru.user_id
                LEFT JOIN roles r ON ru.role_id = r.id
                WHERE u.email = :email
                GROUP BY u.id";

        $result = $this->db->fetchAll($sql, [':email' => $email]);

        if (empty($result)) {
            return null;
        }

        $user = $result[0];
        $user['roles'] = $user['roles'] ? explode(',', $user['roles']) : [];

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
