<?php

require_once __DIR__.'/Tugas.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/TugasListManager.php';

// inheritance dari tugas
class TugasModel extends Tugas
{
    private $conn;
    private $listManager;

    public function __construct(
        $namaTugas = '',
        $statusTugas = 'Belum Selesai',
        $dueDate = null,
        $prioritas = '',
        $kategori = '',
        $userId = 0
    ) {
        parent::__construct(
            $namaTugas,
            $statusTugas,
            $dueDate,
            $prioritas,
            $kategori,
            $userId
        );

        $this->conn = connectDB();
        $this->listManager = new TugasListManager($this->conn);
    }

    public function getConn()
    {
        return $this->conn;
    }

    // CRUD Tugas

    public function tambahTugas()
    {
        $userId = (int) $this->userId;
        $listId = $this->listManager->resolveListId($userId, $this->kategori);
        if (!$listId) {
            return false;
        }

        $list = $this->conn->query("SELECT slug FROM task_lists WHERE id = $listId LIMIT 1");
        $listRow = $list ? $list->fetch_assoc() : null;
        $kategori = $this->conn->real_escape_string($listRow['slug'] ?? 'pribadi');
        $namaTugas = $this->conn->real_escape_string($this->namaTugas);
        $statusTugas = $this->conn->real_escape_string($this->statusTugas);
        $prioritas = $this->conn->real_escape_string($this->prioritas);
        $dueDate = $this->dueDate ? "'".$this->conn->real_escape_string($this->dueDate)."'" : 'NULL';

        return $this->conn->query("
            INSERT INTO tugas (user_id, list_id, nama_tugas, status_tugas, due_date, prioritas, kategori)
            VALUES ($userId, $listId, '$namaTugas', '$statusTugas', $dueDate, '$prioritas', '$kategori')
        ");
    }

    public function tampilTugas($user_id)
    {
        $user_id = (int) $user_id;
        $this->listManager->seedDefaultLists($user_id);
        $this->listManager->syncLegacyTasks($user_id);

        return $this->conn->query("
            SELECT tugas.*,
                   task_lists.id AS accessible_list_id,
                   COALESCE(task_lists.slug, tugas.kategori, 'pribadi') AS kategori,
                   task_lists.nama_list,
                   task_lists.jenis AS list_jenis,
                   task_lists.warna AS list_warna,
                   task_lists.ikon AS list_ikon,
                   owner.username AS owner_username,
                   CASE WHEN task_lists.user_id = $user_id THEN 1 ELSE 0 END AS is_owner
            FROM tugas
            LEFT JOIN task_lists ON task_lists.id = tugas.list_id
            LEFT JOIN users owner ON owner.id = task_lists.user_id
            LEFT JOIN task_list_members
                ON task_list_members.list_id = task_lists.id
               AND task_list_members.user_id = $user_id
            WHERE task_lists.user_id = $user_id
               OR task_list_members.user_id IS NOT NULL
               OR (tugas.user_id = $user_id AND tugas.list_id IS NULL)
            ORDER BY tugas.id DESC
        ");
    }

    public function hapusTugas($id, $user_id)
    {
        $id = (int) $id;
        $user_id = (int) $user_id;

        return $this->conn->query("
            DELETE tugas FROM tugas
            LEFT JOIN task_lists ON task_lists.id = tugas.list_id
            LEFT JOIN task_list_members
                ON task_list_members.list_id = task_lists.id
               AND task_list_members.user_id = $user_id
            WHERE tugas.id = $id
              AND (task_lists.user_id = $user_id OR task_list_members.user_id IS NOT NULL OR tugas.user_id = $user_id)
        ");
    }

    public function selesaiTugas($id, $user_id)
    {
        $id = (int) $id;
        $user_id = (int) $user_id;

        return $this->conn->query("
            UPDATE tugas
            LEFT JOIN task_lists ON task_lists.id = tugas.list_id
            LEFT JOIN task_list_members
                ON task_list_members.list_id = task_lists.id
               AND task_list_members.user_id = $user_id
            SET tugas.status_tugas = 'Selesai'
            WHERE tugas.id = $id
              AND (task_lists.user_id = $user_id OR task_list_members.user_id IS NOT NULL OR tugas.user_id = $user_id)
        ");
    }

    public function hapusSemua($user_id)
    {
        $user_id = (int) $user_id;

        return $this->conn->query("
            DELETE tugas FROM tugas
            INNER JOIN task_lists ON task_lists.id = tugas.list_id
            WHERE task_lists.user_id = $user_id
        ");
    }

    public function hapusSelesai($user_id)
    {
        $user_id = (int) $user_id;

        return $this->conn->query("
            DELETE tugas FROM tugas
            INNER JOIN task_lists ON task_lists.id = tugas.list_id
            WHERE tugas.status_tugas = 'Selesai'
              AND task_lists.user_id = $user_id
        ");
    }

    // Proxy ke TugasListManager

    public function tambahList($user_id, $nama_list, $jenis = 'pribadi', $member_usernames = [])
    {
        return $this->listManager->tambahList($user_id, $nama_list, $jenis, $member_usernames);
    }

    public function editList($user_id, $list_id, $nama_list, $jenis = 'pribadi', $member_usernames = [])
    {
        return $this->listManager->editList($user_id, $list_id, $nama_list, $jenis, $member_usernames);
    }

    public function hapusList($user_id, $list_id)
    {
        return $this->listManager->hapusList($user_id, $list_id);
    }

    public function tampilLists($user_id)
    {
        return $this->listManager->tampilLists($user_id);
    }
}
