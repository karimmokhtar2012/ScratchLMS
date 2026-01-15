<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

abstract class Controller
{
    protected function view(string $path, array $data = []): void
    {
        // Auto-inject CSRF token
        $data['csrf_token'] = \App\Core\Csrf::getToken();

        // Extract data to make variables available in the view
        extract($data);

        // Convert dot notation to path (e.g., 'home.index' -> 'home/index')
        $path = str_replace('.', '/', $path);
        
        $viewFile = __DIR__ . '/../../resources/views/' . $path . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException("View file not found: {$viewFile}");
        }

        require $viewFile;
    }

    protected function abort(int $code = 404): void
    {
        http_response_code($code);
        $viewFile = __DIR__ . '/../../resources/views/errors/' . $code . '.php';
        
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "{$code} Error";
        }
        exit;
    }
}
