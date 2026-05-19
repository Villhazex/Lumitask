<?php

namespace App\Core;

use mysqli;
use mysqli_stmt;

class Database
{
    private static ?self $instance = null;
    private mysqli $conn;

    private function __construct(array $config)
    {
        $this->conn = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database']
        );

        if ($this->conn->connect_error) {
            throw new \RuntimeException('Database connection failed: ' . $this->conn->connect_error);
        }

        $this->conn->set_charset($config['charset'] ?? 'utf8mb4');
    }

    public static function boot(array $config): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Database not initialized.');
        }

        return self::$instance;
    }

    public function connection(): mysqli
    {
        return $this->conn;
    }

    /** @param array<int|string,mixed> $params */
    public function query(string $sql, array $params = [], string $types = '')
    {
        if ($params === []) {
            return $this->conn->query($sql);
        }

        $stmt = $this->prepare($sql, $params, $types);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ?? true;
    }

    /** @return array<int,array<string,mixed>> */
    public function fetchAll(string $sql, array $params = [], string $types = ''): array
    {
        $result = $this->query($sql, $params, $types);
        if ($result instanceof \mysqli_result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }

        return [];
    }

    /** @return array<string,mixed>|null */
    public function fetchOne(string $sql, array $params = [], string $types = ''): ?array
    {
        $rows = $this->fetchAll($sql, $params, $types);

        return $rows[0] ?? null;
    }

    public function insert(string $sql, array $params = [], string $types = ''): int
    {
        $this->query($sql, $params, $types);

        return (int) $this->conn->insert_id;
    }

    public function execute(string $sql, array $params = [], string $types = ''): int
    {
        $this->query($sql, $params, $types);

        return $this->conn->affected_rows;
    }

    /** @param array<int|string,mixed> $params */
    private function prepare(string $sql, array $params, string $types): mysqli_stmt
    {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new \RuntimeException('Prepare failed: ' . $this->conn->error);
        }

        if ($params !== []) {
            if ($types === '') {
                $types = $this->detectTypes($params);
            }
            $stmt->bind_param($types, ...array_values($params));
        }

        return $stmt;
    }

    /** @param array<int|string,mixed> $params */
    private function detectTypes(array $params): string
    {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }

        return $types;
    }
}
