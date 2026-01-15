<?php

declare(strict_types=1);

namespace App\Domain\Orders;

use App\Core\Model;

class OrderItem extends Model
{
    public function create(array $data): bool
    {
        $sql = "INSERT INTO order_items (order_id, course_id, price_at_purchase) 
                VALUES (:oid, :cid, :price)";
        
        return (bool) $this->db->query($sql, [
            ':oid' => $data['order_id'],
            ':cid' => $data['course_id'],
            ':price' => $data['price_at_purchase']
        ]);
    }
}
