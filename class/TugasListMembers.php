<?php

// Menangani pencarian user berdasarkan username dan pengelolaan anggota list.

class TugasListMembers
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    private function CariUser($usernames)
    {
        $ids = [];
        $missing = [];
        $clean = [];

        foreach ($usernames as $username) {
            $username = trim($username);
            if ($username !== '' && !in_array($username, $clean, true)) {
                $clean[] = $username;
            }
        }

        foreach ($clean as $username) {
            $stmt = $this->conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $ids[] = (int) $result->fetch_assoc()['id'];
            } else {
                $missing[] = $username;
            }
        }

        return ['ids' => $ids, 'missing' => $missing];
    }

    private function applyListMembers($list_id, $owner_id, $jenis, $member_usernames)
    {
        $list_id = (int) $list_id;
        $owner_id = (int) $owner_id;
        $jenis = $jenis === 'kelompok' ? 'kelompok' : 'pribadi';

        if ($jenis === 'pribadi') {
            $this->conn->query("DELETE FROM task_list_members WHERE list_id = $list_id");

            return ['ok' => true, 'msg' => ''];
        }

        $members = $this->CariUser($member_usernames);
        $memberIds = array_values(array_filter($members['ids'], fn ($id) => $id !== $owner_id));

        if (!empty($members['missing'])) {
            return ['ok' => false, 'msg' => 'Username tidak ditemukan: '.implode(', ', $members['missing']).'.'];
        }

        $this->conn->query("DELETE FROM task_list_members WHERE list_id = $list_id");

        foreach ($memberIds as $member_id) {
            $member_id = (int) $member_id;
            $this->conn->query("
                INSERT IGNORE INTO task_list_members (list_id, user_id)
                VALUES ($list_id, $member_id)
            ");
        }

        return ['ok' => true, 'msg' => ''];
    }

    public function findMembers($usernames)
    {
        return $this->CariUser($usernames);
    }

    public function applyMembers($list_id, $owner_id, $jenis, $member_usernames)
    {
        return $this->applyListMembers($list_id, $owner_id, $jenis, $member_usernames);
    }
}
