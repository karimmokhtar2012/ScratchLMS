<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Models\Lesson;

try {
    $db = Database::getInstance();
    $userId = 1; // Assuming 'Karim' (Admin/Instructor)

    echo "🧪 TESTING HOURS LEARNED LOGIC...\n";

    // 1. Create a dummy lesson with Duration
    $db->query("INSERT INTO lessons (module_id, title, type, duration) VALUES (1, 'Long Lecture', 'video', 7200)"); // 2 Hours (7200s)
    $lessonId = $db->getConnection()->lastInsertId();
    echo "   -> Created Lesson ID $lessonId with 2 Hours duration.\n";

    // 2. Mark as Completed
    $db->query("INSERT OR IGNORE INTO completed_lessons (user_id, lesson_id) VALUES (:uid, :lid)", [':uid' => $userId, ':lid' => $lessonId]);
    echo "   -> Marked as Completed for User $userId.\n";

    // 3. Verify Calculation (Same logic as DashboardController)
    $sql = "SELECT SUM(l.duration) as seconds 
            FROM completed_lessons cl
            JOIN lessons l ON cl.lesson_id = l.id
            WHERE cl.user_id = :uid";
    $res = $db->fetchAll($sql, [':uid' => $userId]);
    $seconds = $res[0]['seconds'] ?? 0;
    $hours = (int) floor($seconds / 3600);

    echo "   -> Total Seconds: $seconds\n";
    echo "   -> Calculated Hours: $hours\n";

    if ($hours >= 2) {
        echo "   ✅ SUCCESS: Hours Learned is tracking correctly.\n";
    } else {
        echo "   ❌ FAILURE: Duration not summing up.\n";
    }

    // Cleanup
    $db->query("DELETE FROM lessons WHERE id = $lessonId");
    $db->query("DELETE FROM completed_lessons WHERE lesson_id = $lessonId");

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
