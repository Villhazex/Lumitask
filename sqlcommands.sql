SET NAMES utf8mb4; 

-- 1. Tabel users (login & register)

CREATE TABLE users (
  id INT(11) NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  email VARCHAR(120) NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY username (username), 
  UNIQUE KEY email (email) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 

-- 2. Tabel task_lists (list pribadi / kelompok)

CREATE TABLE task_lists (
  id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  nama_list VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  jenis VARCHAR(20) NOT NULL DEFAULT 'pribadi',
  warna VARCHAR(20) NOT NULL DEFAULT '#b87200',
  ikon VARCHAR(20) NOT NULL DEFAULT '.',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_user_slug (user_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Tabel tugas

CREATE TABLE tugas (
  id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  list_id INT(11) DEFAULT NULL,
  nama_tugas VARCHAR(255) NOT NULL,
  status_tugas VARCHAR(50) NOT NULL DEFAULT 'Belum Selesai',
  due_date DATE DEFAULT NULL,
  prioritas VARCHAR(20) DEFAULT NULL,
  kategori VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY fk_user_tugas (user_id),
  CONSTRAINT fk_user_tugas
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE 

-- 4. Tabel task_list_members (anggota list kelompok)

CREATE TABLE task_list_members (
  id INT(11) NOT NULL AUTO_INCREMENT,
  list_id INT(11) NOT NULL,
  user_id INT(11) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_list_member (list_id, user_id),
  KEY idx_member_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;