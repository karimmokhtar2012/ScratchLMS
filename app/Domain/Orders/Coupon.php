<?php

declare(strict_types=1);

namespace App\Domain\Orders;

use App\Core\Model;

class Coupon extends Model
{
    public function findByCode(string $code): ?array
    {
        $sql = "SELECT * FROM coupons WHERE code = :code LIMIT 1";
        $res = $this->db->fetchAll($sql, [':code' => $code]);
        return $res[0] ?? null;
    }

    public function isValid(array $coupon): bool
    {
        // Check usage limit
        if ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
            return false;
        }

        // Check expiration
        if ($coupon['valid_until'] && strtotime($coupon['valid_until']) < time()) {
            return false;
        }

        return true;
    }

    public function incrementUsage(int $id): void
    {
        $this->db->query("UPDATE coupons SET used_count = used_count + 1 WHERE id = :id", [':id' => $id]);
    }
}
