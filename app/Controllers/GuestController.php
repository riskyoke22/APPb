<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\RateLimiter;
use App\Models\GuestModel;
use PDOException;

class GuestController
{
    public function __construct(
        private GuestModel $guests,
        private array $config
    ) {
    }

    public function index(): void
    {
        $stats = $this->guests->getPublicStats();
        $tujuanList = $this->config['tujuan_list'];
        $appName = $this->config['app_name'];
        require dirname(__DIR__, 2) . '/views/guest/index.php';
    }

    public function store(): void
    {
        $csrfToken = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Csrf::verify($csrfToken)) {
            Helpers::json(['success' => false, 'message' => 'Sesi tidak valid. Silakan muat ulang halaman.'], 403);
        }

        $ip = Helpers::getClientIp($this->config['trusted_proxies'] ?? []);
        $rateLimiter = new RateLimiter();
        $attempt = $rateLimiter->attempt('guest_checkin_' . $ip, 20, 900);
        if (!$attempt['allowed']) {
            Helpers::json([
                'success' => false,
                'message' => 'Terlalu banyak permintaan check-in. Silakan tunggu beberapa menit.',
            ], 429);
        }

        $nama = trim((string) ($_POST['nama_lengkap'] ?? ''));
        $instansi = trim((string) ($_POST['instansi'] ?? ''));
        $noHp = trim((string) ($_POST['no_hp'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $tujuan = trim((string) ($_POST['tujuan'] ?? ''));
        $bertemuDengan = trim((string) ($_POST['bertemu_dengan'] ?? ''));
        $keperluan = trim((string) ($_POST['keperluan'] ?? ''));
        $fotoBase64 = (string) ($_POST['photo_data'] ?? $_POST['foto_base64'] ?? '');

        if ($nama === '' || $instansi === '' || $noHp === '' || $tujuan === '' || $keperluan === '') {
            Helpers::json(['success' => false, 'message' => 'Mohon lengkapi semua kolom yang wajib diisi.'], 422);
        }

        if (mb_strlen($nama) > 120 || mb_strlen($instansi) > 150 || mb_strlen($bertemuDengan) > 120 || mb_strlen($keperluan) > 2000) {
            Helpers::json(['success' => false, 'message' => 'Panjang teks melebihi batas maksimal yang diizinkan.'], 422);
        }

        if (!preg_match('/^[0-9+\-\s()]{6,25}$/', $noHp)) {
            Helpers::json(['success' => false, 'message' => 'Format nomor HP / WhatsApp tidak valid (6–25 karakter angka).'], 422);
        }

        if ($email !== '' && (mb_strlen($email) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            Helpers::json(['success' => false, 'message' => 'Format email tidak valid.'], 422);
        }

        $allowedTujuan = $this->config['tujuan_list'] ?? [];
        if (!empty($allowedTujuan) && !in_array($tujuan, $allowedTujuan, true)) {
            Helpers::json(['success' => false, 'message' => 'Unit / tujuan kunjungan tidak valid.'], 422);
        }

        // Save face photo (from base64 camera capture or fallback file upload)
        $fotoRelPath = null;
        if ($fotoBase64 !== '') {
            $fotoRelPath = $this->saveBase64Photo($fotoBase64);
        } elseif (!empty($_FILES['foto_file']['tmp_name'])) {
            $fotoRelPath = $this->saveUploadedPhoto($_FILES['foto_file']);
        }

        if ($fotoRelPath === null) {
            Helpers::json([
                'success' => false,
                'message' => 'Foto wajah wajib diambil sebagai bukti kehadiran (maks. 8MB).',
            ], 422);
        }

        $kode = '';
        $id = 0;
        for ($retry = 0; $retry < 3; $retry++) {
            $kode = Helpers::generateVisitCode();
            try {
                $id = $this->guests->create([
                    'kode_kunjungan' => $kode,
                    'nama_lengkap' => $nama,
                    'instansi' => $instansi,
                    'no_hp' => $noHp,
                    'email' => $email,
                    'tujuan' => $tujuan,
                    'bertemu_dengan' => $bertemuDengan,
                    'keperluan' => $keperluan,
                    'foto_path' => $fotoRelPath,
                    'ip_address' => $ip,
                    'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250),
                ]);
                break;
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000' || $retry === 2) {
                    @unlink($this->config['upload_dir'] . '/' . $fotoRelPath);
                    Helpers::json(['success' => false, 'message' => 'Gagal menyimpan data kunjungan. Silakan coba lagi.'], 500);
                }
            }
        }

        $stats = $this->guests->getPublicStats();

        Helpers::json([
            'success' => true,
            'message' => 'Terima kasih! Kehadiran Anda berhasil dicatat.',
            'data' => [
                'id' => $id,
                'kode_kunjungan' => $kode,
                'nama_lengkap' => $nama,
                'instansi' => $instansi,
                'tujuan' => $tujuan,
                'waktu_masuk' => Helpers::formatTanggalIndo(date('Y-m-d H:i:s')),
            ],
            'stats' => $stats,
        ]);
    }

    public function checkoutPage(): void
    {
        $appName = $this->config['app_name'];
        require dirname(__DIR__, 2) . '/views/guest/checkout.php';
    }

    public function checkoutProcess(): void
    {
        $csrfToken = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Csrf::verify($csrfToken)) {
            Helpers::json(['success' => false, 'message' => 'Token keamanan tidak valid.'], 403);
        }

        $ip = Helpers::getClientIp($this->config['trusted_proxies'] ?? []);
        $rateLimiter = new RateLimiter();
        $attempt = $rateLimiter->attempt('guest_checkout_' . $ip, 15, 900);
        if (!$attempt['allowed']) {
            Helpers::json([
                'success' => false,
                'message' => 'Terlalu banyak percobaan check-out. Silakan tunggu beberapa menit.',
            ], 429);
        }

        $query = trim((string) ($_POST['query'] ?? ''));
        if ($query === '') {
            Helpers::json(['success' => false, 'message' => 'Masukkan Kode Kunjungan atau Nomor HP Anda.'], 422);
        }

        $guest = $this->guests->findActiveByCodeOrPhone($query);
        if (!$guest) {
            Helpers::json([
                'success' => false,
                'message' => 'Data kunjungan aktif tidak ditemukan atau Anda sudah melakukan check-out.',
            ], 404);
        }

        $this->guests->checkout((int) $guest['id']);

        Helpers::json([
            'success' => true,
            'message' => 'Check-out berhasil! Terima kasih telah berkunjung ke BDI Surabaya.',
            'data' => [
                'kode_kunjungan' => $guest['kode_kunjungan'],
                'nama_lengkap' => $guest['nama_lengkap'],
                'instansi' => $guest['instansi'],
                'waktu_masuk' => Helpers::formatTanggalIndo($guest['waktu_masuk']),
                'waktu_keluar' => Helpers::formatTanggalIndo(date('Y-m-d H:i:s')),
            ],
        ]);
    }

    public function servePhoto(): void
    {
        Auth::requireAuth();

        $file = (string) ($_GET['f'] ?? '');
        if (!preg_match('/^[0-9]{4}\/[0-9]{2}\/[a-zA-Z0-9_-]+\.(jpg|jpeg|png)$/', $file)) {
            http_response_code(404);
            exit('Not found');
        }

        $baseDir = realpath($this->config['upload_dir']);
        $fullPath = realpath($this->config['upload_dir'] . '/' . $file);
        if (
            $baseDir === false ||
            $fullPath === false ||
            !str_starts_with($fullPath, $baseDir . DIRECTORY_SEPARATOR) ||
            !is_file($fullPath)
        ) {
            http_response_code(404);
            exit('Not found');
        }

        $mime = str_ends_with($file, '.png') ? 'image/png' : 'image/jpeg';
        header('Content-Type: ' . $mime);
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        readfile($fullPath);
        exit;
    }

    private function saveBase64Photo(string $base64): ?string
    {
        if (!preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,(.+)$/s', $base64, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[2], true);
        if ($binary === false || strlen($binary) > 8 * 1024 * 1024) {
            return null;
        }

        return $this->processAndSaveImage($binary);
    }

    private function saveUploadedPhoto(array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        if (($file['size'] ?? 0) > 8 * 1024 * 1024) {
            return null;
        }

        $raw = @file_get_contents($file['tmp_name']);
        if ($raw === false) {
            return null;
        }

        return $this->processAndSaveImage($raw);
    }

    private function processAndSaveImage(string $binary): ?string
    {
        $img = @imagecreatefromstring($binary);
        if ($img === false) {
            return null;
        }

        // Auto-resize to max width 720px (preserving aspect ratio) for lightweight storage
        $origWidth = imagesx($img);
        $origHeight = imagesy($img);
        if ($origWidth > 720 && $origHeight > 0) {
            $scaled = @imagescale($img, 720, -1, IMG_BICUBIC);
            if ($scaled !== false) {
                imagedestroy($img);
                $img = $scaled;
            }
        }

        $subDir = date('Y/m');
        $targetDir = $this->config['upload_dir'] . '/' . $subDir;
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            imagedestroy($img);
            return null;
        }

        $filename = 'tamu_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.jpg';
        $fullPath = $targetDir . '/' . $filename;

        $saved = @imagejpeg($img, $fullPath, 75);
        imagedestroy($img);

        if (!$saved || !is_file($fullPath)) {
            return null;
        }

        return $subDir . '/' . $filename;
    }
}
