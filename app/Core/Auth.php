<?php

declare(strict_types=1);

namespace App\Core;

class Auth
{
    private const SESSION_KEY = 'admin_user';

    public static function check(): bool
    {
        return !empty($_SESSION[self::SESSION_KEY]['id']);
    }

    public static function user(): ?array
    {
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    public static function login(array $admin): void
    {
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = [
            'id' => (int) $admin['id'],
            'username' => $admin['username'],
            'nama_lengkap' => $admin['nama_lengkap'],
            'role' => $admin['role'],
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        session_regenerate_id(true);
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            Helpers::redirect('/admin/login');
        }
    }
}
