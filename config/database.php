<?php

return [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'lumitask',
    'charset' => 'utf8mb4',
];

/**
 * Legacy helper — kept for backward compatibility during migration.
 */
function connectDB(
    string $host = 'localhost',
    string $username = 'root',
    string $password = '',
    string $database = 'lumitask'
): mysqli {
    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        exit('Koneksi gagal: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');

    return $conn;
}
