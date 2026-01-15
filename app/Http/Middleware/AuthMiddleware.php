<?php

declare(strict_types=1);

namespace App\Http\Middleware;

class AuthMiddleware
{
    public function handle(array $allowedRoles = []): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $db = \App\Core\Database::getInstance();
        $userId = $_SESSION['user_id'];
        $exists = $db->fetchAll("SELECT id FROM users WHERE id = :uid", [':uid' => $userId]);
        if (empty($exists)) {
            session_destroy();
            header('Location: /login');
            exit;
        }

        if (!empty($allowedRoles)) {
            // Check if user has ANY of the allowed roles
            $sql = "SELECT COUNT(*) FROM role_user ru 
                    JOIN roles r ON ru.role_id = r.id 
                    WHERE ru.user_id = :uid AND r.slug IN (" . implode(',', array_map(fn($r) => "'$r'", $allowedRoles)) . ")";
            
            // Note: In a real app, use prepared statements for the IN clause properly or array matching.
            // For simplicity with our DB wrapper:
            $placeholders = [];
            $params = [':uid' => $userId];
            foreach ($allowedRoles as $i => $role) {
                $key = ":role$i";
                $placeholders[] = $key;
                $params[$key] = $role;
            }
            
            $sql = "SELECT COUNT(*) as count FROM role_user ru 
                    JOIN roles r ON ru.role_id = r.id 
                    WHERE ru.user_id = :uid AND r.slug IN (" . implode(',', $placeholders) . ")";
            
            $result = $db->fetchAll($sql, $params);
            
            if (($result[0]['count'] ?? 0) == 0) {
                // User does not have permission
                http_response_code(403);
                $view = __DIR__ . '/../../../resources/views/errors/403.php';
                if (file_exists($view)) {
                    require $view;
                } else {
                    echo "403 Forbidden - Access Denied";
                }
                exit;
            }
        }
    }
}
