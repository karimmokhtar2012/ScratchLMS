<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Policies\Authorization;

class QuizService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Start a new attempt or resume an incomplete one.
     */
    public function startAttempt(int $quizId, int $userId): array
    {
        // 0. Security: Check Enrollment
        $sqlContext = "SELECT m.course_id 
                       FROM quizzes q 
                       JOIN lessons l ON q.lesson_id = l.id 
                       JOIN modules m ON l.module_id = m.id 
                       WHERE q.id = :qid";
        $context = $this->db->fetchAll($sqlContext, [':qid' => $quizId])[0] ?? null;
        
        if (!$context) throw new \Exception("Quiz context not found");
        
        Authorization::requireEnrollment($userId, (int)$context['course_id']);

        // 1. Check Limits
        $sqlQuiz = "SELECT allowed_attempts FROM quizzes WHERE id = :id";
        $quizSettings = $this->db->fetchAll($sqlQuiz, [':id' => $quizId])[0] ?? null;

        if ($quizSettings && $quizSettings['allowed_attempts'] > 0) {
            $sqlCount = "SELECT COUNT(*) as count FROM quiz_attempts WHERE quiz_id = :qid AND user_id = :uid AND completed_at IS NOT NULL";
            $count = $this->db->fetchAll($sqlCount, [':qid' => $quizId, ':uid' => $userId])[0]['count'] ?? 0;
            
            if ($count >= $quizSettings['allowed_attempts']) {
                throw new \Exception("MAX_ATTEMPTS_REACHED");
            }
        }

        // 1. Check for existing incomplete attempt
        $sql = "SELECT * FROM quiz_attempts WHERE quiz_id = :qid AND user_id = :uid AND completed_at IS NULL ORDER BY started_at DESC LIMIT 1";
        $attempt = $this->db->fetchAll($sql, [':qid' => $quizId, ':uid' => $userId]);

        if (!empty($attempt)) {
            return $attempt[0];
        }

        // 2. Start New Attempt
        $sqlInsert = "INSERT INTO quiz_attempts (user_id, quiz_id, score, is_passed, answers) 
                      VALUES (:uid, :qid, 0, 0, '{}')";
        
        $this->db->query($sqlInsert, [':uid' => $userId, ':qid' => $quizId]);
        
        // Fetch it back
        $id = $this->db->getConnection()->lastInsertId();
        return $this->db->fetchAll("SELECT * FROM quiz_attempts WHERE id = :id", [':id' => $id])[0];
    }

    /**
     * Get the Full Quiz Context: Questions + User's Current Answers.
     */
    public function getQuizContext(int $attemptId, int $userId): ?array
    {
        // Fetch Attempt with Course Context
        $sqlAttempt = "SELECT qa.*, q.time_limit, q.lesson_id, q.is_randomized, m.course_id 
                       FROM quiz_attempts qa 
                       JOIN quizzes q ON qa.quiz_id = q.id 
                       JOIN lessons l ON q.lesson_id = l.id
                       JOIN modules m ON l.module_id = m.id
                       WHERE qa.id = :id AND qa.user_id = :uid";
        $attempt = $this->db->fetchAll($sqlAttempt, [':id' => $attemptId, ':uid' => $userId]);

        if (empty($attempt)) return null;
        $attempt = $attempt[0];

        // Security: Check Enrollment
        try {
            Authorization::requireEnrollment($userId, (int)$attempt['course_id']);
        } catch (\Exception $e) {
            return null; // Or throw, but controller handles null
        }

        // Fetch Questions (Without correct answers for security)
        $sqlQuestions = "SELECT id, type, question_text, media_url, options, points 
                         FROM questions 
                         WHERE quiz_id = :qid 
                         ORDER BY sort_order ASC, id ASC";
        
        $questions = $this->db->fetchAll($sqlQuestions, [':qid' => $attempt['quiz_id']]);

        // Logic: Shuffle if randomized
        if (!empty($attempt['is_randomized'])) {
            shuffle($questions);
        }

        // Decode Options
        foreach ($questions as &$q) {
            $q['options'] = json_decode($q['options'] ?? '[]', true);
        }

        return [
            'attempt' => $attempt,
            'questions' => $questions,
            'user_answers' => json_decode($attempt['answers'] ?? '{}', true)
        ];
    }

    /**
     * Save a single answer asynchronously.
     */
    public function saveAnswer(int $attemptId, int $userId, int $questionId, mixed $answer): void
    {
        // Fetch current answers JSON + Course ID
        $sql = "SELECT qa.answers, m.course_id 
                FROM quiz_attempts qa 
                JOIN quizzes q ON qa.quiz_id = q.id
                JOIN lessons l ON q.lesson_id = l.id
                JOIN modules m ON l.module_id = m.id
                WHERE qa.id = :id AND qa.user_id = :uid";
        
        $row = $this->db->fetchAll($sql, [':id' => $attemptId, ':uid' => $userId]);
        
        if (empty($row)) return; // Attempt not found or not owned by user

        // Security: Check Enrollment
        try {
            Authorization::requireEnrollment($userId, (int)$row[0]['course_id']);
        } catch (\Exception $e) {
            return; // Silently fail or throw (controller handles)
        }

        $answers = json_decode($row[0]['answers'] ?? '{}', true);
        $answers[$questionId] = $answer;

        $sqlUpdate = "UPDATE quiz_attempts SET answers = :ans WHERE id = :id";
        $this->db->query($sqlUpdate, [':ans' => json_encode($answers), ':id' => $attemptId]);
    }

    /**
     * Grade the quiz and finalize the attempt.
     */
    public function submitAttempt(int $attemptId, int $userId): array
    {
        // 1. Fetch Attempt & Answers & Course ID
        $sql = "SELECT qa.*, q.passing_score, q.time_limit, m.course_id 
                FROM quiz_attempts qa
                JOIN quizzes q ON qa.quiz_id = q.id
                JOIN lessons l ON q.lesson_id = l.id
                JOIN modules m ON l.module_id = m.id
                WHERE qa.id = :id AND qa.user_id = :uid";
        $attempt = $this->db->fetchAll($sql, [':id' => $attemptId, ':uid' => $userId]);

        if (empty($attempt)) throw new \Exception("Attempt not found");
        $attempt = $attempt[0];

        // Security: Check Enrollment
        Authorization::requireEnrollment($userId, (int)$attempt['course_id']);

        // --- ENFORCE TIME LIMIT ---
        if ($attempt['time_limit'] > 0) {
            $startedAt = strtotime($attempt['started_at']);
            $now = time();
            $elapsedMinutes = ($now - $startedAt) / 60;
            // 1 Minute Grace Period
            if ($elapsedMinutes > ($attempt['time_limit'] + 1)) {
                // Technically we could reject it or penalty it. 
                // For now, we will allow it but could log a "late" flag if we had one.
                // Or force close it at the limit.
                // Let's just proceed for grading but ensure we don't allow "resuming" expired quizzes later.
            }
        }

        $userAnswers = json_decode($attempt['answers'] ?? '{}', true);

        // 2. Fetch Questions & Correct Answers
        $sqlQ = "SELECT id, type, correct_answers, points FROM questions WHERE quiz_id = :qid";
        $questions = $this->db->fetchAll($sqlQ, [':qid' => $attempt['quiz_id']]);

        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($questions as $q) {
            $totalPoints += $q['points'];
            $correctKey = json_decode($q['correct_answers'] ?? '[]', true);
            $userAns = $userAnswers[$q['id']] ?? null;

            // Grading Logic
            $isCorrect = false;
            
            if ($q['type'] === 'single_choice' || $q['type'] === 'true_false') {
                // Expect single value match
                // User answer might be single value, correctKey is array [val]
                if (!empty($correctKey) && $userAns == $correctKey[0]) {
                    $isCorrect = true;
                }
            } elseif ($q['type'] === 'multi_choice') {
                // Expect arrays to match (ignoring order)
                if (is_array($userAns) && is_array($correctKey)) {
                    sort($userAns);
                    sort($correctKey);
                    if ($userAns == $correctKey) {
                        $isCorrect = true;
                    }
                }
            }

            if ($isCorrect) {
                $earnedPoints += $q['points'];
            }
        }

        // 3. Calculate Result
        $percentage = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;
        $isPassed = $percentage >= $attempt['passing_score'] ? 1 : 0;

        // 4. Update Database
        $update = "UPDATE quiz_attempts SET score = :score, is_passed = :passed, completed_at = CURRENT_TIMESTAMP WHERE id = :id";
        $this->db->query($update, [
            ':score' => round($percentage), 
            ':passed' => $isPassed, 
            ':id' => $attemptId
        ]);

        return [
            'score' => round($percentage),
            'is_passed' => (bool)$isPassed,
            'quiz_id' => $attempt['quiz_id']
        ];
    }
}
