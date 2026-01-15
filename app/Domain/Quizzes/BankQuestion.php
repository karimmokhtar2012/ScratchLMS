<?php

declare(strict_types=1);

namespace App\Domain\Quizzes;

use App\Core\Model;

class BankQuestion extends Model
{
    public function create(array $data): int
    {
        $sql = "INSERT INTO bank_questions (bank_id, type, question_text, options, correct_answers, explanation) 
                VALUES (:bid, :type, :text, :opts, :correct, :exp)";
        
        $stmt = $this->db->query($sql, [
            ':bid' => $data['bank_id'],
            ':type' => $data['type'],
            ':text' => $data['question_text'],
            ':opts' => $data['options'],
            ':correct' => $data['correct_answers'],
            ':exp' => $data['explanation']
        ]);

        if ($stmt) {
            return (int) $this->db->getConnection()->lastInsertId();
        }
        return 0;
    }

    public function findAllByBank(int $bankId): array
    {
        $sql = "SELECT * FROM bank_questions WHERE bank_id = :bid ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [':bid' => $bankId]);
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM bank_questions WHERE id = :id";
        $result = $this->db->fetchAll($sql, [':id' => $id]);
        return $result[0] ?? null;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM bank_questions WHERE id = :id";
        return (bool) $this->db->query($sql, [':id' => $id]);
    }
}
