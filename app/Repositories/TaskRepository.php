<?php

namespace App\Repositories;

use App\Core\Database;

class TaskRepository
{
    private Database $db;
    private TaskListRepository $lists;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->lists = new TaskListRepository();
    }

    public function allForUser(int $userId, array $filters = []): array
    {
        $this->lists->seedDefaultLists($userId);
        $this->lists->syncLegacyTasks($userId);

        $sql = "SELECT tugas.*,
                       task_lists.id AS accessible_list_id,
                       COALESCE(task_lists.slug, tugas.kategori, 'pribadi') AS kategori,
                       task_lists.nama_list,
                       task_lists.jenis AS list_jenis,
                       task_lists.warna AS list_warna,
                       task_lists.ikon AS list_ikon,
                       owner.username AS owner_username,
                       CASE WHEN task_lists.user_id = ? THEN 1 ELSE 0 END AS is_owner
                FROM tugas
                LEFT JOIN task_lists ON task_lists.id = tugas.list_id
                LEFT JOIN users owner ON owner.id = task_lists.user_id
                LEFT JOIN task_list_members ON task_list_members.list_id = task_lists.id AND task_list_members.user_id = ?
                WHERE tugas.deleted_at IS NULL
                  AND (task_lists.user_id = ? OR task_list_members.user_id IS NOT NULL OR (tugas.user_id = ? AND tugas.list_id IS NULL))";

        $params = [$userId, $userId, $userId, $userId];
        $types = 'iiii';

        if (!empty($filters['q'])) {
            $sql .= ' AND (tugas.nama_tugas LIKE ? OR tugas.deskripsi LIKE ?)';
            $q = '%' . $filters['q'] . '%';
            $params[] = $q;
            $params[] = $q;
            $types .= 'ss';
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND tugas.status_tugas = ?';
            $params[] = $filters['status'];
            $types .= 's';
        }

        if (!empty($filters['prioritas'])) {
            $sql .= ' AND tugas.prioritas = ?';
            $params[] = $filters['prioritas'];
            $types .= 's';
        }

        if (!empty($filters['list_id'])) {
            $sql .= ' AND tugas.list_id = ?';
            $params[] = (int) $filters['list_id'];
            $types .= 'i';
        }

        if (!empty($filters['due'])) {
            if ($filters['due'] === 'overdue') {
                $sql .= " AND tugas.due_date < CURDATE() AND tugas.status_tugas = 'Belum Selesai'";
            } elseif ($filters['due'] === 'upcoming') {
                $sql .= ' AND tugas.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)';
            }
        }

        $sql .= ' ORDER BY tugas.id DESC';

        return $this->db->fetchAll($sql, $params, $types);
    }

    public function create(int $userId, array $data): ?int
    {
        $listId = $this->lists->resolveListId($userId, (string) ($data['kategori'] ?? ''));
        if (!$listId) {
            return null;
        }

        $list = $this->db->fetchOne('SELECT slug FROM task_lists WHERE id = ?', [$listId], 'i');
        $slug = $list['slug'] ?? 'pribadi';

        return $this->db->insert(
            'INSERT INTO tugas (user_id, list_id, nama_tugas, deskripsi, status_tugas, due_date, prioritas, kategori)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $listId,
                $data['nama_tugas'],
                $data['deskripsi'] ?? null,
                'Belum Selesai',
                $data['due_date'] ?: null,
                $data['prioritas'] ?: null,
                $slug,
            ],
            'iissssss'
        );
    }

    public function canAccess(int $taskId, int $userId): bool
    {
        $row = $this->db->fetchOne(
            'SELECT tugas.id FROM tugas
             LEFT JOIN task_lists ON task_lists.id = tugas.list_id
             LEFT JOIN task_list_members ON task_list_members.list_id = task_lists.id AND task_list_members.user_id = ?
             WHERE tugas.id = ? AND tugas.deleted_at IS NULL
               AND (task_lists.user_id = ? OR task_list_members.user_id IS NOT NULL OR tugas.user_id = ?)',
            [$userId, $taskId, $userId, $userId],
            'iiii'
        );

        return $row !== null;
    }

    public function complete(int $taskId, int $userId): bool
    {
        if (!$this->canAccess($taskId, $userId)) {
            return false;
        }

        return $this->db->execute(
            "UPDATE tugas SET status_tugas = 'Selesai', updated_at = NOW() WHERE id = ?",
            [$taskId],
            'i'
        ) > 0;
    }

    public function softDelete(int $taskId, int $userId): bool
    {
        if (!$this->canAccess($taskId, $userId)) {
            return false;
        }

        return $this->db->execute(
            'UPDATE tugas SET deleted_at = NOW() WHERE id = ?',
            [$taskId],
            'i'
        ) > 0;
    }

    public function restore(int $taskId, int $userId): bool
    {
        return $this->db->execute(
            'UPDATE tugas SET deleted_at = NULL WHERE id = ? AND user_id = ?',
            [$taskId, $userId],
            'ii'
        ) > 0;
    }

    public function stats(int $userId): array
    {
        $tasks = $this->allForUser($userId);
        $total = count($tasks);
        $done = count(array_filter($tasks, fn ($t) => $t['status_tugas'] === 'Selesai'));
        $overdue = 0;
        $upcoming = 0;
        $today = strtotime('today');

        foreach ($tasks as $t) {
            if (empty($t['due_date']) || $t['status_tugas'] === 'Selesai') {
                continue;
            }
            $due = strtotime($t['due_date']);
            if ($due < $today) {
                $overdue++;
            } elseif ($due <= $today + 86400 * 3) {
                $upcoming++;
            }
        }

        $byPriority = ['tinggi' => 0, 'sedang' => 0, 'rendah' => 0];
        foreach ($tasks as $t) {
            if ($t['prioritas'] && isset($byPriority[$t['prioritas']])) {
                $byPriority[$t['prioritas']]++;
            }
        }

        return [
            'total' => $total,
            'done' => $done,
            'pending' => $total - $done,
            'percent' => $total > 0 ? round($done / $total * 100) : 0,
            'overdue' => $overdue,
            'upcoming' => $upcoming,
            'by_priority' => $byPriority,
        ];
    }
}
