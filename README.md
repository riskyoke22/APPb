# Buku Tamu Digital — BDI Surabaya

Aplikasi **Buku Tamu Digital** berbasis **PHP 8.2 + MariaDB + Docker** untuk **Balai Diklat Industri (BDI) Surabaya**. Dilengkapi fitur **foto wajah langsung dari kamera** sebagai bukti kehadiran, tampilan modern semi-formal, animasi halus, check-out mandiri, serta panel administrator lengkap.

## Fitur Utama
- **Halaman Pengunjung (`/`)**:
  - Live webcam face capture dengan panduan bingkai oval, efek flash, timer 3 detik, ganti kamera depan/belakang, dan fallback upload foto.
  - Formulir kunjungan lengkap + Digital Visitor Pass berisi **Kode Kunjungan** unik.
  - Statistik real-time tamu hari ini & tamu yang sedang berkunjung.
- **Check-Out Mandiri (`/checkout`)**:
  - Pengunjung dapat check-out menggunakan Kode Kunjungan atau Nomor HP.
- **Panel Administrator (`/admin`)**:
  - Login aman (session + CSRF + `password_hash`).
  - Dashboard KPI & grafik interaktif (tren 7 hari & distribusi unit tujuan).
  - Filter pencarian, preview foto wajah ukuran penuh (Lightbox), check-out manual, hapus data, cetak laporan, dan **Export CSV/Excel**.
  - Pengaturan profil & ganti password admin (`/admin/settings`).

## Cara Menjalankan Cepat (Docker)

1. Pastikan Docker & Docker Compose sudah terpasang.
2. Sesuaikan konfigurasi di file `.env` (terutama `APP_PORT`, default `8085` agar tidak bentrok dengan web lain di VM).
3. Jalankan container:
   ```bash
   docker compose up -d --build
   ```
4. Buka di browser:
   - Halaman Buku Tamu: `http://localhost:8085`
   - Panel Admin: `http://localhost:8085/admin`

## Akun Admin Default
- **Username**: `admin`
- **Password**: `adminbdi`

> Segera ganti password melalui menu **Pengaturan Akun** (`/admin/settings`) setelah login pertama kali.

Untuk panduan deploy di VM yang sama dengan folder web lain beserta konfigurasi HTTPS/Nginx, lihat [DEPLOY.md](DEPLOY.md).
