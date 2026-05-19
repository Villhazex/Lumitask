<?php

namespace App\Repositories;

use App\Core\Database;

class PublicTaskRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(int $taskId, int $ownerId, string $title, string $visibility = 'link'): array
    {
        $token = bin2hex(random_bytes(16));
        $id = $this->db->insert(
            'INSERT INTO public_tasks (task_id, owner_id, title, visibility, share_token) VALUES (?, ?, ?, ?, ?)',
            [$taskId, $ownerId, $title, $visibility, $token],
            'iisss'
        );

        $this->db->execute(
            'UPDATE tugas SET is_public = 1, share_token = ? WHERE id = ?',
            [$token, $taskId],
            'si'
        );

        return ['id' => $id, 'share_token' => $token];
    }

    public function findByToken(string $token): ?array
    {
        return $this->db->fetchOne(
            'SELECT pt.*, t.nama_tugas, t.deskripsi, t.status_tugas, t.due_date, t.prioritas, u.username AS owner_name
             FROM public_tasks pt
             INNER JOIN tugas t ON t.id = pt.task_id AND t.deleted_at IS NULL
             INNER JOIN users u ON u.id = pt.owner_id
             WHERE pt.share_token = ? LIMIT 1',
            [$token],
            's'
        );
    }

    public function join(int $publicTaskId, int $userId, string $permission = 'view'): void
    {
        $this->db->execute(
            'INSERT IGNORE INTO public_task_members (public_task_id, user_id, permission) VALUES (?, ?, ?)',
            [$publicTaskId, $userId, $permission],
            'iis'
        );
    }

    public function listForUser(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT pt.*, t.nama_tugas, t.status_tugas
             FROM public_tasks pt
             INNER JOIN tugas t ON t.id = pt.task_id
             WHERE pt.owner_id = ? OR pt.id IN (SELECT public_task_id FROM public_task_members WHERE user_id = ?)
             ORDER BY pt.created_at DESC',
            [$userId, $userId],
            'ii'
        );
    }
}
