<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Rate Limiter Service
 * 
 * Provides brute-force protection by limiting attempts per key (IP + action).
 * Uses file-based storage for simplicity. For high-traffic production,
 * consider Redis implementation.
 */
class RateLimiter
{
    private string $storagePath;

    public function __construct()
    {
        $this->storagePath = __DIR__ . '/../../storage/cache/rate_limits/';

        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    /**
     * Record an attempt and check if allowed
     * 
     * @param string $key Unique identifier (e.g., "login:192.168.1.1")
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $decayMinutes Time window in minutes
     * @return bool True if attempt is allowed, false if blocked
     */
    public function attempt(string $key, int $maxAttempts, int $decayMinutes): bool
    {
        $data = $this->getData($key);
        $now = time();

        // Clean expired attempts
        $cutoff = $now - ($decayMinutes * 60);
        $data['attempts'] = array_filter(
            $data['attempts'] ?? [],
            fn($timestamp) => $timestamp > $cutoff
        );

        // Check if blocked
        if (count($data['attempts']) >= $maxAttempts) {
            $this->saveData($key, $data);
            return false;
        }

        // Record this attempt
        $data['attempts'][] = $now;
        $this->saveData($key, $data);

        return true;
    }

    /**
     * Check if too many attempts have been made (without recording new attempt)
     * 
     * @param string $key Unique identifier
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $decayMinutes Time window in minutes
     * @return bool True if blocked, false if allowed
     */
    public function tooManyAttempts(string $key, int $maxAttempts, int $decayMinutes): bool
    {
        $data = $this->getData($key);
        $now = time();

        // Clean expired attempts
        $cutoff = $now - ($decayMinutes * 60);
        $data['attempts'] = array_filter(
            $data['attempts'] ?? [],
            fn($timestamp) => $timestamp > $cutoff
        );

        return count($data['attempts']) >= $maxAttempts;
    }

    /**
     * Clear all attempts for a key (call on successful action)
     * 
     * @param string $key Unique identifier
     */
    public function clear(string $key): void
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Get remaining attempts before lockout
     * 
     * @param string $key Unique identifier
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $decayMinutes Time window in minutes
     * @return int Remaining attempts
     */
    public function remainingAttempts(string $key, int $maxAttempts, int $decayMinutes): int
    {
        $data = $this->getData($key);
        $now = time();

        // Clean expired attempts
        $cutoff = $now - ($decayMinutes * 60);
        $attempts = array_filter(
            $data['attempts'] ?? [],
            fn($timestamp) => $timestamp > $cutoff
        );

        return max(0, $maxAttempts - count($attempts));
    }

    /**
     * Get seconds until lockout expires
     * 
     * @param string $key Unique identifier
     * @param int $decayMinutes Time window in minutes
     * @return int Seconds until unlock (0 if not locked)
     */
    public function availableIn(string $key, int $decayMinutes): int
    {
        $data = $this->getData($key);

        if (empty($data['attempts'])) {
            return 0;
        }

        $oldestAttempt = min($data['attempts']);
        $unlocksAt = $oldestAttempt + ($decayMinutes * 60);

        return max(0, $unlocksAt - time());
    }

    /**
     * Build a rate limit key from components
     * 
     * @param string $action Action name (login, register, etc.)
     * @param string|null $identifier Optional identifier (defaults to IP)
     * @return string Combined key
     */
    public static function key(string $action, ?string $identifier = null): string
    {
        $identifier = $identifier ?? self::getClientIp();
        return $action . ':' . md5($identifier);
    }

    /**
     * Get client IP address
     */
    public static function getClientIp(): string
    {
        // Check for proxy headers (be careful in production - validate trusted proxies)
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated list (X-Forwarded-For)
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get data for a key
     */
    private function getData(string $key): array
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return ['attempts' => []];
        }

        $content = file_get_contents($file);
        $data = json_decode($content, true);

        return is_array($data) ? $data : ['attempts' => []];
    }

    /**
     * Save data for a key
     */
    private function saveData(string $key, array $data): void
    {
        $file = $this->getFilePath($key);
        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    /**
     * Get file path for a key
     */
    private function getFilePath(string $key): string
    {
        return $this->storagePath . md5($key) . '.json';
    }
}
