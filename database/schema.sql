SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS lumitask CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lumitask;

DROP TABLE IF EXISTS task_attachments;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS public_task_members;
DROP TABLE IF EXISTS public_tasks;
DROP TABLE IF EXISTS task_list_members;
DROP TABLE IF EXISTS tugas;
DROP TABLE IF EXISTS task_lists;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- Users with roles
CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(120) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'member') NOT NULL DEFAULT 'member',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_users_username (username),
    UNIQUE KEY uk_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Task lists
CREATE TABLE task_lists (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    nama_list VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    jenis ENUM('pribadi', 'kelompok') NOT NULL DEFAULT 'pribadi',
    warna VARCHAR(20) NOT NULL DEFAULT '#b87200',
    ikon VARCHAR(20) NOT NULL DEFAULT '◉',
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_list_user_slug (user_id, slug),
    KEY idx_lists_user (user_id),
    CONSTRAINT fk_lists_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tasks (soft delete)
CREATE TABLE tugas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    list_id INT UNSIGNED NULL DEFAULT NULL,
    nama_tugas VARCHAR(255) NOT NULL,
    deskripsi TEXT NULL,
    status_tugas ENUM('Belum Selesai', 'Selesai') NOT NULL DEFAULT 'Belum Selesai',
    due_date DATE NULL DEFAULT NULL,
    prioritas ENUM('tinggi', 'sedang', 'rendah') NULL DEFAULT NULL,
    kategori VARCHAR(50) NULL DEFAULT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    share_token VARCHAR(64) NULL DEFAULT NULL,
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tugas_user (user_id),
    KEY idx_tugas_list (list_id),
    KEY idx_tugas_status (status_tugas),
    KEY idx_tugas_due (due_date),
    KEY idx_tugas_deleted (deleted_at),
    KEY idx_tugas_share (share_token),
    FULLTEXT KEY ft_tugas_search (nama_tugas, deskripsi),
    CONSTRAINT fk_tugas_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_tugas_list FOREIGN KEY (list_id) REFERENCES task_lists (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- List members
CREATE TABLE task_list_members (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    list_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role ENUM('owner', 'member') NOT NULL DEFAULT 'member',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_list_member (list_id, user_id),
    KEY idx_member_user (user_id),
    CONSTRAINT fk_members_list FOREIGN KEY (list_id) REFERENCES task_lists (id) ON DELETE CASCADE,
    CONSTRAINT fk_members_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Public collaborative tasks (advanced sharing)
CREATE TABLE public_tasks (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id INT UNSIGNED NOT NULL,
    owner_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    visibility ENUM('link', 'public', 'team') NOT NULL DEFAULT 'link',
    allow_comments TINYINT(1) NOT NULL DEFAULT 1,
    allow_edit TINYINT(1) NOT NULL DEFAULT 0,
    share_token VARCHAR(64) NOT NULL,
    expires_at DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_public_task (task_id),
    UNIQUE KEY uk_public_token (share_token),
    CONSTRAINT fk_public_task FOREIGN KEY (task_id) REFERENCES tugas (id) ON DELETE CASCADE,
    CONSTRAINT fk_public_owner FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE public_task_members (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_task_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    permission ENUM('view', 'comment', 'edit') NOT NULL DEFAULT 'view',
    joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_public_member (public_task_id, user_id),
    CONSTRAINT fk_ptm_public FOREIGN KEY (public_task_id) REFERENCES public_tasks (id) ON DELETE CASCADE,
    CONSTRAINT fk_ptm_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity log
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT UNSIGNED NULL,
    meta JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_activity_user (user_id),
    KEY idx_activity_entity (entity_type, entity_id),
    KEY idx_activity_created (created_at),
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications (deadline reminders, collaboration)
CREATE TABLE notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id INT UNSIGNED NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notif_user_read (user_id, is_read),
    KEY idx_notif_created (created_at),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- File attachments
CREATE TABLE task_attachments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attach_task (task_id),
    CONSTRAINT fk_attach_task FOREIGN KEY (task_id) REFERENCES tugas (id) ON DELETE CASCADE,
    CONSTRAINT fk_attach_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin (password: admin123) — change after first login
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@lumitask.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
