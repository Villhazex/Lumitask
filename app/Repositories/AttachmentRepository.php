<?php

namespace App\Repositories;

use App\Core\Database;

class AttachmentRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(int $taskId, int $userId, string $original, string $stored, string $mime, int $size): int
    {
        return $this->db->insert(
            'INSERT INTO task_attachments (task_id, user_id, original_name, stored_name, mime_type, file_size)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$taskId, $userId, $original, $stored, $mime, $size],
            'iissi'
        );
    }

    public function forTask(int $taskId): array
    {
        return $this->db->fetchAll(
            'SELECT id, original_name, stored_name, mime_type, file_size, created_at
             FROM task_attachments WHERE task_id = ? ORDER BY created_at DESC',
            [$taskId],
            'i'
        );
    }
}
