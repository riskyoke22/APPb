# Panduan Deploy di VM yang Sama dengan Folder Web

Aplikasi ini dirancang agar bisa langsung dijalankan di VM yang sudah memiliki web server (Nginx/Apache) atau website lain tanpa bentrok port.

## 1. Jalankan Docker di Folder Aplikasi
Masuk ke folder aplikasi (misalnya `/var/www/bukutamu-bdi` atau folder project ini) lalu jalankan:

```bash
cp .env.example .env
# Edit .env jika ingin mengubah port atau password database
nano .env

docker compose up -d --build
```

Secara default, aplikasi berjalan di port `8085` (`127.0.0.1:8085`) sehingga tidak bentrok dengan port `80` / `443` milik web utama di VM.

Cek status container:
```bash
docker compose ps
```

## 2. Konfigurasi Reverse Proxy Nginx + HTTPS (Wajib untuk Kamera)
> **Penting:** Browser modern hanya mengizinkan akses kamera (`getUserMedia`) pada `localhost` atau domain dengan **HTTPS**. Karena itu, gunakan Nginx/Apache di VM sebagai reverse proxy dengan SSL (misalnya Certbot / Let's Encrypt).

Contoh konfigurasi Nginx untuk subdomain `bukutamu.bdisurabaya.go.id`:

```nginx
server {
    listen 80;
    server_name bukutamu.bdisurabaya.go.id;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name bukutamu.bdisurabaya.go.id;

    ssl_certificate /etc/letsencrypt/live/bukutamu.bdisurabaya.go.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/bukutamu.bdisurabaya.go.id/privkey.pem;

    client_max_body_size 15M;

    location / {
        proxy_pass http://127.0.0.1:8085;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Aktifkan dan reload Nginx:
```bash
sudo nginx -t && sudo systemctl reload nginx
```

### Konfigurasi `TRUSTED_PROXIES` untuk Rate Limiter
Saat aplikasi berjalan di dalam Docker di belakang Nginx reverse proxy, `REMOTE_ADDR` yang diterima Apache di dalam container adalah IP gateway bridge Docker (bukan IP asli pengunjung).

- Secara default, `TRUSTED_PROXIES` dikosongkan sehingga aplikasi hanya menggunakan `REMOTE_ADDR` dan tidak mempercayai header `X-Forwarded-For` atau `X-Real-IP` dari sumber acak.
- Jika Anda menggunakan Nginx reverse proxy di VM yang sama, cek IP gateway bridge Docker (`docker network inspect bdi_network` atau lihat kolom `Gateway`), lalu isi IP tersebut secara spesifik di `.env`:
  ```env
  TRUSTED_PROXIES=172.18.0.1
  ```
- Jangan memasukkan subnet luas seperti `172.16.0.0/12` jika terdapat container lain yang tidak tepercaya pada host yang sama.

## 3. Penyimpanan Data & Backup
- **Foto Wajah Tamu**: Tersimpan di folder `./storage/uploads/photos/YYYY/MM/` pada VM (ter-mount via Docker volume) sehingga tetap aman meskipun container di-rebuild.
- **Database MariaDB**: Tersimpan di Docker volume `db_data`.

Backup database kapan saja:
```bash
docker exec bdi_bukutamu_db mariadb-dump -u bdi_user -pBdiSurabaya2026! buku_tamu_bdi > backup_bukutamu.sql
```
