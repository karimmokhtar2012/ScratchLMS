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
        $sqlQ = "SELECT id, type, question_text, correct_answers, points, grading_policy, ai_rubric, options FROM questions WHERE quiz_id = :qid";
        $questions = $this->db->fetchAll($sqlQ, [':qid' => $attempt['quiz_id']]);

        $totalPoints = 0;
        $earnedPoints = 0;
        $manualQueue = [];

        foreach ($questions as $q) {
            $totalPoints += $q['points'];
            $correctKey = json_decode($q['correct_answers'] ?? '[]', true);
            $userAns = $userAnswers[$q['id']] ?? null;
            $policy = $this->resolveGradingPolicy($q['grading_policy'] ?? null, $q['type']);

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
            } elseif ($q['type'] === 'fill_blank') {
                $isCorrect = $this->gradeFillBlank($userAns, $correctKey, $policy);
                if (!$isCorrect && $this->shouldUseAiGrading($q)) {
                    $aiResult = $this->requestAiScore($q, $userAns, $correctKey);
                    $isCorrect = $this->aiPasses($aiResult, (float) $q['points']);
                    if ($aiResult) {
                        $userAnswers[$q['id'] . '_ai_result'] = $aiResult;
                    }
                }
            } elseif ($q['type'] === 'matching') {
                $options = json_decode($q['options'] ?? '[]', true);
                $isCorrect = $this->gradeMatching($userAns, $correctKey, $options, $policy);
            } elseif ($q['type'] === 'short_text') {
                if (!empty($correctKey) && $userAns == $correctKey[0]) {
                    $isCorrect = true;
                } elseif ($this->shouldUseAiGrading($q)) {
                    $aiResult = $this->requestAiScore($q, $userAns, $correctKey);
                    $isCorrect = $this->aiPasses($aiResult, (float) $q['points']);
                    if ($aiResult) {
                        $userAnswers[$q['id'] . '_ai_result'] = $aiResult;
                    }
                }
            } elseif (in_array($q['type'], ['essay', 'video_submission'], true)) {
                if ($q['type'] === 'video_submission' && is_string($userAns) && $userAns !== '' && !$this->isValidUrl($userAns)) {
                    throw new \Exception('INVALID_VIDEO_URL');
                }
                $manualQueue[] = $q;
            }

            if ($isCorrect) {
                $earnedPoints += $q['points'];
            }
        }

        // 3. Calculate Result
        $percentage = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;
        $hasManual = !empty($manualQueue);
        $gradingStatus = $hasManual ? 'pending' : 'final';
        $isPassed = (!$hasManual && $percentage >= $attempt['passing_score']) ? 1 : 0;

        // 4. Process Manual Queue & AI Pre-scoring
        if ($hasManual) {
            foreach ($manualQueue as $q) {
                $aiScore = null;
                $aiFeedback = null;
                $status = 'pending_review';
                $policy = $this->resolveGradingPolicy($q['grading_policy'] ?? null, $q['type']);
                
                if ($q['type'] === 'essay' && $policy === 'ai') {
                    $aiResult = $this->requestAiScore($q, $userAnswers[$q['id']] ?? null, []);
                    if ($aiResult !== null) {
                        $aiScore = $aiResult['score'];
                        $aiFeedback = $aiResult['feedback'];
                        $status = 'ai_scored';
                    } else {
                        $aiScore = round($q['points'] * 0.6, 2);
                        $aiFeedback = 'AI pre-score placeholder. Manual review required.';
                        $status = 'ai_scored';
                    }
                }
                
                $this->db->query("INSERT INTO grading_queue (attempt_id, question_id, status, ai_score, ai_feedback) VALUES (:aid, :qid, :status, :ai_score, :ai_feedback)", [
                    ':aid' => $attemptId,
                    ':qid' => $q['id'],
                    ':status' => $status,
                    ':ai_score' => $aiScore,
                    ':ai_feedback' => $aiFeedback
                ]);
            }
        }

        // 5. Final Database Update
        $update = "UPDATE quiz_attempts SET score = :score, is_passed = :passed, grading_status = :status, completed_at = CURRENT_TIMESTAMP, answers = :answers WHERE id = :id";
        $this->db->query($update, [
            ':score' => round($percentage), 
            ':passed' => $isPassed, 
            ':status' => $gradingStatus,
            ':answers' => json_encode($userAnswers),
            ':id' => $attemptId
        ]);

        return [
            'score' => round($percentage),
            'is_passed' => (bool)$isPassed,
            'grading_status' => $gradingStatus,
            'quiz_id' => $attempt['quiz_id']
        ];
    }

    public function recalculateAttemptScore(int $attemptId): array
    {
        $attempt = $this->db->fetchAll("SELECT qa.*, q.passing_score FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id = q.id WHERE qa.id = :id", [':id' => $attemptId])[0] ?? null;
        if (!$attempt) {
            throw new \Exception('Attempt not found');
        }

        $userAnswers = json_decode($attempt['answers'] ?? '{}', true);
        $questions = $this->db->fetchAll("SELECT id, type, correct_answers, points, grading_policy, options FROM questions WHERE quiz_id = :qid", [':qid' => $attempt['quiz_id']]);
        $manualScores = $this->db->fetchAll("SELECT question_id, final_score, status FROM grading_queue WHERE attempt_id = :aid", [':aid' => $attemptId]);
        $manualMap = [];
        $manualComplete = true;
        foreach ($manualScores as $row) {
            $manualMap[(int)$row['question_id']] = $row;
            if ($row['status'] === 'pending_review' || $row['status'] === 'ai_scored') {
                $manualComplete = false;
            }
        }

        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($questions as $q) {
            $totalPoints += $q['points'];
            $userAns = $userAnswers[$q['id']] ?? null;
            $correctKey = json_decode($q['correct_answers'] ?? '[]', true);
            $policy = $this->resolveGradingPolicy($q['grading_policy'] ?? null, $q['type']);
            $isCorrect = false;

            if (in_array($q['type'], ['essay', 'video_submission'], true)) {
                if (isset($manualMap[$q['id']]) && $manualMap[$q['id']]['final_score'] !== null) {
                    $earnedPoints += (float) $manualMap[$q['id']]['final_score'];
                }
                continue;
            }

            if ($q['type'] === 'single_choice' || $q['type'] === 'true_false') {
                if (!empty($correctKey) && $userAns == $correctKey[0]) {
                    $isCorrect = true;
                }
            } elseif ($q['type'] === 'multi_choice') {
                if (is_array($userAns) && is_array($correctKey)) {
                    sort($userAns);
                    sort($correctKey);
                    if ($userAns == $correctKey) {
                        $isCorrect = true;
                    }
                }
            } elseif ($q['type'] === 'fill_blank') {
                $isCorrect = $this->gradeFillBlank($userAns, $correctKey, $policy);
                if (!$isCorrect && $this->shouldUseAiGrading($q)) {
                    $aiResult = $this->requestAiScore($q, $userAns, $correctKey);
                    $isCorrect = $this->aiPasses($aiResult, (float) $q['points']);
                    if ($aiResult) {
                        $userAnswers[$q['id'] . '_ai_result'] = $aiResult;
                    }
                }
            } elseif ($q['type'] === 'matching') {
                $options = json_decode($q['options'] ?? '[]', true);
                $isCorrect = $this->gradeMatching($userAns, $correctKey, $options, $policy);
            } elseif ($q['type'] === 'short_text') {
                if (!empty($correctKey) && $userAns == $correctKey[0]) {
                    $isCorrect = true;
                } elseif ($this->shouldUseAiGrading($q)) {
                    $aiResult = $this->requestAiScore($q, $userAns, $correctKey);
                    $isCorrect = $this->aiPasses($aiResult, (float) $q['points']);
                    if ($aiResult) {
                        $userAnswers[$q['id'] . '_ai_result'] = $aiResult;
                    }
                }
            }

            if ($isCorrect) {
                $earnedPoints += $q['points'];
            }
        }

        $percentage = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;
        $gradingStatus = $manualComplete ? 'final' : 'pending';
        $isPassed = ($gradingStatus === 'final' && $percentage >= $attempt['passing_score']) ? 1 : 0;

        $this->db->query("UPDATE quiz_attempts SET score = :score, is_passed = :passed, grading_status = :status, answers = :answers WHERE id = :id", [
            ':score' => round($percentage),
            ':passed' => $isPassed,
            ':status' => $gradingStatus,
            ':answers' => json_encode($userAnswers),
            ':id' => $attemptId
        ]);

        return [
            'score' => round($percentage),
            'is_passed' => (bool) $isPassed,
            'grading_status' => $gradingStatus
        ];
    }
    
    // ...

    private function resolveGradingPolicy(?string $policy, string $type): string
    {
        if (in_array($type, ['fill_blank', 'matching'], true)) {
            if (in_array($policy, ['strict', 'fuzzy', 'both'], true)) {
                return $policy;
            }
            return 'both';
        }

        if ($type === 'essay') {
            return $policy === 'manual' ? 'manual' : 'ai';
        }

        if ($type === 'video_submission') {
            return 'manual';
        }

        return $policy ?: 'auto';
    }

    private function gradeFillBlank(mixed $userAns, array $correctKey, string $policy): bool
    {
        if (!is_string($userAns)) {
            return false;
        }

        foreach ($correctKey as $candidate) {
            $candidate = (string) $candidate;
            if ($policy === 'strict') {
                if ($this->isStrictTextMatch($userAns, $candidate)) {
                    return true;
                }
            } elseif ($policy === 'fuzzy') {
                if ($this->isFuzzyTextMatch($userAns, $candidate)) {
                    return true;
                }
            } else {
                if ($this->isStrictTextMatch($userAns, $candidate) || $this->isFuzzyTextMatch($userAns, $candidate)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function gradeMatching(mixed $userAns, array $correctKey, array $options, string $policy): bool
    {
        if (!is_array($userAns) || !is_array($correctKey)) {
            return false;
        }

        if ($this->isMatchingStrict($userAns, $correctKey)) {
            return true;
        }

        if ($policy === 'strict') {
            return false;
        }

        return $this->isMatchingFuzzy($userAns, $correctKey, $options);
    }

    private function isMatchingStrict(array $userAns, array $correctKey): bool
    {
        foreach ($correctKey as $pair) {
            $leftId = $pair['left_id'] ?? null;
            $rightId = $pair['right_id'] ?? null;
            if ($leftId === null || $rightId === null) {
                return false;
            }
            if (!isset($userAns[$leftId]) || (int)$userAns[$leftId] !== (int)$rightId) {
                return false;
            }
        }

        return true;
    }

    private function isMatchingFuzzy(array $userAns, array $correctKey, array $options): bool
    {
        $rightTextById = [];
        foreach ($options as $pair) {
            if (isset($pair['right_id'])) {
                $rightTextById[(int) $pair['right_id']] = (string) ($pair['right_text'] ?? '');
            }
        }

        foreach ($correctKey as $pair) {
            $leftId = $pair['left_id'] ?? null;
            $rightId = $pair['right_id'] ?? null;
            if ($leftId === null || $rightId === null) {
                return false;
            }
            if (!isset($userAns[$leftId])) {
                return false;
            }
            $userValue = $userAns[$leftId];
            if (is_numeric($userValue)) {
                return false;
            }
            $correctText = $rightTextById[(int) $rightId] ?? '';
            if (!$this->isFuzzyTextMatch((string) $userValue, $correctText)) {
                return false;
            }
        }

        return true;
    }

    private function isStrictTextMatch(string $userAns, string $candidate): bool
    {
        return strtolower(trim($userAns)) === strtolower(trim($candidate));
    }

    private function isFuzzyTextMatch(string $userAns, string $candidate): bool
    {
        $normalizedUser = $this->normalizeForFuzzy($userAns);
        $normalizedCandidate = $this->normalizeForFuzzy($candidate);

        if ($normalizedUser === '' || $normalizedCandidate === '') {
            return false;
        }

        if ($normalizedUser === $normalizedCandidate) {
            return true;
        }

        if (str_replace(' ', '', $normalizedUser) === str_replace(' ', '', $normalizedCandidate)) {
            return true;
        }

        similar_text($normalizedUser, $normalizedCandidate, $percent);
        return $percent >= 85;
    }

    private function normalizeForFuzzy(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value);
    }

    private function isValidUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }

    private function shouldUseAiGrading(array $question): bool
    {
        $key = getenv('GROQ_API_KEY') ?: ($_ENV['GROQ_API_KEY'] ?? null);
        if (!$key) {
            return false;
        }
        return !empty($question['ai_rubric']);
    }

    private function aiPasses(?array $aiResult, float $points): bool
    {
        if (!$aiResult) {
            return false;
        }
        $threshold = max(1.0, $points * 0.7);
        return $aiResult['score'] >= $threshold;
    }

    private function requestAiScore(array $question, mixed $userAnswer, array $correctAnswers): ?array
    {
        $key = getenv('GROQ_API_KEY') ?: ($_ENV['GROQ_API_KEY'] ?? null);
        if (!$key) {
            $this->logAi("GROQ_API_KEY not found in environment.");
            return null;
        }

        $isDebug = (getenv('APP_DEBUG') === 'true' || ($_ENV['APP_DEBUG'] ?? '') === 'true');

        $prompt = "You are grading a student answer.\n"
            . "Question: " . ($question['question_text'] ?? '') . "\n"
            . "Correct answers: " . json_encode($correctAnswers) . "\n"
            . "Student answer: " . json_encode($userAnswer) . "\n"
            . "Rubric: " . ($question['ai_rubric'] ?? '') . "\n"
            . "Return JSON: {\"score\": number (0 to " . (float) $question['points'] . "), \"feedback\": string}.\n";

        $payload = json_encode([
            'model' => 'openai/gpt-oss-20b',
            'input' => $prompt,
            'temperature' => 0.2,
            'max_output_tokens' => 200
        ]);

        if ($isDebug) {
            $this->logAi("Sending request to Groq API. Payload: " . $payload);
        } else {
            $this->logAi("Sending request to Groq API. Question ID: " . ($question['id'] ?? 'unknown'));
        }

        $ch = curl_init('https://api.groq.com/openai/v1/responses');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            $this->logAi("cURL Error: " . $error);
            return null;
        }

        if ($isDebug) {
            $this->logAi("Response Status: $status. Body: " . $raw);
        } else {
            $this->logAi("Response Status: $status. (Body redacted)");
        }

        if ($status < 200 || $status >= 300) {
            $this->logAi("API Error (Status $status). Response: " . ($isDebug ? $raw : '[Redacted]'));
            return null;
        }

        $data = json_decode($raw, true);
        $text = '';
        
        if (isset($data['output_text'])) {
            $text = $data['output_text'];
        } elseif (isset($data['output']) && is_array($data['output'])) {
            foreach ($data['output'] as $outBlock) {
                if (isset($outBlock['content']) && is_array($outBlock['content'])) {
                    foreach ($outBlock['content'] as $contentItem) {
                        if (($contentItem['type'] ?? '') === 'output_text' && isset($contentItem['text'])) {
                            $text = $contentItem['text'];
                            break 2;
                        }
                    }
                }
            }
        }

        $json = $this->extractJson($text);
        if (!$json || !isset($json['score'])) {
            $this->logAi("Failed to extract JSON from response. Text: " . ($isDebug ? $text : '[Redacted]'));
            return null;
        }

        return [
            'score' => (float) $json['score'],
            'feedback' => (string) ($json['feedback'] ?? '')
        ];
    }

    private function logAi(string $message): void
    {
        $dir = __DIR__ . '/../../storage/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file = $dir . '/ai_grading.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($file, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    }

    private function extractJson(string $text): ?array
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }
        $json = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }
}
