<?php

declare(strict_types=1);

namespace App\Domain\Courses;

use App\Core\Model;
use PDO;

class Course extends Model
{
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO courses (title, slug, description, price, thumbnail, instructor_id, status, published_at) 
                VALUES (:title, :slug, :description, :price, :thumbnail, :instructor_id, :status, :published_at)";
        
        $stmt = $this->db->query($sql, [
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':description' => $data['description'],
            ':price' => $data['price'],
            ':thumbnail' => $data['thumbnail'],
            ':instructor_id' => $data['instructor_id'],
            ':status' => $data['status'] ?? 'draft',
            ':published_at' => $data['published_at'] ?? null
        ]);

        if ($stmt) {
             return (int) $this->db->getConnection()->lastInsertId();
        }
        return false;
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM courses WHERE id = :id";
        $result = $this->db->fetchAll($sql, [':id' => $id]);
        return $result[0] ?? null;
    }

    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT id FROM courses WHERE slug = :slug";
        $result = $this->db->fetchAll($sql, [':slug' => $slug]);
        return $result[0] ?? null;
    }

    public function modules(int $courseId): array
    {
        $sql = "SELECT * FROM modules WHERE course_id = :course_id ORDER BY sort_order ASC";
        return $this->db->fetchAll($sql, [':course_id' => $courseId]);
    }
}