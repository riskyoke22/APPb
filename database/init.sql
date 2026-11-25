CREATE DATABASE IF NOT EXISTS `buku_tamu_bdi` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `buku_tamu_bdi`;

CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `role` VARCHAR(30) NOT NULL DEFAULT 'administrator',
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `guests` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode_kunjungan` VARCHAR(30) NOT NULL UNIQUE,
    `nama_lengkap` VARCHAR(120) NOT NULL,
    `instansi` VARCHAR(150) NOT NULL,
    `no_hp` VARCHAR(25) NOT NULL,
    `email` VARCHAR(120) NULL,
    `tujuan` VARCHAR(120) NOT NULL,
    `bertemu_dengan` VARCHAR(120) NULL,
    `keperluan` TEXT NOT NULL,
    `foto_path` VARCHAR(255) NOT NULL,
    `status` ENUM('berkunjung', 'selesai') NOT NULL DEFAULT 'berkunjung',
    `waktu_masuk` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `waktu_keluar` DATETIME NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_waktu_masuk` (`waktu_masuk`),
    INDEX `idx_status` (`status`),
    INDEX `idx_no_hp` (`no_hp`),
    INDEX `idx_tujuan` (`tujuan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
