<?php

namespace App\Repositories;

use App\Core\Database;

class TaskListRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function slugify(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'lainnya';
    }

    public function seedDefaultLists(int $userId): void
    {
        $exists = $this->db->fetchOne(
            'SELECT id FROM task_lists WHERE user_id = ? AND slug = ? LIMIT 1',
            [$userId, 'pribadi'],
            'is'
        );
        if (!$exists) {
            $this->db->insert(
                'INSERT INTO task_lists (user_id, nama_list, slug, jenis, warna, ikon) VALUES (?, ?, ?, ?, ?, ?)',
                [$userId, 'Pribadi', 'pribadi', 'pribadi', '#008b8b', '✦'],
                'isssss'
            );
        }
    }

    public function userCanAccessList(int $userId, int $listId): bool
    {
        $row = $this->db->fetchOne(
            'SELECT tl.id FROM task_lists tl
             LEFT JOIN task_list_members tlm ON tlm.list_id = tl.id AND tlm.user_id = ?
             WHERE tl.id = ? AND tl.deleted_at IS NULL
               AND (tl.user_id = ? OR tlm.user_id IS NOT NULL)
             LIMIT 1',
            [$userId, $listId, $userId],
            'iii'
        );

        return $row !== null;
    }

    public function resolveListId(int $userId, string $kategori): ?int
    {
        $kategori = trim($kategori);
        if (preg_match('/^list:(\d+)$/', $kategori, $m)) {
            $listId = (int) $m[1];

            return $this->userCanAccessList($userId, $listId) ? $listId : null;
        }

        $slug = $this->slugify($kategori ?: 'pribadi');
        $row = $this->db->fetchOne(
            'SELECT id FROM task_lists WHERE user_id = ? AND slug = ? AND deleted_at IS NULL LIMIT 1',
            [$userId, $slug],
            'is'
        );

        if ($row) {
            return (int) $row['id'];
        }

        $count = $this->db->fetchOne('SELECT COUNT(*) AS c FROM task_lists WHERE user_id = ?', [$userId], 'i');
        $idx = (int) ($count['c'] ?? 0);
        $colors = ['#008b8b', '#3366ff', '#c1006b', '#6b00c1', '#b87200', '#006b38'];
        $nama = ucwords(str_replace('-', ' ', $slug));

        $this->db->insert(
            'INSERT IGNORE INTO task_lists (user_id, nama_list, slug, jenis, warna, ikon) VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $nama, $slug, 'pribadi', $colors[$idx % count($colors)], mb_strtoupper(mb_substr($nama, 0, 1))],
            'isssss'
        );

        $row = $this->db->fetchOne(
            'SELECT id FROM task_lists WHERE user_id = ? AND slug = ? LIMIT 1',
            [$userId, $slug],
            'is'
        );

        return $row ? (int) $row['id'] : null;
    }

    public function syncLegacyTasks(int $userId): void
    {
        $legacy = $this->db->fetchAll(
            'SELECT DISTINCT COALESCE(NULLIF(kategori, ""), "pribadi") AS kategori FROM tugas
             WHERE user_id = ? AND (list_id IS NULL OR list_id = 0) AND deleted_at IS NULL',
            [$userId],
            'i'
        );

        foreach ($legacy as $row) {
            $listId = $this->resolveListId($userId, $row['kategori']);
            if ($listId) {
                $this->db->execute(
                    'UPDATE tugas SET list_id = ? WHERE user_id = ? AND (list_id IS NULL OR list_id = 0)
                     AND COALESCE(NULLIF(kategori, ""), "pribadi") = ? AND deleted_at IS NULL',
                    [$listId, $userId, $row['kategori']],
                    'iis'
                );
            }
        }
    }

    public function listsForUser(int $userId): array
    {
        $this->seedDefaultLists($userId);
        $this->syncLegacyTasks($userId);

        return $this->db->fetchAll(
            'SELECT tl.*, owner.username AS owner_username,
                    CASE WHEN tl.user_id = ? THEN 1 ELSE 0 END AS is_owner,
                    GROUP_CONCAT(DISTINCT mu.username ORDER BY mu.username SEPARATOR ", ") AS member_usernames
             FROM task_lists tl
             LEFT JOIN users owner ON owner.id = tl.user_id
             LEFT JOIN task_list_members access ON access.list_id = tl.id AND access.user_id = ?
             LEFT JOIN task_list_members am ON am.list_id = tl.id
             LEFT JOIN users mu ON mu.id = am.user_id
             WHERE tl.deleted_at IS NULL AND (tl.user_id = ? OR access.user_id IS NOT NULL)
             GROUP BY tl.id
             ORDER BY tl.created_at ASC',
            [$userId, $userId, $userId],
            'iii'
        );
    }

    public function create(int $userId, string $nama, string $jenis, array $memberUsernames): array
    {
        $nama = trim($nama);
        if ($nama === '') {
            return ['ok' => false, 'msg' => 'Nama list tidak boleh kosong.'];
        }

        $jenis = $jenis === 'kelompok' ? 'kelompok' : 'pribadi';
        $memberIds = [];
        $missing = [];

        if ($jenis === 'kelompok') {
            $userRepo = new UserRepository();
            foreach ($memberUsernames as $uname) {
                $uname = trim($uname);
                if ($uname === '') {
                    continue;
                }
                $id = $userRepo->findIdByUsername($uname);
                if (!$id) {
                    $missing[] = $uname;
                } elseif ($id !== $userId) {
                    $memberIds[] = $id;
                }
            }
            if ($missing) {
                return ['ok' => false, 'msg' => 'Username tidak ditemukan: ' . implode(', ', $missing)];
            }
        }

        $slug = $this->slugify($nama);
        $dupe = $this->db->fetchOne(
            'SELECT id FROM task_lists WHERE user_id = ? AND slug = ? LIMIT 1',
            [$userId, $slug],
            'is'
        );
        if ($dupe) {
            return ['ok' => false, 'msg' => 'Nama list sudah digunakan.'];
        }

        $listId = $this->db->insert(
            'INSERT INTO task_lists (user_id, nama_list, slug, jenis, warna, ikon) VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $nama, $slug, $jenis, '#b87200', mb_strtoupper(mb_substr($nama, 0, 1))],
            'isssss'
        );

        foreach ($memberIds as $mid) {
            $this->db->execute(
                'INSERT IGNORE INTO task_list_members (list_id, user_id, role) VALUES (?, ?, ?)',
                [$listId, $mid, 'member'],
                'iis'
            );
        }

        return ['ok' => true, 'msg' => 'List berhasil dibuat.', 'list_id' => $listId];
    }

    public function update(int $userId, int $listId, string $nama, string $jenis, array $memberUsernames): array
    {
        $list = $this->db->fetchOne(
            'SELECT id, slug FROM task_lists WHERE id = ? AND user_id = ? LIMIT 1',
            [$listId, $userId],
            'ii'
        );
        if (!$list) {
            return ['ok' => false, 'msg' => 'List tidak ditemukan.'];
        }
        if ($list['slug'] === 'pribadi') {
            return ['ok' => false, 'msg' => 'List Pribadi utama tidak bisa diedit.'];
        }

        $slug = $this->slugify(trim($nama));
        $this->db->execute(
            'UPDATE task_lists SET nama_list = ?, slug = ?, jenis = ?, ikon = ? WHERE id = ? AND user_id = ?',
            [trim($nama), $slug, $jenis === 'kelompok' ? 'kelompok' : 'pribadi', mb_strtoupper(mb_substr($nama, 0, 1)), $listId, $userId],
            'ssssii'
        );

        $this->db->execute('DELETE FROM task_list_members WHERE list_id = ?', [$listId], 'i');
        if ($jenis === 'kelompok') {
            $userRepo = new UserRepository();
            foreach ($memberUsernames as $uname) {
                $id = $userRepo->findIdByUsername(trim($uname));
                if ($id && $id !== $userId) {
                    $this->db->execute(
                        'INSERT INTO task_list_members (list_id, user_id) VALUES (?, ?)',
                        [$listId, $id],
                        'ii'
                    );
                }
            }
        }

        return ['ok' => true, 'msg' => 'List berhasil diperbarui.'];
    }

    public function softDelete(int $userId, int $listId): array
    {
        $list = $this->db->fetchOne(
            'SELECT slug FROM task_lists WHERE id = ? AND user_id = ? LIMIT 1',
            [$listId, $userId],
            'ii'
        );
        if (!$list) {
            return ['ok' => false, 'msg' => 'List tidak ditemukan.'];
        }
        if ($list['slug'] === 'pribadi') {
            return ['ok' => false, 'msg' => 'List Pribadi tidak bisa dihapus.'];
        }

        $now = date('Y-m-d H:i:s');
        $this->db->execute('UPDATE tugas SET deleted_at = ? WHERE list_id = ?', [$now, $listId], 'si');
        $this->db->execute('UPDATE task_lists SET deleted_at = ? WHERE id = ?', [$now, $listId], 'si');

        return ['ok' => true, 'msg' => 'List berhasil dihapus.'];
    }
}
