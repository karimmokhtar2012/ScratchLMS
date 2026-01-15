<?php

declare(strict_types=1);

namespace App\Domain\Notifications;

use App\Core\Model;

class Notification extends Model
{
    public function create(array $data): bool
    {
        $sql = "INSERT INTO notifications (user_id, type, title, message, link) 
                VALUES (:uid, :type, :title, :msg, :link)";
        
        return (bool) $this->db->query($sql, [
            ':uid' => $data['user_id'],
            ':type' => $data['type'],
            ':title' => $data['title'],
            ':msg' => $data['message'],
            ':link' => $data['link'] ?? null
        ]);
    }

    public function findByUser(int $userId, int $limit = 20): array
    {
        $sql = "SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT :limit";
        return $this->db->fetchAll($sql, [':uid' => $userId, ':limit' => $limit]);
    }

    public function unreadCount(int $userId): int
    {
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :uid AND is_read = 0";
        $res = $this->db->fetchAll($sql, [':uid' => $userId]);
        return $res[0]['count'] ?? 0;
    }

    public function markAsRead(int $id, int $userId): bool
    {
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid";
        return (bool) $this->db->query($sql, [':id' => $id, ':uid' => $userId]);
    }

    public function markAllAsRead(int $userId): bool
    {
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = :uid";
        return (bool) $this->db->query($sql, [':uid' => $userId]);
    }
}
