<?php

declare(strict_types=1);

namespace App\Core;

class Lang
{
    private static ?Lang $instance = null;
    private string $currentLang = 'en';
    private array $translations = [];
    
    private const SUPPORTED_LANGUAGES = ['en', 'ar'];

    private function __construct()
    {
        // 1. Check Session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], self::SUPPORTED_LANGUAGES)) {
            $this->currentLang = $_SESSION['lang'];
        } 
        // 2. Check Cookie (if session expired)
        elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], self::SUPPORTED_LANGUAGES)) {
            $this->currentLang = $_COOKIE['lang'];
            $_SESSION['lang'] = $_COOKIE['lang'];
        }
        
        // Load translations for the current language
        $this->loadTranslations();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadTranslations(): void
    {
        // Absolute path relative to this file: app/Core/Lang.php -> resources/lang
        $baseDir = dirname(__DIR__, 2) . '/resources/lang/';
        
        $path = $baseDir . $this->currentLang . '/messages.php';

        if (file_exists($path)) {
            $this->translations = require $path;
        } else {
            // Fallback to English if specific lang file is missing
            $fallbackPath = $baseDir . 'en/messages.php';
            if (file_exists($fallbackPath)) {
                $this->translations = require $fallbackPath;
            } else {
                // If both fail, empty array
                $this->translations = [];
            }
        }
    }

    public function get(string $key): string
    {
        return $this->translations[$key] ?? $key;
    }

    public function current(): string
    {
        return $this->currentLang;
    }

    public function direction(): string
    {
        return $this->currentLang === 'ar' ? 'rtl' : 'ltr';
    }

    public function set(string $lang): void
    {
        if (in_array($lang, self::SUPPORTED_LANGUAGES)) {
            $this->currentLang = $lang;
            $_SESSION['lang'] = $lang;
            setcookie('lang', $lang, time() + (86400 * 30), "/"); // 30 days
            
            // Reload translations immediately so the current request reflects the change
            $this->loadTranslations(); 
        }
    }
}
