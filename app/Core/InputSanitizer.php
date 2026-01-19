<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Input Sanitizer
 * 
 * Defense-in-depth input sanitization.
 * Use this at the controller level BEFORE storing data.
 * 
 * Note: This complements output escaping (htmlspecialchars/e()).
 * Both should be used for maximum security.
 */
class InputSanitizer
{
    /**
     * Sanitize text input (names, titles, etc.)
     * Strips all HTML tags and trims whitespace
     * 
     * @param string $value Raw input
     * @return string Sanitized text
     */
    public static function text(string $value): string
    {
        // Trim whitespace
        $value = trim($value);

        // Strip all HTML/PHP tags
        $value = strip_tags($value);

        // Normalize whitespace (collapse multiple spaces)
        $value = preg_replace('/\s+/', ' ', $value);

        return $value;
    }

    /**
     * Sanitize email input
     * Validates format and sanitizes
     * 
     * @param string $value Raw email
     * @return string Sanitized email (empty if invalid)
     */
    public static function email(string $value): string
    {
        $value = trim($value);
        $value = filter_var($value, FILTER_SANITIZE_EMAIL);

        // Return empty if not valid email format
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return '';
        }

        return strtolower($value);
    }

    /**
     * Sanitize integer input
     * 
     * @param mixed $value Raw value
     * @param int $default Default if invalid
     * @return int Sanitized integer
     */
    public static function int(mixed $value, int $default = 0): int
    {
        $sanitized = filter_var($value, FILTER_SANITIZE_NUMBER_INT);

        if ($sanitized === false || $sanitized === '') {
            return $default;
        }

        return (int) $sanitized;
    }

    /**
     * Sanitize float input
     * 
     * @param mixed $value Raw value
     * @param float $default Default if invalid
     * @return float Sanitized float
     */
    public static function float(mixed $value, float $default = 0.0): float
    {
        $sanitized = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        if ($sanitized === false || $sanitized === '') {
            return $default;
        }

        return (float) $sanitized;
    }

    /**
     * Sanitize URL input
     * 
     * @param string $value Raw URL
     * @return string Sanitized URL (empty if invalid)
     */
    public static function url(string $value): string
    {
        $value = trim($value);
        $value = filter_var($value, FILTER_SANITIZE_URL);

        // Validate URL format
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return '';
        }

        // Only allow http/https protocols
        $parsed = parse_url($value);
        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'], true)) {
            return '';
        }

        return $value;
    }

    /**
     * Sanitize HTML with allowed tags (for rich text editors)
     * 
     * @param string $value Raw HTML
     * @param array $allowedTags Tags to allow (e.g., ['p', 'br', 'strong', 'em'])
     * @return string Sanitized HTML
     */
    public static function html(string $value, array $allowedTags = []): string
    {
        $value = trim($value);

        if (empty($allowedTags)) {
            // Default safe tags for rich text
            $allowedTags = ['p', 'br', 'strong', 'em', 'b', 'i', 'u', 'ul', 'ol', 'li', 'a', 'h1', 'h2', 'h3', 'h4', 'blockquote', 'code', 'pre'];
        }

        // Build allowed tags string for strip_tags
        $allowedStr = '<' . implode('><', $allowedTags) . '>';

        // Strip disallowed tags
        $value = strip_tags($value, $allowedStr);

        // Remove dangerous attributes from allowed tags
        $value = self::removeUnsafeAttributes($value);

        return $value;
    }

    /**
     * Batch sanitize multiple inputs
     * 
     * @param array $inputs Input array (e.g., $_POST)
     * @param array $rules Rules ['field' => 'type'] or ['field' => ['type', default]]
     * @return array Sanitized inputs
     * 
     * Example:
     * InputSanitizer::sanitize($_POST, [
     *     'name' => 'text',
     *     'email' => 'email',
     *     'age' => ['int', 18],
     *     'bio' => 'html'
     * ]);
     */
    public static function sanitize(array $inputs, array $rules): array
    {
        $result = [];

        foreach ($rules as $field => $rule) {
            $value = $inputs[$field] ?? '';
            $type = is_array($rule) ? $rule[0] : $rule;
            $default = is_array($rule) ? ($rule[1] ?? null) : null;

            $result[$field] = match ($type) {
                'text' => self::text((string) $value),
                'email' => self::email((string) $value),
                'int' => self::int($value, $default ?? 0),
                'float' => self::float($value, $default ?? 0.0),
                'url' => self::url((string) $value),
                'html' => self::html((string) $value),
                'raw' => $value, // No sanitization (use carefully!)
                default => self::text((string) $value)
            };
        }

        return $result;
    }

    /**
     * Remove unsafe attributes from HTML tags
     * Removes: onclick, onerror, onload, javascript:, etc.
     */
    private static function removeUnsafeAttributes(string $html): string
    {
        // Remove event handlers (on*)
        $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/\s*on\w+\s*=\s*[^\s>]*/i', '', $html);

        // Remove javascript: and data: URLs
        $html = preg_replace('/\s*href\s*=\s*["\']?\s*javascript:[^"\'>\s]*/i', '', $html);
        $html = preg_replace('/\s*src\s*=\s*["\']?\s*javascript:[^"\'>\s]*/i', '', $html);
        $html = preg_replace('/\s*href\s*=\s*["\']?\s*data:[^"\'>\s]*/i', '', $html);

        // Remove style attributes (can contain expressions)
        $html = preg_replace('/\s*style\s*=\s*["\'][^"\']*["\']/i', '', $html);

        return $html;
    }
}
