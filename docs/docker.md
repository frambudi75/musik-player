# Panduan Deployment Docker — Aura Music 🐳🎵

Panduan resmi untuk menjalankan **Aura Music Web Player** menggunakan Docker & Docker Compose di server lokal maupun VPS Linux.

---

## 🌟 Keunggulan Setup Docker
- **All-in-One Image**: PHP 8.2, Apache, FFmpeg, Python 3, `yt-dlp`, dan `spotdl` sudah terpasang dan dikonfigurasi siap pakai.
- **Zero-Dependency**: Tidak perlu install atau kompilasi PHP ekstensi atau dependensi manual di host OS.
- **Persistent Storage**: Folder musik (`./songs`) dimount langsung ke host sehingga koleksi lagu aman saat container diperbarui.
- **Healthcheck Terintegrasi**: Memastikan API pemindaian selalu responsif.

---

## 🚀 Quick Start (Docker Compose)

### 1. Clone Repository & Masuk ke Direktori
```bash
git clone https://github.com/frambudi75/musik-player.git
cd musik-player
```

### 2. Siapkan Konfigurasi Lingkungan (Opsional)
Salin berkas `.env.example` ke `.env`:
```bash
cp .env.example .env
```
Anda dapat mengubah port default (`8080`) dan zona waktu (`TZ=Asia/Jakarta`) di dalam berkas `.env`.

### 3. Jalankan Container
```bash
docker compose up -d --build
```

### 4. Akses Web Player
Buka browser dan akses:
```
http://localhost:8080
```
*(atau sesuaikan dengan IP / Domain server Anda, misal `http://ip-server:8080`)*

---

## 🛠️ Menjalankan dengan Docker CLI (Tanpa Compose)

Jika ingin menjalankan secara manual via Docker CLI:

```bash
# 1. Build Image
docker build -t aura-music:latest .

# 2. Jalankan Container
docker run -d \
  --name aura-music \
  --restart unless-stopped \
  -p 8080:80 \
  -v $(pwd)/songs:/var/www/html/songs \
  -e TZ=Asia/Jakarta \
  aura-music:latest
```

---

## 📂 Struktur Volume & File Penyimpanan

| Path di Host | Path di Container | Fungsi |
|---|---|---|
| `./songs/` | `/var/www/html/songs` | Direktori file audio (`.mp3`, `.flac`, `.m4a`, dll) dan cache cover art |

> [!NOTE]
> Semua lagu yang diupload atau didownload via YouTube/Spotify downloader akan tersimpan otomatis di direktori `./songs/` pada host.

---

## 🔐 Manajemen Hak Akses (Permissions)

Container secara otomatis mengatur permission pada folder `songs/` saat pertama kali dijalankan via `entrypoint.sh`. Namun jika Anda menambahkan lagu secara manual melalui host OS, pastikan permissions terbaca:

```bash
sudo chmod -R 775 ./songs
```

---

## 🔄 Pemindaian Ulang Library (Refresh Library via CLI)

Untuk menjalankan scan pustaka lagu langsung dari dalam container:

```bash
docker exec -it aura-music php /var/www/html/api/scan.php --refresh
```

---

## 🌐 Konfigurasi Reverse Proxy Nginx + SSL (Produksi)

Jika menjalankan di VPS dengan domain dan SSL Let's Encrypt, gunakan konfigurasi reverse proxy Nginx berikut:

```nginx
server {
    listen 80;
    server_name musik.domainanda.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name musik.domainanda.com;

    ssl_certificate /etc/letsencrypt/live/musik.domainanda.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/musik.domainanda.com/privkey.pem;

    client_max_body_size 128M;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # Streaming Range Requests
        proxy_set_header Range $http_range;
        proxy_set_header If-Range $http_if_range;
        proxy_buffering off;
    }
}
```

---

## 🛑 Perintah Pemeliharaan Docker

| Perintah | Deskripsi |
|---|---|
| `docker compose ps` | Cek status container & healthcheck |
| `docker compose logs -f` | Lihat log realtime Apache / PHP / Downloader |
| `docker compose restart` | Restart container |
| `docker compose down` | Hentikan dan hapus container |
| `docker compose pull && docker compose up -d --build` | Update container ke versi terbaru |
