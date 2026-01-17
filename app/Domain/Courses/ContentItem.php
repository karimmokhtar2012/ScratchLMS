<?php

declare(strict_types=1);

namespace App\Domain\Courses;

use App\Core\Model;

class ContentItem extends Model
{
    protected string $table = 'content_items';

    /**
     * Create a new content item linked to a payload (lesson/quiz)
     */
    public function createItem(array $data): int|false
    {
        // Calculate next position if not provided
        if (!isset($data['position'])) {
            $sqlPos = "SELECT MAX(position) as max_pos FROM content_items WHERE course_id = :cid AND module_id = :mid";
            $res = $this->db->fetchAll($sqlPos, [
                ':cid' => $data['course_id'],
                ':mid' => $data['module_id']
            ]);
            $data['position'] = ($res[0]['max_pos'] ?? 0) + 1;
        }

        $sql = "INSERT INTO content_items (course_id, module_id, item_type, item_id, position, display_group_id, created_at)
                VALUES (:cid, :mid, :type, :iid, :pos, :group, CURRENT_TIMESTAMP)";
        
        $success = $this->db->query($sql, [
            ':cid' => $data['course_id'],
            ':mid' => $data['module_id'],
            ':type' => $data['item_type'],
            ':iid' => $data['item_id'],
            ':pos' => $data['position'],
            ':group' => $data['display_group_id'] ?? null
        ]);

        return $success ? (int) $this->db->getConnection()->lastInsertId() : false;
    }

    /**
     * Find item by type and payload ID
     */
    public function findByPayload(string $type, int $itemId): ?array
    {
        $sql = "SELECT * FROM content_items WHERE item_type = :type AND item_id = :id";
        $res = $this->db->fetchAll($sql, [':type' => $type, ':id' => $itemId]);
        return $res[0] ?? null;
    }

    /**
     * Fetch full structure for a module
     */
    public function getModuleStructure(int $moduleId): array
    {
        $sql = "SELECT ci.*, 
                       CASE 
                           WHEN ci.item_type = 'lesson' THEN l.title 
                           WHEN ci.item_type = 'quiz' THEN l_quiz.title
                           ELSE 'Unknown'
                       END as title,
                       CASE 
                           WHEN ci.item_type = 'lesson' THEN l.type 
                           WHEN ci.item_type = 'quiz' THEN 'quiz'
                           ELSE 'unknown'
                       END as type
                FROM content_items ci
                LEFT JOIN lessons l ON ci.item_type = 'lesson' AND ci.item_id = l.id
                LEFT JOIN quizzes q ON ci.item_type = 'quiz' AND ci.item_id = q.id
                LEFT JOIN lessons l_quiz ON q.lesson_id = l_quiz.id
                WHERE ci.module_id = :mid
                ORDER BY ci.position ASC";
        
        return $this->db->fetchAll($sql, [':mid' => $moduleId]);
    }

    public function updatePosition(int $id, int $newPosition): void
    {
        $this->db->query("UPDATE content_items SET position = :pos WHERE id = :id", [
            ':pos' => $newPosition,
            ':id' => $id
        ]);
    }

    public function deleteItem(int $id): void
    {
        $this->db->query("DELETE FROM content_items WHERE id = :id", [':id' => $id]);
    }
}
