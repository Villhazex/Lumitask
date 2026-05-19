<?php

/**
 * Run: php database/migrate.php
 * Upgrades existing Lumitask DB to v2 schema (non-destructive where possible).
 */

$config = require dirname(__DIR__) . '/config/database.php';
$conn = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);

if ($conn->connect_error) {
    exit("Connection failed: {$conn->connect_error}\n");
}

$conn->set_charset('utf8mb4');

$migrations = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('admin','member') NOT NULL DEFAULT 'member' AFTER password",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
    "ALTER TABLE task_lists ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL DEFAULT NULL",
    "ALTER TABLE task_lists ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP",
    "ALTER TABLE tugas ADD COLUMN IF NOT EXISTS deskripsi TEXT NULL AFTER nama_tugas",
    "ALTER TABLE tugas ADD COLUMN IF NOT EXISTS is_public TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE tugas ADD COLUMN IF NOT EXISTS share_token VARCHAR(64) NULL",
    "ALTER TABLE tugas ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL DEFAULT NULL",
    "ALTER TABLE tugas ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
    "ALTER TABLE tugas ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP",
    "ALTER TABLE task_list_members ADD COLUMN IF NOT EXISTS role ENUM('owner','member') NOT NULL DEFAULT 'member' AFTER user_id",
];

foreach ($migrations as $sql) {
    if ($conn->query($sql)) {
        echo "OK: $sql\n";
    } else {
        echo "SKIP/FAIL: $sql — {$conn->error}\n";
    }
}

$tables = [
    'activity_logs' => "CREATE TABLE IF NOT EXISTS activity_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NULL,
        action VARCHAR(80) NOT NULL,
        entity_type VARCHAR(50) NOT NULL,
        entity_id INT UNSIGNED NULL,
        meta JSON NULL,
        ip_address VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_activity_user (user_id),
        KEY idx_activity_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'notifications' => "CREATE TABLE IF NOT EXISTS notifications (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        entity_type VARCHAR(50) NULL,
        entity_id INT UNSIGNED NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_notif_user (user_id, is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'task_attachments' => "CREATE TABLE IF NOT EXISTS task_attachments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        task_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        stored_name VARCHAR(255) NOT NULL,
        mime_type VARCHAR(120) NOT NULL,
        file_size INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_attach_task (task_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'public_tasks' => "CREATE TABLE IF NOT EXISTS public_tasks (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        task_id INT UNSIGNED NOT NULL,
        owner_id INT UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        visibility ENUM('link','public','team') DEFAULT 'link',
        allow_comments TINYINT(1) DEFAULT 1,
        allow_edit TINYINT(1) DEFAULT 0,
        share_token VARCHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_public_task (task_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'public_task_members' => "CREATE TABLE IF NOT EXISTS public_task_members (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_task_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        permission ENUM('view','comment','edit') DEFAULT 'view',
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_public_member (public_task_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($tables as $name => $sql) {
    if ($conn->query($sql)) {
        echo "Table $name ready.\n";
    } else {
        echo "Table $name error: {$conn->error}\n";
    }
}

echo "Migration finished.\n";
