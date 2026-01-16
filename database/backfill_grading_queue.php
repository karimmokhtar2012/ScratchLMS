<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();

    $attempts = $db->query("SELECT id, quiz_id FROM quiz_attempts WHERE grading_status = 'pending'")->fetchAll(PDO::FETCH_ASSOC);
    $inserted = 0;

    foreach ($attempts as $attempt) {
        $attemptId = (int) $attempt['id'];
        $quizId = (int) $attempt['quiz_id'];

        $stmt = $db->prepare("SELECT 1 FROM grading_queue WHERE attempt_id = :aid LIMIT 1");
        $stmt->execute([':aid' => $attemptId]);
        $existing = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($existing)) {
            continue;
        }

        $stmt = $db->prepare("SELECT id, type, points, grading_policy FROM questions WHERE quiz_id = :qid AND type IN ('essay','video_submission')");
        $stmt->execute([':qid' => $quizId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($questions as $q) {
            $aiScore = null;
            $aiFeedback = null;
            $status = 'pending_review';
            if ($q['type'] === 'essay' && ($q['grading_policy'] ?? 'ai') === 'ai') {
                $aiScore = round(((float) $q['points']) * 0.6, 2);
                $aiFeedback = 'AI pre-score placeholder. Manual review required.';
                $status = 'ai_scored';
            }

            $db->prepare("INSERT INTO grading_queue (attempt_id, question_id, status, ai_score, ai_feedback) VALUES (:aid, :qid, :status, :ai_score, :ai_feedback)")
               ->execute([
                   ':aid' => $attemptId,
                   ':qid' => (int) $q['id'],
                   ':status' => $status,
                   ':ai_score' => $aiScore,
                   ':ai_feedback' => $aiFeedback
               ]);
            $inserted++;
        }
    }

    echo "✅ Backfill complete. Inserted $inserted grading items.\n";
} catch (Exception $e) {
    echo "❌ Backfill failed: " . $e->getMessage() . "\n";
    exit(1);
}
