<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Http\Middleware\AuthMiddleware;

class OnboardingController extends Controller
{
    private Database $db;

    public function __construct()
    {
        (new AuthMiddleware())->handle();
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        // If already onboarded, redirect to home
        $userId = $_SESSION['user_id'];
        $user = $this->db->fetchAll("SELECT is_onboarded FROM users WHERE id = :uid", [':uid' => $userId])[0] ?? null;

        if ($user && $user['is_onboarded']) {
            header('Location: /');
            exit;
        }

        $this->view('onboarding.index');
    }

    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /onboarding');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $fields = [
            'preferred_name' => $input['preferred_name'] ?? null,
            'gender' => $input['gender'] ?? null,
            'dob' => $input['dob'] ?? null,
            'country' => $input['country'] ?? null,
            'phone' => $input['phone'] ?? null,
            'field_of_work' => $input['field_of_work'] ?? null,
            'usage_intent' => $input['usage_intent'] ?? null,
            'is_onboarded' => 1
        ];

        $sets = [];
        $params = [':uid' => $userId];

        foreach ($fields as $key => $value) {
            $sets[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        $sql = "UPDATE users SET " . implode(', ', $sets) . " WHERE id = :uid";
        $this->db->query($sql, $params);

        // Update session name if preferred name is set
        if (!empty($fields['preferred_name'])) {
            $_SESSION['user_name'] = $fields['preferred_name'];
        }

        echo json_encode(['success' => true, 'redirect' => '/']);
        exit;
    }
}
