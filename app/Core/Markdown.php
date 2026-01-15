<?php

declare(strict_types=1);

namespace App\Core;

class Markdown
{
    /**
     * Parse Markdown into HTML.
     * Uses a simple regex-based parser for basic functionality without external dependencies.
     * Supports: Headers, Bold, Italic, Lists, Code Blocks, Links.
     */
    public static function parse(?string $text): string
    {
        if (empty($text)) return '';

        // Escape HTML to prevent XSS (Allowing specific tags later if needed)
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Headers
        $text = preg_replace('/^# (.*)$/m', '<h1 class="text-3xl font-bold text-white mb-4">$1</h1>', $text);
        $text = preg_replace('/^## (.*)$/m', '<h2 class="text-2xl font-bold text-white mb-3">$1</h2>', $text);
        $text = preg_replace('/^### (.*)$/m', '<h3 class="text-xl font-bold text-white mb-2">$1</h3>', $text);

        // Code Blocks (Pre-formatted)
        $text = preg_replace('/```(.*?)```/s', '<pre class="bg-zinc-900 p-4 rounded-lg overflow-x-auto text-sm font-mono text-blue-300 my-4">$1</pre>', $text);
        
        // Inline Code
        $text = preg_replace('/`(.*?)`/', '<code class="bg-zinc-800 text-blue-300 px-1 py-0.5 rounded font-mono text-sm">$1</code>', $text);

        // Bold & Italic
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);

        // Unordered Lists
        // Wrap adjacent list items in <ul>
        // This simple regex replaces each line. A proper block parser handles wrapping better.
        // For simplicity: Bullet points become list items with margin.
        $text = preg_replace('/^\* (.*)$/m', '<div class="flex items-start mb-2"><span class="mr-2 text-blue-500">•</span> <span>$1</span></div>', $text);

        // Links
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank" class="text-blue-400 hover:text-blue-300 underline">$1</a>', $text);

        // Paragraphs (Double newline)
        $lines = explode("\n\n", $text);
        $text = implode('</p><p class="mb-4 text-zinc-300 leading-relaxed">', $lines);
        $text = '<p class="mb-4 text-zinc-300 leading-relaxed">' . $text . '</p>';

        return $text;
    }
}

