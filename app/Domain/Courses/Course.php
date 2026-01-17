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

    

    public function contentItems(int $courseId): array

    {

        if (!$this->hasTable('content_items')) {

            return [];

        }

    

            $sql = "SELECT ci.*, 

                           CASE 

                               WHEN ci.item_type = 'lesson' THEN l.title 

                               WHEN ci.item_type = 'quiz' THEN ql.title

                               ELSE 'Unknown'

                           END as title,

                           CASE 

                               WHEN ci.item_type = 'lesson' THEN l.type 

                               WHEN ci.item_type = 'quiz' THEN 'quiz'

                               ELSE 'unknown'

                           END as type,

                           CASE 

                               WHEN ci.item_type = 'lesson' THEN l.duration 

                               ELSE 0

                           END as duration

                    FROM content_items ci

                    LEFT JOIN lessons l ON ci.item_type = 'lesson' AND ci.item_id = l.id

                    LEFT JOIN quizzes q ON ci.item_type = 'quiz' AND ci.item_id = q.id

                    LEFT JOIN lessons ql ON q.lesson_id = ql.id

                    WHERE ci.course_id = :course_id

                    ORDER BY ci.position ASC";

            

            return $this->db->fetchAll($sql, [':course_id' => $courseId]);

        }

        private function hasTable(string $table): bool
        {
            $driver = $this->db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $tableCheck = $this->db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name = :table", [
                    ':table' => $table
                ]);
            } else {
                $tableCheck = $this->db->fetchAll("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table", [
                    ':table' => $table
                ]);
            }

            return !empty($tableCheck);
        }

    }

    
