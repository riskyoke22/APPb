<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\RateLimiter;
use App\Models\AdminModel;
use App\Models\GuestModel;

class AdminController
{
    public function __construct(
        private AdminModel $admins,
        private GuestModel $guests,
        private array $config
    ) {
    }

    public function loginForm(): void
    {
        if (Auth::check()) {
            Helpers::redirect('/admin');
        }
        $appName = $this->config['app_name'];
        $error = Helpers::flash('error');
        require dirname(__DIR__, 2) . '/views/admin/login.php';
    }

    public function loginSubmit(): void
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Helpers::flash('error', 'Sesi tidak valid. Silakan coba lagi.');
            Helpers::redirect('/admin/login');
        }

        $ip = Helpers::getClientIp($this->config['trusted_proxies'] ?? []);
        $rateLimiter = new RateLimiter();
        $limitKey = 'admin_login_' . $ip;

        $attempt = $rateLimiter->attempt($limitKey, 5, 900);
        if (!$attempt['allowed']) {
            $minutes = max(1, (int) ceil($attempt['remaining_seconds'] / 60));
            Helpers::flash('error', "Terlalu banyak percobaan login gagal. Silakan coba lagi dalam {$minutes} menit.");
            Helpers::redirect('/admin/login');
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $admin = $this->admins->findByUsername($username);
        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            Helpers::flash('error', 'Username atau password salah.');
            Helpers::redirect('/admin/login');
        }

        $rateLimiter->clear($limitKey);
        $this->admins->updateLastLogin((int) $admin['id']);
        Auth::login($admin);
        Helpers::redirect('/admin');
    }

    public function logout(): void
    {
        Auth::logout();
        Helpers::redirect('/admin/login');
    }

    public function dashboard(): void
    {
        Auth::requireAuth();

        $filters = [
            'search' => trim((string) ($_GET['q'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'tujuan' => trim((string) ($_GET['tujuan'] ?? '')),
            'date_from' => trim((string) ($_GET['from'] ?? '')),
            'date_to' => trim((string) ($_GET['to'] ?? '')),
        ];

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 15;

        $stats = $this->guests->getDashboardStats();
        $totalRows = $this->guests->countFiltered($filters);
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $guestList = $this->guests->listFiltered($filters, $perPage, $offset);

        $tujuanList = $this->config['tujuan_list'];
        $adminUser = Auth::user();
        $appName = $this->config['app_name'];
        $flashSuccess = Helpers::flash('success');
        $flashError = Helpers::flash('error');

        require dirname(__DIR__, 2) . '/views/admin/dashboard.php';
    }

    public function checkoutGuest(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Helpers::flash('error', 'Token CSRF tidak valid.');
            Helpers::redirect('/admin');
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && $this->guests->checkout($id)) {
            Helpers::flash('success', 'Status tamu berhasil diubah menjadi Selesai (Check-Out).');
        } else {
            Helpers::flash('error', 'Gagal memperbarui status tamu.');
        }

        Helpers::redirect($this->adminRedirectTarget());
    }

    public function deleteGuest(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Helpers::flash('error', 'Token CSRF tidak valid.');
            Helpers::redirect($this->adminRedirectTarget());
        }

        $id = (int) ($_POST['id'] ?? 0);
        $guest = $id > 0 ? $this->guests->findById($id) : null;
        if ($guest) {
            $photoPath = $this->config['upload_dir'] . '/' . $guest['foto_path'];
            if (is_file($photoPath)) {
                @unlink($photoPath);
            }
            $this->guests->delete($id);
            Helpers::flash('success', 'Data tamu "' . $guest['nama_lengkap'] . '" berhasil dihapus.');
        } else {
            Helpers::flash('error', 'Data tamu tidak ditemukan.');
        }

        Helpers::redirect($this->adminRedirectTarget());
    }

    private function adminRedirectTarget(): string
    {
        $query = trim((string) ($_POST['redirect_query'] ?? ''));
        return $query !== '' ? '/admin?' . $query : '/admin';
    }

    public function exportCsv(): void
    {
        Auth::requireAuth();

        $filters = [
            'search' => trim((string) ($_GET['q'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'tujuan' => trim((string) ($_GET['tujuan'] ?? '')),
            'date_from' => trim((string) ($_GET['from'] ?? '')),
            'date_to' => trim((string) ($_GET['to'] ?? '')),
        ];

        $rows = $this->guests->getAllForExport($filters);
        $filename = 'buku-tamu-bdi-surabaya-' . date('Ymd-His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Microsoft Excel compatibility
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'Kode Kunjungan',
            'Nama Lengkap',
            'Asal Instansi',
            'No. HP / WhatsApp',
            'Email',
            'Tujuan',
            'Bertemu Dengan',
            'Keperluan',
            'Status',
            'Waktu Masuk',
            'Waktu Keluar',
        ]);

        foreach ($rows as $row) {
            fputcsv($out, [
                $this->sanitizeCsvCell($row['kode_kunjungan']),
                $this->sanitizeCsvCell($row['nama_lengkap']),
                $this->sanitizeCsvCell($row['instansi']),
                $this->sanitizeCsvCell($row['no_hp']),
                $this->sanitizeCsvCell($row['email'] ?? '-'),
                $this->sanitizeCsvCell($row['tujuan']),
                $this->sanitizeCsvCell($row['bertemu_dengan'] ?? '-'),
                $this->sanitizeCsvCell($row['keperluan']),
                $this->sanitizeCsvCell($row['status'] === 'berkunjung' ? 'Sedang Berkunjung' : 'Selesai'),
                $this->sanitizeCsvCell($row['waktu_masuk']),
                $this->sanitizeCsvCell($row['waktu_keluar'] ?? '-'),
            ]);
        }

        fclose($out);
        exit;
    }

    private function sanitizeCsvCell(?string $value): string
    {
        $str = (string) ($value ?? '');
        if ($str === '' || $str === '-') {
            return $str;
        }
        if (
            preg_match('/^[\x00-\x20\x7F]*[=+\-@]/', $str) === 1 ||
            preg_match('/^[\x00-\x1F\x7F]/', $str) === 1
        ) {
            return "'" . $str;
        }
        return $str;
    }

    public function settingsForm(): void
    {
        Auth::requireAuth();
        $adminUser = Auth::user();
        $adminData = $this->admins->findById((int) $adminUser['id']);
        $appName = $this->config['app_name'];
        $flashSuccess = Helpers::flash('success');
        $flashError = Helpers::flash('error');
        require dirname(__DIR__, 2) . '/views/admin/settings.php';
    }

    public function settingsUpdate(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Helpers::flash('error', 'Token keamanan tidak valid.');
            Helpers::redirect('/admin/settings');
        }

        $adminUser = Auth::user();
        $id = (int) $adminUser['id'];
        $nama = trim((string) ($_POST['nama_lengkap'] ?? ''));
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($nama === '' || mb_strlen($nama) > 100) {
            Helpers::flash('error', 'Nama lengkap wajib diisi (maksimal 100 karakter).');
            Helpers::redirect('/admin/settings');
        }

        $adminData = $this->admins->findById($id);
        if (!$adminData) {
            Auth::logout();
            Helpers::redirect('/admin/login');
        }
        if ($newPassword !== '') {
            if (!password_verify($currentPassword, $adminData['password_hash'])) {
                Helpers::flash('error', 'Password saat ini tidak sesuai.');
                Helpers::redirect('/admin/settings');
            }
            if (strlen($newPassword) < 6) {
                Helpers::flash('error', 'Password baru minimal 6 karakter.');
                Helpers::redirect('/admin/settings');
            }
            if ($newPassword !== $confirmPassword) {
                Helpers::flash('error', 'Konfirmasi password baru tidak cocok.');
                Helpers::redirect('/admin/settings');
            }
        }

        $this->admins->updateProfile($id, $nama, $newPassword !== '' ? $newPassword : null);
        $_SESSION['admin_user']['nama_lengkap'] = $nama;

        Helpers::flash('success', 'Profil & keamanan akun berhasil diperbarui.');
        Helpers::redirect('/admin/settings');
    }
}
