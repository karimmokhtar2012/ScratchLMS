<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Domain\Notifications\Notification;
use App\Http\Middleware\AuthMiddleware;

class NotificationController extends Controller
{
    private Notification $notificationModel;

    public function __construct()
    {
        (new AuthMiddleware())->handle();
        $this->notificationModel = new Notification();
    }

    // JSON API for Header Dropdown
    public function index(): void
    {
        $userId = $_SESSION['user_id'];
        $notifications = $this->notificationModel->findByUser($userId, 10); // Last 10
        $unreadCount = $this->notificationModel->unreadCount($userId);

        // Format dates for friendly display (e.g., "2 mins ago") if desired, 
        // but for now keeping it simple. Front-end can handle relative time if needed.

        header('Content-Type: application/json');
        echo json_encode([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
        exit;
    }

    public function markRead(string $id): void
    {
        $userId = $_SESSION['user_id'];
        $this->notificationModel->markAsRead((int)$id, $userId);
        echo json_encode(['status' => 'success']);
        exit;
    }

    public function markAllRead(): void
    {
        $userId = $_SESSION['user_id'];
        $this->notificationModel->markAllAsRead($userId);
        echo json_encode(['status' => 'success']);
        exit;
    }
}
