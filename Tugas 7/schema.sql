
-- Skema basis data Modul Buku Tamu Digital Perpustakaan
-- Database baru: perpustakaan

CREATE DATABASE IF NOT EXISTS perpustakaan
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE perpustakaan;

CREATE TABLE IF NOT EXISTS buku_tamu (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,
    pesan TEXT NOT NULL,
    tanggal_kirim DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;