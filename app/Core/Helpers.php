<?php

declare(strict_types=1);

namespace App\Core;

class Helpers
{
    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    public static function formatTanggalIndo(?string $datetime): string
    {
        if (!$datetime) {
            return '-';
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return $datetime;
        }
        $bulan = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];
        $m = (int) date('n', $ts);
        return date('d', $ts) . ' ' . ($bulan[$m] ?? date('M', $ts)) . ' ' . date('Y, H:i', $ts) . ' WIB';
    }

    public static function formatBulanTahunIndo(?int $timestamp = null): string
    {
        $ts = $timestamp ?? time();
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $m = (int) date('n', $ts);
        return ($bulan[$m] ?? date('F', $ts)) . ' ' . date('Y', $ts);
    }

    public static function normalizePhoneDigits(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '62')) {
            $digits = '0' . substr($digits, 2);
        }
        return $digits;
    }

    public static function flash(string $key, ?string $message = null): ?string
    {
        if ($message !== null) {
            $_SESSION['_flash'][$key] = $message;
            return null;
        }
        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }

    public static function generateVisitCode(): string
    {
        $date = date('Ymd');
        $rand = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        return "BDI-{$date}-{$rand}";
    }

    /**
     * Resolves client IP safely. Only trusts X-Forwarded-For / X-Real-IP when REMOTE_ADDR
     * matches an explicitly configured trusted proxy IP or CIDR.
     *
     * @param string[] $trustedProxies
     */
    public static function getClientIp(array $trustedProxies = []): string
    {
        $remoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($remoteAddr === '') {
            return 'unknown';
        }

        if (empty($trustedProxies) || !self::ipInList($remoteAddr, $trustedProxies)) {
            return $remoteAddr;
        }

        $forwardedFor = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        if ($forwardedFor !== '') {
            $parts = array_reverse(array_map('trim', explode(',', $forwardedFor)));
            foreach ($parts as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP) && !self::ipInList($ip, $trustedProxies)) {
                    return $ip;
                }
            }
        }

        $realIp = trim((string) ($_SERVER['HTTP_X_REAL_IP'] ?? ''));
        if ($realIp !== '' && filter_var($realIp, FILTER_VALIDATE_IP)) {
            return $realIp;
        }

        return $remoteAddr;
    }

    private static function ipInList(string $ip, array $list): bool
    {
        foreach ($list as $entry) {
            $entry = trim((string) $entry);
            if ($entry === '') {
                continue;
            }
            if (!str_contains($entry, '/')) {
                if ($ip === $entry) {
                    return true;
                }
                continue;
            }

            [$subnet, $bits] = explode('/', $entry, 2);
            $bitsInt = (int) $bits;
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            if ($ipLong !== false && $subnetLong !== false && $bitsInt >= 0 && $bitsInt <= 32) {
                $mask = $bitsInt === 0 ? 0 : (~0 << (32 - $bitsInt));
                if (($ipLong & $mask) === ($subnetLong & $mask)) {
                    return true;
                }
            }
        }
        return false;
    }
}
