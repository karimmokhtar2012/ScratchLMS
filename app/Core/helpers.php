<?php

declare(strict_types=1);

/**
 * Security Helper Functions
 * Global helper functions for XSS protection, file validation, and safe redirects
 */

if (!function_exists('e')) {
    /**
     * Escape output for HTML display (XSS protection)
     * 
     * @param string|null $value The value to escape
     * @return string The escaped value
     */
    function e(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('validateFileUpload')) {
    /**
     * Validate uploaded file against whitelist of allowed extensions and MIME types
     * 
     * @param array $file The $_FILES array element
     * @param array $allowedExtensions Array of allowed file extensions (without dots)
     * @param array $allowedMimeTypes Array of allowed MIME types
     * @return array ['valid' => bool, 'error' => string|null]
     */
    function validateFileUpload(array $file, array $allowedExtensions, array $allowedMimeTypes = []): array
    {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'File upload failed'];
        }

        // Validate file extension
        $filename = $file['name'] ?? '';
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions, true)) {
            return ['valid' => false, 'error' => "File type '$extension' not allowed. Allowed types: " . implode(', ', $allowedExtensions)];
        }

        // Validate MIME type if provided
        if (!empty($allowedMimeTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedMimeTypes, true)) {
                return ['valid' => false, 'error' => "Invalid file content. Expected MIME types: " . implode(', ', $allowedMimeTypes)];
            }
        }

        // Additional security: Check for double extensions
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        if (preg_match('/\.(php|phtml|php3|php4|php5|phar|exe|sh|bat|cmd)$/i', $basename)) {
            return ['valid' => false, 'error' => 'Potential executable file detected'];
        }

        return ['valid' => true, 'error' => null];
    }
}

if (!function_exists('sanitizeRedirect')) {
    /**
     * Sanitize redirect path to prevent open redirect vulnerabilities
     * 
     * @param string $path The redirect path to validate
     * @param string $default The default path if validation fails
     * @return string Safe redirect path
     */
    function sanitizeRedirect(string $path, string $default = '/'): string
    {
        // Empty path
        if (empty($path)) {
            return $default;
        }

        // Only allow paths starting with single forward slash
        if (!preg_match('#^/[^/]#', $path)) {
            return $default;
        }

        // Reject protocol-relative URLs (//example.com)
        if (str_starts_with($path, '//')) {
            return $default;
        }

        // Reject URLs with protocols
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $path)) {
            return $default;
        }

        // Reject path traversal attempts
        if (str_contains($path, '..')) {
            return $default;
        }

        // Reject null bytes
        if (str_contains($path, "\0")) {
            return $default;
        }

        return $path;
    }
}

if (!function_exists('logDatabaseError')) {
    /**
     * Log database errors with context
     * 
     * @param string $query The SQL query that failed
     * @param array $params Query parameters
     * @param string $error The error message
     * @param string $file The file where the error occurred
     * @param int $line The line number where the error occurred
     * @return void
     */
    function logDatabaseError(string $query, array $params, string $error, string $file, int $line): void
    {
        $context = [
            'query' => $query,
            'params' => $params,
            'error' => $error,
            'file' => $file,
            'line' => $line,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        error_log('[DB ERROR] ' . json_encode($context, JSON_UNESCAPED_SLASHES));
    }
}
