<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Security Logger Service
 * 
 * Centralized security event logging for audit trail.
 * Logs authentication events, authorization failures, and suspicious activity.
 */
class SecurityLogger
{
    private string $logPath;

    public function __construct()
    {
        $this->logPath = __DIR__ . '/../../storage/logs/';

        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * Log an authentication event
     * 
     * @param string $type Event type (login_success, login_failed, login_blocked, logout)
     * @param int|null $userId User ID (null for failed attempts)
     * @param array $context Additional context (email, ip, user_agent, etc.)
     */
    public function logAuthEvent(string $type, ?int $userId, array $context = []): void
    {
        $this->log('AUTH', $type, array_merge([
            'user_id' => $userId,
            'ip' => RateLimiter::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ], $context));
    }

    /**
     * Log an access denied event
     * 
     * @param string $resource Resource that was denied (route, file, etc.)
     * @param int|null $userId User ID if authenticated
     * @param array $context Additional context
     */
    public function logAccessDenied(string $resource, ?int $userId = null, array $context = []): void
    {
        $this->log('ACCESS', 'permission_denied', array_merge([
            'user_id' => $userId,
            'resource' => $resource,
            'ip' => RateLimiter::getClientIp(),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
        ], $context));
    }

    /**
     * Log a CSRF validation failure
     * 
     * @param string $endpoint The endpoint that failed CSRF
     * @param array $context Additional context
     */
    public function logCsrfFailure(string $endpoint, array $context = []): void
    {
        $this->log('SECURITY', 'csrf_failure', array_merge([
            'endpoint' => $endpoint,
            'ip' => RateLimiter::getClientIp(),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ], $context));
    }

    /**
     * Log a file upload rejection
     * 
     * @param int|null $userId User ID
     * @param string $filename Original filename
     * @param string $reason Reason for rejection
     */
    public function logUploadRejected(?int $userId, string $filename, string $reason): void
    {
        $this->log('UPLOAD', 'file_rejected', [
            'user_id' => $userId,
            'filename' => $filename,
            'reason' => $reason,
            'ip' => RateLimiter::getClientIp()
        ]);
    }

    /**
     * Log suspicious activity
     * 
     * @param string $description Description of the suspicious activity
     * @param array $context Additional context
     */
    public function logSuspiciousActivity(string $description, array $context = []): void
    {
        $this->log('ALERT', 'suspicious_activity', array_merge([
            'description' => $description,
            'ip' => RateLimiter::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ], $context));
    }

    /**
     * Core logging method
     * 
     * @param string $category Category (AUTH, ACCESS, SECURITY, UPLOAD, ALERT)
     * @param string $event Event type
     * @param array $data Event data
     */
    private function log(string $category, string $event, array $data): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logFile = $this->logPath . 'security-' . date('Y-m-d') . '.log';

        $entry = [
            'timestamp' => $timestamp,
            'category' => $category,
            'event' => $event,
            'data' => $data
        ];

        $line = "[{$timestamp}] [{$category}] {$event}: " . json_encode($data, JSON_UNESCAPED_SLASHES) . "\n";

        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get severity level for an event type
     * 
     * @param string $event Event type
     * @return string Severity (INFO, WARNING, ALERT, CRITICAL)
     */
    public static function getSeverity(string $event): string
    {
        return match ($event) {
            'login_success', 'logout' => 'INFO',
            'login_failed', 'permission_denied', 'file_rejected' => 'WARNING',
            'login_blocked', 'csrf_failure' => 'ALERT',
            'suspicious_activity' => 'CRITICAL',
            default => 'INFO'
        };
    }
}
