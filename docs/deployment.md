# Panduan Deployment & Operasional — Aura Music 🚢🎵

Dokumen ini memandu proses instalasi dan konfigurasi server produksi untuk **Aura Music Web Player** di lingkungan Linux VPS (Ubuntu/Debian) dengan Nginx dan aaPanel.

---

## 1. Kebutuhan Sistem & Paket Dependensi

### Paket Sistem Linux
```bash
# Update repository
sudo apt-get update -y

# Install dependensi audio & video
sudo apt-get install -y ffmpeg python3 python3-pip git curl
```

### Python Package (Downloader Engine)
```bash
sudo pip3 install yt-dlp spotdl requests mutagen eyed3 --break-system-packages
```

---

## 2. Instalasi di aaPanel (Nginx + PHP 8.x)

1. **Buat Website Baru:**
   * Di dashboard **aaPanel**, buka menu **Website** -> **Add Site**.
   * Masukkan domain Anda (misal: `musik.domainanda.com`).
   * Pilih versi PHP **PHP-8.0** / **PHP-8.1** / **PHP-8.2** / **PHP-8.3** / **PHP-8.4**.

2. **Aktifkan Fungsi `exec()` di PHP:**
   * Buka **App Store** -> **PHP (versi aktif)** -> **Settings** -> **Disabled functions**.
   * Temukan dan **hapus** `exec` dan `shell_exec` dari daftar disabled functions (agar fitur download YouTube dan ekstraksi cover via FFmpeg berjalan lancar).
   * Restart PHP-FPM service.

3. **Deploy Source Code:**
   ```bash
   cd /www/wwwroot/musik.domainanda.com
   git clone https://github.com/frambudi75/musik-player.git .
   ```

4. **Konfigurasi Hak Akses (Permissions):**
   ```bash
   mkdir -p songs/covers
   chown -R www:www /www/wwwroot/musik.domainanda.com
   chmod -R 775 /www/wwwroot/musik.domainanda.com/songs
   chmod -R 777 /www/wwwroot/musik.domainanda.com/songs/covers
   ```

---

## 3. Konfigurasi Nginx Web Server

Pastikan konfigurasi virtual host Nginx mengizinkan akses ke folder `songs/covers/` dan mendukung streaming audio *Range Requests*:

```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name musik.domainanda.com;
    root /www/wwwroot/musik.domainanda.com;
    index index.php index.html;

    # Aktifkan gzip compression untuk file teks
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml;

    # Byte-range requests untuk streaming lagu tanpa delay
    location ~* \.(mp3|flac|wav|ogg|m4a|aac|opus)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
        add_header Accept-Ranges bytes;
    }

    # Static cover images cache
    location ^~ /songs/covers/ {
        expires 30d;
        add_header Cache-Control "public, max-age=2592000, immutable";
        try_files $uri =404;
    }

    # PHP Handler
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/tmp/php-cgi-84.sock; # Sesuaikan versi PHP Anda
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Blokir akses ke file tersembunyi kecuali .well-known
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 4. Pemindaian Library Awal (Initial Scan)

Jalankan perintah pemindaian via CLI terminal untuk mengekstrak seluruh cover dan mengompilasi cache library:

```bash
php /www/wwwroot/musik.domainanda.com/api/scan.php --refresh
```

---

## 5. Pemecahan Masalah (Troubleshooting)

| Gejala Masalah | Penyebab Utama | Solusi |
| :--- | :--- | :--- |
| **Cover lagu error 404** | Folder `songs/covers` belum dibuat atau permission belum writeable oleh user `www`. | Jalankan `chmod -R 777 songs/covers` dan scan ulang via `php api/scan.php --refresh`. |
| **Download YouTube gagal** | Fungsi `exec()` dimatikan di PHP atau binary `yt-dlp` belum terpasang. | Hapus `exec` dari PHP Disabled Functions dan instal `pip3 install yt-dlp`. |
| **Error `Call to undefined function mb_convert_encoding()`** | Ekstensi `php-mbstring` belum aktif di server. | Sistem sudah memiliki fallback otomatis di `api/id3.php`, atau instal via `apt install php-mbstring`. |
