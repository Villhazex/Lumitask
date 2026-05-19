<?php

namespace App\Repositories;

use App\Core\Database;

class UserRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByUsername(string $username): ?array
    {
        return $this->db->fetchOne(
            'SELECT id, username, email, password, role FROM users WHERE username = ? AND is_active = 1 LIMIT 1',
            [$username],
            's'
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT id, username, email, role FROM users WHERE id = ? LIMIT 1',
            [$id],
            'i'
        );
    }

    public function findIdByUsername(string $username): ?int
    {
        $row = $this->db->fetchOne(
            'SELECT id FROM users WHERE username = ? LIMIT 1',
            [$username],
            's'
        );

        return $row ? (int) $row['id'] : null;
    }

    public function create(string $username, string $email, string $passwordHash, string $role = 'member'): int
    {
        return $this->db->insert(
            'INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)',
            [$username, $email, $passwordHash, $role],
            'ssss'
        );
    }
}
