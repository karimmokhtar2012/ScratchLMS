<?php

declare(strict_types=1);

namespace App\Domain\Orders;

use App\Core\Model;

class Order extends Model
{
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO orders (user_id, total_amount, status, payment_method, transaction_id) 
                VALUES (:uid, :total, :status, :method, :tid)";
        
        $stmt = $this->db->query($sql, [
            ':uid' => $data['user_id'],
            ':total' => $data['total_amount'],
            ':status' => $data['status'] ?? 'pending',
            ':method' => $data['payment_method'] ?? null,
            ':tid' => $data['transaction_id'] ?? null
        ]);

        if ($stmt) {
            return (int) $this->db->getConnection()->lastInsertId();
        }
        return false;
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM orders WHERE id = :id LIMIT 1";
        $res = $this->db->fetchAll($sql, [':id' => $id]);
        return $res[0] ?? null;
    }

    public function updateStatus(int $id, string $status, ?string $transactionId = null): bool
    {
        $sql = "UPDATE orders SET status = :status, transaction_id = :tid WHERE id = :id";
        return (bool) $this->db->query($sql, [
            ':status' => $status,
            ':tid' => $transactionId,
            ':id' => $id
        ]);
    }

    public function getItems(int $orderId): array
    {
        $sql = "SELECT oi.*, c.title, c.thumbnail 
                FROM order_items oi 
                JOIN courses c ON oi.course_id = c.id 
                WHERE oi.order_id = :oid";
        return $this->db->fetchAll($sql, [':oid' => $orderId]);
    }
}
