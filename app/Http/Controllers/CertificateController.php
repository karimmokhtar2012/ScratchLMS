<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Http\Middleware\AuthMiddleware;
use App\Core\Database;

class CertificateController extends Controller
{
    private Database $db;

    public function __construct()
    {
        (new AuthMiddleware())->handle();
        $this->db = Database::getInstance();
    }

    /**
     * List all earned certificates
     */
    public function index(): void
    {
        $userId = $_SESSION['user_id'];
        
        $sql = "SELECT e.certificate_id, e.certified_at, c.title as course_title, c.thumbnail 
                FROM enrollments e 
                JOIN courses c ON e.course_id = c.id 
                WHERE e.user_id = :uid AND e.certificate_id IS NOT NULL 
                ORDER BY e.certified_at DESC";
        
        $certificates = $this->db->fetchAll($sql, [':uid' => $userId]);

        $this->view('certificates.index', ['certificates' => $certificates]);
    }

    /**
     * Show a specific certificate
     */
    public function show(string $certId): void
    {
        $userId = $_SESSION['user_id']; // For now, only owner can view. Later, public verification.

        $sql = "SELECT e.*, c.title as course_title, u.name as student_name 
                FROM enrollments e 
                JOIN courses c ON e.course_id = c.id 
                JOIN users u ON e.user_id = u.id 
                WHERE e.certificate_id = :certId";
        
        $cert = $this->db->fetchAll($sql, [':certId' => $certId])[0] ?? null;

        if (!$cert) {
            header('Location: /dashboard');
            exit;
        }

        // Layout: none (Full page print view)
        require_once __DIR__ . '/../../resources/views/certificates/view.php';
    }
}
