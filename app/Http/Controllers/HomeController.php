<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Pass session state to view for conditional button rendering
        $isLoggedIn = isset($_SESSION['user_id']);
        
        $this->view('home', ['isLoggedIn' => $isLoggedIn]);
    }

    public function setLanguage(string $lang): void
    {
        \App\Core\Lang::getInstance()->set($lang);
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header('Location: ' . $referer);
        exit;
    }

    public function flashTest(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash_success'] = 'Hello';
        header('Location: /dashboard');
        exit;
    }
}
