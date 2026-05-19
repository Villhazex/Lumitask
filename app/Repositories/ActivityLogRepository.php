<?php

namespace App\Repositories;

use App\Core\Database;

class ActivityLogRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(array $data): int
    {
        return $this->db->insert(
            'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, meta, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'],
                $data['action'],
                $data['entity_type'],
                $data['entity_id'],
                $data['meta'],
                $data['ip_address'],
                $data['user_agent'],
            ],
            'ississs'
        );
    }

    public function recentForUser(int $userId, int $limit = 30): array
    {
        return $this->db->fetchAll(
            'SELECT al.*, u.username
             FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.user_id = ? OR al.user_id IN (
                SELECT DISTINCT user_id FROM task_list_members tlm
                INNER JOIN task_list_members mine ON mine.list_id = tlm.list_id AND mine.user_id = ?
             )
             ORDER BY al.created_at DESC
             LIMIT ?',
            [$userId, $userId, $limit],
            'iii'
        );
    }

    public function since(string $since, int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM activity_logs WHERE created_at > ? AND user_id != ? ORDER BY created_at ASC LIMIT 50',
            [$since, $userId],
            'si'
        );
    }
}
