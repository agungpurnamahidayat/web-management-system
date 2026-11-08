-- Web Management System: struktur database + data contoh
-- Cara pakai: buka phpMyAdmin -> tab Import -> pilih file ini -> Go.

CREATE DATABASE IF NOT EXISTS web_management_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE web_management_system;

CREATE TABLE IF NOT EXISTS users (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  username   VARCHAR(50)  NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  role       ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS informasi (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  judul      VARCHAR(150) NOT NULL,
  kategori   VARCHAR(100) NOT NULL,
  isi        TEXT NOT NULL,
  status     ENUM('Draft','Published') NOT NULL DEFAULT 'Draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- AKUN DEMO UNTUK PENGGUNAAN LOKAL SAJA
-- username: admin | password: Admin12345
-- Segera ganti password ini jika aplikasi dipasang di server publik.
INSERT INTO users (name, username, password, role) VALUES
('Administrator', 'admin', '$2y$10$NAgxUniJ4ZotYVwvei0ZOOGdfyXnYkmHpTD.xL/gWld.pzLDjMFh.', 'admin');

INSERT INTO informasi (judul, kategori, isi, status) VALUES
('Selamat datang', 'Pengumuman', 'Ini adalah contoh data informasi.', 'Published'),
('Jadwal pemeliharaan sistem', 'Operasional', 'Contoh data dengan status draft.', 'Draft');
