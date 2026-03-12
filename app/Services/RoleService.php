<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Role Service
 * Handles role management and role assignments
 */
class RoleService
{
    private Database $db;
    private array $roleCache = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get role by slug (with caching)
     *
     * @param string $slug Role slug
     * @return array|null Role data
     */
    public function getRoleBySlug(string $slug): ?array
    {
        // Check cache
        if (isset($this->roleCache[$slug])) {
            return $this->roleCache[$slug];
        }

        $sql = "SELECT * FROM roles WHERE slug = :slug LIMIT 1";
        $result = $this->db->fetchAll($sql, [':slug' => $slug]);

        $role = $result[0] ?? null;

        // Cache result
        if ($role) {
            $this->roleCache[$slug] = $role;
        }

        return $role;
    }

    /**
     * Get all roles for a user
     *
     * @param int $userId
     * @return array Array of role slugs
     */
    public function getUserRoles(int $userId): array
    {
        $sql = "SELECT r.slug FROM roles r
                JOIN role_user ru ON r.id = ru.role_id
                WHERE ru.user_id = :uid";

        $result = $this->db->fetchAll($sql, [':uid' => $userId]);

        return array_column($result, 'slug');
    }

    /**
     * Assign a role to a user
     *
     * @param int $userId
     * @param string $roleSlug Role slug (e.g., 'student', 'instructor')
     * @return bool Success status
     */
    public function assignRole(int $userId, string $roleSlug): bool
    {
        $role = $this->getRoleBySlug($roleSlug);

        if (!$role) {
            error_log("[RoleService] Role not found: $roleSlug");
            return false;
        }

        $sql = "INSERT INTO role_user (user_id, role_id) VALUES (:uid, :rid)";

        try {
            $this->db->query($sql, [
                ':uid' => $userId,
                ':rid' => $role['id']
            ]);
            return true;
        } catch (\Exception $e) {
            error_log("[RoleService] Failed to assign role: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Assign default student role to a new user
     *
     * @param int $userId
     * @return bool Success status
     */
    public function assignDefaultRole(int $userId): bool
    {
        return $this->assignRole($userId, 'student');
    }

    /**
     * Check if user has a specific role
     *
     * @param int $userId
     * @param string $roleSlug
     * @return bool
     */
    public function hasRole(int $userId, string $roleSlug): bool
    {
        $roles = $this->getUserRoles($userId);
        return in_array($roleSlug, $roles, true);
    }

    /**
     * Check if user has any of the specified roles
     *
     * @param int $userId
     * @param array $roleSlugs
     * @return bool
     */
    public function hasAnyRole(int $userId, array $roleSlugs): bool
    {
        $userRoles = $this->getUserRoles($userId);

        foreach ($roleSlugs as $slug) {
            if (in_array($slug, $userRoles, true)) {
                return true;
            }
        }

        return false;
    }
}
