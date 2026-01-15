<?php

declare(strict_types=1);

namespace App\Domain\Users;

use App\Core\Model;
use PDO;

class User extends Model
{
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
        $fields = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
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
