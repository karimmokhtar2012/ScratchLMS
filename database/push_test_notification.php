<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance();

// Default to User ID 1 (Admin/Instructor)
$userId = 1;

// Optional: Pass ID as argument
if (isset($argv[1])) {
    $userId = (int) $argv[1];
}

echo "🔔 Pushing test notification to User ID: $userId...\n";

$title = "Test Notification " . date('H:i:s');
$message = "This is a test notification generated at " . date('Y-m-d H:i:s') . ". It should pop up immediately.";
$link = "/dashboard";

$sql = "INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) 
        VALUES (:uid, 'system', :title, :msg, :link, 0, CURRENT_TIMESTAMP)";

$db->query($sql, [
    ':uid' => $userId,
    ':title' => $title,
    ':msg' => $message,
    ':link' => $link
]);

echo "✅ Notification pushed! Check your dashboard in ~5 seconds.\n";

