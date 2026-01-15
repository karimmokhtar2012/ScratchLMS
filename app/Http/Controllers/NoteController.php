<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Http\Middleware\AuthMiddleware;
use App\Core\Database;

use App\Policies\Authorization;

class NoteController extends Controller
{
    private Database $db;

    public function __construct()
    {
        (new AuthMiddleware())->handle();
        $this->db = Database::getInstance();
    }

    /**
     * List all notes for the user (The Notebook Dashboard)
     */
    public function index(): void
    {
        $userId = $_SESSION['user_id'];
        
        $sql = "SELECT n.*, l.title as lesson_title, l.type as lesson_type, c.title as course_title, c.id as course_id
                FROM notes n
                JOIN lessons l ON n.lesson_id = l.id
                JOIN modules m ON l.module_id = m.id
                JOIN courses c ON m.course_id = c.id
                WHERE n.user_id = :uid
                ORDER BY n.created_at DESC";
        
        $notes = $this->db->fetchAll($sql, [':uid' => $userId]);

        $this->view('notes.index', ['notes' => $notes]);
    }

    /**
     * AJAX: Get notes for a specific lesson
     */
    public function list(string $lessonId): void
    {
        $userId = $_SESSION['user_id'];
        $lessonId = (int) $lessonId;

        // Verify Enrollment first (indirectly)
        // Ideally we check if lesson belongs to enrolled course.
        $sqlCheck = "SELECT m.course_id FROM lessons l JOIN modules m ON l.module_id = m.id WHERE l.id = :lid";
        $res = $this->db->fetchAll($sqlCheck, [':lid' => $lessonId]);
        
        if ($res) {
            try {
                Authorization::requireEnrollment($userId, $res[0]['course_id']);
            } catch (\Exception $e) {
                echo json_encode([]);
                exit;
            }
        }

        $sql = "SELECT * FROM notes WHERE user_id = :uid AND lesson_id = :lid ORDER BY timestamp ASC";
        $notes = $this->db->fetchAll($sql, [':uid' => $userId, ':lid' => $lessonId]);

        echo json_encode($notes);
    }

    /**
     * AJAX: Save a new note
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        header('Content-Type: application/json'); // Ensure JSON response

        $input = json_decode(file_get_contents('php://input'), true);
        $lessonId = (int) ($input['lesson_id'] ?? 0);
        $content = trim($input['content'] ?? '');
        $timestamp = isset($input['timestamp']) ? (int)$input['timestamp'] : null;
        $userId = $_SESSION['user_id'];

        if ($lessonId && !empty($content)) {
            // Security: Check Enrollment
            $sqlCheck = "SELECT m.course_id FROM lessons l JOIN modules m ON l.module_id = m.id WHERE l.id = :lid";
            $res = $this->db->fetchAll($sqlCheck, [':lid' => $lessonId]);
            
            if (!$res) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Lesson not found']);
                exit;
            }

            try {
                Authorization::requireEnrollment($userId, $res[0]['course_id']);
            } catch (\Exception $e) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Not enrolled']);
                exit;
            }

            $sql = "INSERT INTO notes (user_id, lesson_id, content, timestamp) VALUES (:uid, :lid, :content, :ts)";
            $this->db->query($sql, [
                ':uid' => $userId,
                ':lid' => $lessonId,
                ':content' => $content,
                ':ts' => $timestamp
            ]);
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        }
    }

    /**
     * AJAX: Delete a note
     */
    public function delete(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $noteId = (int) $id;
        $userId = $_SESSION['user_id'];

        $sql = "DELETE FROM notes WHERE id = :id AND user_id = :uid";
        $this->db->query($sql, [':id' => $noteId, ':uid' => $userId]);

        // If AJAX request, return JSON. If Form submit (from dashboard), redirect.
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success']);
        } else {
            $_SESSION['flash_success'] = 'Note deleted.';
            header('Location: /notes');
        }
    }
}
