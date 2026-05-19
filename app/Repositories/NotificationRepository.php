<?php

namespace App\Repositories;

use App\Core\Database;

class NotificationRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(int $userId, string $type, string $title, string $message, ?string $entityType = null, ?int $entityId = null): int
    {
        return $this->db->insert(
            'INSERT INTO notifications (user_id, type, title, message, entity_type, entity_id) VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $type, $title, $message, $entityType, $entityId],
            'issssi'
        );
    }

    public function forUser(int $userId, bool $unreadOnly = false): array
    {
        $sql = 'SELECT * FROM notifications WHERE user_id = ?';
        $params = [$userId];
        $types = 'i';

        if ($unreadOnly) {
            $sql .= ' AND is_read = 0';
        }

        $sql .= ' ORDER BY created_at DESC LIMIT 50';

        return $this->db->fetchAll($sql, $params, $types);
    }

    public function markRead(int $userId, ?int $notificationId = null): int
    {
        if ($notificationId) {
            return $this->db->execute(
                'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?',
                [$notificationId, $userId],
                'ii'
            );
        }

        return $this->db->execute(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ?',
            [$userId],
            'i'
        );
    }

    public function syncDeadlineReminders(int $userId): void
    {
        $tasks = $this->db->fetchAll(
            "SELECT id, nama_tugas, due_date FROM tugas
             WHERE user_id = ? AND deleted_at IS NULL AND status_tugas = 'Belum Selesai'
             AND due_date IS NOT NULL
             AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)",
            [$userId],
            'i'
        );

        foreach ($tasks as $task) {
            $exists = $this->db->fetchOne(
                "SELECT id FROM notifications WHERE user_id = ? AND type = 'deadline' AND entity_id = ? AND DATE(created_at) = CURDATE()",
                [$userId, $task['id']],
                'ii'
            );
            if ($exists) {
                continue;
            }
            $this->create(
                $userId,
                'deadline',
                'Deadline mendekat',
                'Tugas "' . $task['nama_tugas'] . '" jatuh tempo ' . $task['due_date'],
                'task',
                (int) $task['id']
            );
        }
    }
}
