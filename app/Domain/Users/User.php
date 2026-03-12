<?php

declare(strict_types=1);

namespace App\Domain\Users;

use App\Core\Model;
use PDO;

class User extends Model
{
    // Whitelist of columns allowed to be updated (prevents mass assignment)
    private const ALLOWED_UPDATE_COLUMNS = [
        'name',
        'email',
        'password',
        'profile_picture',
        'bio',
        'is_onboarded'
    ];

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
        $stmt = $this->db->query($sql, [
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':password' => $data['password'],
        ]);

        if ($stmt) {
            return (int) $this->db->getConnection()->lastInsertId();
        }
        return false;
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
        $result = $this->db->fetchAll($sql, [':id' => $id]);
        return $result[0] ?? null;
    }

    public function update(int $id, array $data): bool
    {
        // Filter data to only allowed columns (prevents mass assignment attacks)
        $filteredData = array_filter(
            $data,
            fn($key) => in_array($key, self::ALLOWED_UPDATE_COLUMNS, true),
            ARRAY_FILTER_USE_KEY
        );

        // If no valid columns to update, return false
        if (empty($filteredData)) {
            error_log("[Security] Attempted to update user $id with no allowed fields: " . json_encode(array_keys($data)));
            return false;
        }

        $fields = [];
        $params = [':id' => $id];

        foreach ($filteredData as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        return (bool) $this->db->query($sql, $params);
    }

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $result = $this->db->fetchAll($sql, [':email' => $email]);

        return $result[0] ?? null;
    }
}
