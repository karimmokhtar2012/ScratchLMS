<?php

declare(strict_types=1);

namespace App\Core;

class RateLimiter
{
    /**
     * Check if an action is allowed for the current session/user.
     * @param string $key Identifier for the action (e.g., 'quiz_submit_123' or 'upload_user_5')
     * @param int $maxAttempts
     * @param int $decaySeconds
     * @return bool
     */
    public static function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $now = time();
        $sessionKey = 'rate_limit_' . $key;

        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [
                'attempts' => 1,
                'first_attempt_at' => $now
            ];
            return true;
        }

        $data = $_SESSION[$sessionKey];

        // Reset if decay period has passed
        if (($now - $data['first_attempt_at']) > $decaySeconds) {
            $_SESSION[$sessionKey] = [
                'attempts' => 1,
                'first_attempt_at' => $now
            ];
            return true;
        }

        // Check attempts
        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }

        // Increment
        $_SESSION[$sessionKey]['attempts']++;
        return true;
    }
}
