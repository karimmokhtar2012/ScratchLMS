<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Custom exception for database-related errors
 * Provides context about failed queries for better debugging
 */
class DatabaseException extends Exception
{
    private string $query;
    private array $params;
    private string $errorInfo;

    public function __construct(
        string $message,
        string $query,
        array $params = [],
        string $errorInfo = '',
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->query = $query;
        $this->params = $params;
        $this->errorInfo = $errorInfo;

        // Log error with full context
        $this->logError();
    }

    /**
     * Get the SQL query that failed
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Get the query parameters
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get database error details
     */
    public function getErrorInfo(): string
    {
        return $this->errorInfo;
    }

    /**
     * Log the database error with full context
     */
    private function logError(): void
    {
        if (function_exists('logDatabaseError')) {
            logDatabaseError(
                $this->query,
                $this->params,
                $this->getMessage() . ' | ' . $this->errorInfo,
                $this->getFile(),
                $this->getLine()
            );
        } else {
            // Fallback if helper not available
            error_log(sprintf(
                '[DB ERROR] %s | Query: %s | Params: %s | Error: %s | File: %s:%d',
                $this->getMessage(),
                $this->query,
                json_encode($this->params),
                $this->errorInfo,
                $this->getFile(),
                $this->getLine()
            ));
        }
    }

    /**
     * Get user-friendly error message (safe to display)
     */
    public function getUserMessage(): string
    {
        return 'A database error occurred. Please try again later.';
    }
}
