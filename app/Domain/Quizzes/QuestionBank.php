<?php

declare(strict_types=1);

namespace App\Domain\Quizzes;

use App\Core\Model;

class QuestionBank extends Model
{
    public function create(array $data): int
    {
        $sql = "INSERT INTO question_banks (instructor_id, title, description) VALUES (:uid, :title, :desc)";
        $stmt = $this->db->query($sql, [
            ':uid' => $data['instructor_id'],
            ':title' => $data['title'],
            ':desc' => $data['description']
        ]);

        if ($stmt) {
            return (int) $this->db->getConnection()->lastInsertId();
        }
        return 0;
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM question_banks WHERE id = :id";
        $result = $this->db->fetchAll($sql, [':id' => $id]);
        return $result[0] ?? null;
    }

    public function findAllByInstructor(int $instructorId): array
    {
        $sql = "SELECT qb.*, (SELECT COUNT(*) FROM bank_questions bq WHERE bq.bank_id = qb.id) as question_count 
                FROM question_banks qb 
                WHERE instructor_id = :uid 
                ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [':uid' => $instructorId]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM question_banks WHERE id = :id";
        return (bool) $this->db->query($sql, [':id' => $id]);
    }
}
