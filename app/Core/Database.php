<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static bool $schemaChecked = false;

    public static function getConnection(array $config): PDO
    {
        if (self::$instance === null) {
            $db = $config['db'];
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $db['host'],
                $db['port'],
                $db['name'],
                $db['charset']
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                self::$instance = new PDO($dsn, $db['user'], $db['pass'], $options);
            } catch (PDOException $e) {
                throw $e;
            }
        }

        if (!self::$schemaChecked) {
            self::ensureSchema(self::$instance);
            self::$schemaChecked = true;
        }

        return self::$instance;
    }

    private static function ensureSchema(PDO $pdo): void
    {
        $pdo->exec("
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
        ");

        $pdo->exec("
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
        ");

        // Seed default admin if none exists (username: admin, password: adminbdi)
        $stmt = $pdo->query("SELECT COUNT(*) FROM `admins`");
        if ((int) $stmt->fetchColumn() === 0) {
            $hash = password_hash('adminbdi', PASSWORD_DEFAULT);
            $insert = $pdo->prepare("
                INSERT INTO `admins` (`username`, `password_hash`, `nama_lengkap`, `role`)
                VALUES (:username, :password_hash, :nama_lengkap, :role)
            ");
            $insert->execute([
                ':username' => 'admin',
                ':password_hash' => $hash,
                ':nama_lengkap' => 'Administrator BDI Surabaya',
                ':role' => 'superadmin',
            ]);
        }
    }
}
