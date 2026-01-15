<?php

declare(strict_types=1);

namespace App\Domain\Courses;

use App\Core\Model;

class Module extends Model
{
    public function create(array $data): bool
    {
        $sql = "INSERT INTO modules (course_id, title) VALUES (:course_id, :title)";
        return (bool) $this->db->query($sql, [
            ':course_id' => $data['course_id'],
            ':title' => $data['title']
        ]);
    }

    public function lessons(int $moduleId): array
    {
        $sql = "SELECT * FROM lessons WHERE module_id = :module_id ORDER BY sort_order ASC";
        return $this->db->fetchAll($sql, [':module_id' => $moduleId]);
    }
}