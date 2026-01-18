<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Http\Middleware\AuthMiddleware;

class UploadController extends Controller
{
    public function __construct()
    {
        (new AuthMiddleware())->handle(['instructor', 'master_instructor', 'admin']);
    }

    public function uploadChunk(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
            exit;
        }

        // Rate Limit: 100 chunks per minute (allows fast upload but prevents DoS)
        if (!\App\Core\RateLimiter::attempt('upload_' . ($_SESSION['user_id'] ?? 'guest'), 100, 60)) {
            http_response_code(429);
            echo json_encode(['status' => 'error', 'message' => 'Too many requests']);
            exit;
        }

        // Parameters
        $fileName = $_POST['file_name'] ?? '';
        $chunkIndex = (int) ($_POST['chunk_index'] ?? -1);
        $totalChunks = (int) ($_POST['total_chunks'] ?? 0);
        $uploadId = $_POST['upload_id'] ?? '';

        if (!$fileName || $chunkIndex < 0 || $totalChunks < 1 || !$uploadId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            exit;
        }

        // Temp Storage Path (Group by Upload ID to avoid collisions)
        $tempDir = __DIR__ . '/../../storage/temp/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $uploadId);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Save Chunk
        $chunkPath = $tempDir . '/' . $chunkIndex . '.part';
        if (!empty($_FILES['file_data']['tmp_name'])) {
            if (!move_uploaded_file($_FILES['file_data']['tmp_name'], $chunkPath)) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Failed to save chunk']);
                exit;
            }
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'No file data received']);
            exit;
        }

        // Check if all chunks are uploaded
        // Simple check: count files. Better check: verify indexes.
        $uploadedChunks = count(glob($tempDir . '/*.part'));

        if ($uploadedChunks === $totalChunks) {
            $this->mergeChunks($tempDir, $fileName, $totalChunks);
        } else {
            echo json_encode(['status' => 'progress', 'chunk' => $chunkIndex]);
        }
    }

    private function mergeChunks(string $tempDir, string $originalFileName, int $totalChunks): void
    {
        $finalDir = __DIR__ . '/../../public/uploads/videos';
        if (!is_dir($finalDir)) {
            mkdir($finalDir, 0755, true);
        }

        // Validate file extension (whitelist for videos)
        $allowedExtensions = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v'];
        $allowedMimeTypes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'];

        $extension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => "File type '$extension' not allowed. Allowed types: " . implode(', ', $allowedExtensions)
            ]);
            return;
        }

        // Check for double extensions (e.g., file.php.mp4)
        $basename = pathinfo($originalFileName, PATHINFO_FILENAME);
        if (preg_match('/\.(php|phtml|php3|php4|php5|phar|exe|sh|bat|cmd|js|html|htm)$/i', $basename)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Potential executable file detected in filename'
            ]);
            return;
        }

        // Generate a safe unique filename
        $safeName = uniqid() . '_' . time() . '.' . $extension;
        $finalPath = $finalDir . '/' . $safeName;

        $fp = fopen($finalPath, 'w');
        if (!$fp) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to open final file for writing']);
            return;
        }

        // Iterate and merge
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $tempDir . '/' . $i . '.part';
            if (!file_exists($chunkPath)) {
                fclose($fp);
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => "Missing chunk index {$i}"]);
                return;
            }

            $chunkData = file_get_contents($chunkPath);
            fwrite($fp, $chunkData);
            unlink($chunkPath); // Delete chunk after merging
        }

        fclose($fp);

        // Validate MIME type of merged file
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $finalPath);
        finfo_close($finfo);

        $allowedMimeTypes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'];

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            unlink($finalPath); // Delete invalid file
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => "Invalid video file. MIME type '$mimeType' not allowed."
            ]);
            return;
        }

        rmdir($tempDir); // Remove temp directory

        echo json_encode([
            'status' => 'success',
            'file_path' => '/uploads/videos/' . $safeName
        ]);
    }
}
