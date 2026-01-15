<?php

declare(strict_types=1);

namespace App\Domain\Lessons;

use App\Core\Model;

class Lesson extends Model
{
    public function create(array $data): bool
    {
        $sql = "INSERT INTO lessons (module_id, title, type, content_url) VALUES (:module_id, :title, :type, :content_url)";
        return (bool) $this->db->query($sql, [
            ':module_id' => $data['module_id'],
            ':title' => $data['title'],
            ':type' => $data['type'],
            ':content_url' => $data['content_url']
        ]);
    }
}
