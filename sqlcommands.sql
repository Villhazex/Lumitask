-- Lumitask schema (fixed). For full v2 install use database/schema.sql
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  email VARCHAR(120) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','member') NOT NULL DEFAULT 'member',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_users_username (username),
  UNIQUE KEY uk_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS task_lists (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  nama_list VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  jenis ENUM('pribadi','kelompok') NOT NULL DEFAULT 'pribadi',
  warna VARCHAR(20) NOT NULL DEFAULT '#b87200',
  ikon VARCHAR(20) NOT NULL DEFAULT '◉',
  deleted_at DATETIME NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_list_user_slug (user_id, slug),
  CONSTRAINT fk_lists_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tugas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  list_id INT UNSIGNED NULL DEFAULT NULL,
  nama_tugas VARCHAR(255) NOT NULL,
  deskripsi TEXT NULL,
  status_tugas ENUM('Belum Selesai','Selesai') NOT NULL DEFAULT 'Belum Selesai',
  due_date DATE NULL DEFAULT NULL,
  prioritas ENUM('tinggi','sedang','rendah') NULL DEFAULT NULL,
  kategori VARCHAR(50) NULL DEFAULT NULL,
  is_public TINYINT(1) NOT NULL DEFAULT 0,
  share_token VARCHAR(64) NULL DEFAULT NULL,
  deleted_at DATETIME NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_tugas_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_tugas_list FOREIGN KEY (list_id) REFERENCES task_lists (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS task_list_members (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  list_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  role ENUM('owner','member') NOT NULL DEFAULT 'member',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_list_member (list_id, user_id),
  CONSTRAINT fk_members_list FOREIGN KEY (list_id) REFERENCES task_lists (id) ON DELETE CASCADE,
  CONSTRAINT fk_members_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
