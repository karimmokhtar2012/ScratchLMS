<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Security Headers
 * 
 * Sends security-related HTTP headers including:
 * - Content Security Policy (CSP)
 * - X-Content-Type-Options
 * - X-XSS-Protection
 * - X-Frame-Options
 * - Referrer-Policy
 */
class SecurityHeaders
{
    /**
     * Send all security headers
     * Call this early in the request lifecycle (e.g., in index.php)
     */
    public static function send(): void
    {
        // Only send headers if not already sent
        if (headers_sent()) {
            return;
        }

        // Content Security Policy
        header("Content-Security-Policy: " . self::buildCSP());

        // Prevent MIME type sniffing
        header("X-Content-Type-Options: nosniff");

        // XSS Protection (legacy browsers)
        header("X-XSS-Protection: 1; mode=block");

        // Prevent clickjacking
        header("X-Frame-Options: SAMEORIGIN");

        // Control referrer information
        header("Referrer-Policy: strict-origin-when-cross-origin");

        // Permissions Policy (formerly Feature-Policy)
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    }

    /**
     * Build the Content Security Policy
     * 
     * Policy is balanced for functionality with CDN dependencies:
     * - Tailwind CSS CDN
     * - Alpine.js CDN
     * - Google Fonts
     * - FontAwesome
     */
    private static function buildCSP(): string
    {
        $directives = [
            // Default: only allow from same origin
            "default-src" => "'self'",

            // Scripts: self + CDNs, allow inline for Alpine.js x- attributes
            "script-src" => "'self' cdn.tailwindcss.com cdn.jsdelivr.net unpkg.com 'unsafe-inline' 'unsafe-eval'",

            // Styles: self + Google Fonts + FontAwesome + inline for Tailwind
            "style-src" => "'self' fonts.googleapis.com cdnjs.cloudflare.com 'unsafe-inline'",

            // Fonts: self + Google Fonts + FontAwesome
            "font-src" => "'self' fonts.gstatic.com cdnjs.cloudflare.com",

            // Images: self + data URIs (for inline images) + any HTTPS
            "img-src" => "'self' data: https:",

            // AJAX/Fetch: only to same origin
            "connect-src" => "'self'",

            // Media: self (for videos)
            "media-src" => "'self'",

            // Objects: none (prevent Flash, Java, etc.)
            "object-src" => "'none'",

            // Child: none (prevent iframes from loading content)
            "child-src" => "'self'",

            // Frame ancestors: same origin only (prevent framing)
            "frame-ancestors" => "'self'",

            // Form actions: same origin only
            "form-action" => "'self'",

            // Base URI: self (prevent base tag hijacking)
            "base-uri" => "'self'",

            // Upgrade insecure requests in production
            // "upgrade-insecure-requests" => "",
        ];

        $parts = [];
        foreach ($directives as $directive => $value) {
            if ($value === '') {
                $parts[] = $directive;
            } else {
                $parts[] = "{$directive} {$value}";
            }
        }

        return implode('; ', $parts);
    }

    /**
     * Get CSP for report-only mode (testing)
     * Use this to test CSP without breaking functionality
     */
    public static function sendReportOnly(): void
    {
        if (headers_sent()) {
            return;
        }

        header("Content-Security-Policy-Report-Only: " . self::buildCSP());
    }
}
