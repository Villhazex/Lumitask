<?php

namespace App\Controllers\Api;

use App\Core\ActivityLogger;
use App\Core\Application;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Repositories\AttachmentRepository;
use App\Repositories\TaskRepository;

class AttachmentController extends Controller
{
    public function upload(): void
    {
        Auth::requireLogin();
        $this->validateCsrf();

        $taskId = (int) Request::input('task_id', 0);
        if (!(new TaskRepository())->canAccess($taskId, Auth::id())) {
            $this->jsonError('Tugas tidak ditemukan', 404);
        }

        $file = Request::file('file');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->jsonError('File tidak valid');
        }

        $config = Application::getInstance()->config('upload', []);
        $max = $config['max_size'] ?? 5242880;
        if ($file['size'] > $max) {
            $this->jsonError('File terlalu besar');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = $config['allowed'] ?? ['pdf', 'jpg', 'png'];
        if (!in_array($ext, $allowed, true)) {
            $this->jsonError('Tipe file tidak diizinkan');
        }

        $uploadDir = $config['path'] ?? dirname(__DIR__, 3) . '/storage/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $stored = bin2hex(random_bytes(12)) . '.' . $ext;
        $dest = $uploadDir . DIRECTORY_SEPARATOR . $stored;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $this->jsonError('Upload gagal');
        }

        $id = (new AttachmentRepository())->create(
            $taskId,
            Auth::id(),
            basename($file['name']),
            $stored,
            $file['type'] ?? 'application/octet-stream',
            (int) $file['size']
        );

        ActivityLogger::log(Auth::id(), 'attachment.uploaded', 'task', $taskId, ['file' => $stored]);
        $this->jsonOk(['attachment_id' => $id, 'stored_name' => $stored]);
    }
}
