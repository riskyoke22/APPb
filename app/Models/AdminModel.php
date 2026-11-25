<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class AdminModel
{
    public function __construct(private PDO $db)
    {
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM admins WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM admins WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE admins SET last_login = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function updateProfile(int $id, string $namaLengkap, ?string $newPassword = null): void
    {
        if ($newPassword !== null && $newPassword !== '') {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("
                UPDATE admins SET nama_lengkap = :nama, password_hash = :hash WHERE id = :id
            ");
            $stmt->execute([
                ':nama' => $namaLengkap,
                ':hash' => $hash,
                ':id' => $id,
            ]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE admins SET nama_lengkap = :nama WHERE id = :id
            ");
            $stmt->execute([
                ':nama' => $namaLengkap,
                ':id' => $id,
            ]);
        }
    }
}
