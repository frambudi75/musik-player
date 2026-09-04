# Aura Music Web Player 🎵✨

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=flat&logo=php&logoColor=white)](https://php.net)
[![PWA Ready](https://img.shields.io/badge/PWA-Ready-5A0FC8?style=flat&logo=pwa&logoColor=white)](https://web.dev/progressive-web-apps/)
[![Audio Engine](https://img.shields.io/badge/Web%20Audio-API%20DSP-FF6F00?style=flat)](https://developer.mozilla.org/en-US/docs/Web/API/Web_Audio_API)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Aura Music adalah aplikasi pemutar musik modern (*Self-Hosted Web Music Player*) berkinerja tinggi dengan antarmuka Glassmorphism gelap premium, mesin Web Audio API DSP 10-Band EQ, penampil lirik karaoke tersinkronisasi (.LRC), pengunduh bawaan YouTube/Spotify, pemotong ringtone audio, pengedit tag ID3, dan dukungan penuh Progressive Web App (PWA) dengan penyimpanan offline berbasis IndexedDB.

---

## 🌟 Fitur Utama

### 🎧 1. Pengalaman Audio Mutakhir (Audiophile Experience)
* **10-Band Graphic Equalizer**: Dilengkapi 10 preset (Bass Boost, Vocal, Electronic, Rock, Acoustic, dll.) dan kustomisasi frekuensi presisi 32Hz - 16kHz.
* **DSP Sound FX Suite**:
  * *Spatial 3D & 8D Audio Rotation*: Efek suara berputar interaktif dengan kecepatan putaran dinamis.
  * *Concert Hall & Studio Reverb*: Simulasi akustik ruangan menggunakan Web Audio Convolver/Feedback nodes.
  * *Pitch & Speed Control*: Pengaturan kecepatan putar (0.5x hingga 2.0x) dan efek *Nightcore / Slowed + Reverb*.
  * *Gapless Playback & Seamless Crossfade*: Transisi antar lagu tanpa jeda dengan durasi crossfade yang dapat disesuaikan.

### 🎨 2. Visual & Antarmuka Interaktif
* **Interactive Waveform Scrubber**: Visualisasi gelombang audio riil yang dapat digeser untuk navigasi lagu secara presisi.
* **Real-time Audio Visualizers**: 5 mode spektrum frekuensi animasi (*Neon Bars*, *Waveform*, *Circle Pulse*, *Particles*, *Cyber Glow*).
* **Adaptive Ambient Glow**: Warna aksen antarmuka berubah secara dinamis mengikuti palet warna cover album yang sedang diputar.
* **Synchronized Karaoke Lyrics Engine**: Penampil lirik kata-per-kata yang bergerak mulus dengan dukungan file format `.lrc` dan mode panggung *Immersive Fullscreen*.

### 📥 3. Integrated Music Downloader & Importer
* **YouTube Downloader**: Ekstraksi audio MP3 320kbps dari URL video tunggal maupun playlist YouTube otomatis menggunakan `yt-dlp`.
* **Spotify Downloader**: Unduh lagu & playlist Spotify via integrasi otomatis metadata dan YouTube Audio backend.
* **Studio Audio Trimmer & Ringtone Maker**: Potong bagian favorit dari lagu langsung di browser dan simpan sebagai ringtone atau klip audio baru.
* **LRC Synchronizer Studio**: Editor pembuat lirik tersinkronisasi interaktif langsung dari dalam antarmuka web.

### 🏷️ 4. In-Browser ID3 Tag Editor & Metadata Enricher
* **ID3 Tag Editor**: Edit Judul, Artis, Album, Tahun, Genre, dan sematkan Cover Art baru langsung ke berkas audio MP3 di server.
* **Auto-Enrich Artwork**: Cari dan unduh cover album resolusi tinggi (HD 1000x1000) dan metadata akurat dari database resmi iTunes Store & MusicBrainz dengan satu klik.

### 📱 5. PWA & Offline Engine (IndexedDB)
* **Installable App**: Pasang Aura Music di Android, iOS, Windows, dan macOS seperti aplikasi native tanpa instalasi toko aplikasi.
* **Download Lagu untuk Offline**: Simpan lagu favorit langsung ke penyimpanan browser (IndexedDB) untuk diputar kapan saja tanpa koneksi internet.
* **MediaSession API Support**: Kontrol putar, jeda, berikutnya, dan artwork muncul langsung di notifikasi Android, lockscreen iOS, dan keyboard media key.

---

## 🚀 Panduan Instalasi & Menjalankan

### Persyaratan Sistem
* **Web Server**: Nginx / Apache
* **PHP**: Versi 8.0 atau lebih baru (`php-json`, `php-fileinfo`, `php-curl`, `php-iconv` / `php-mbstring`)
* **Python**: Versi 3.8+ (Opsional, untuk fitur Downloader YouTube & Spotify)
* **FFmpeg**: Terinstal di sistem server untuk konversi audio & trimmer.

---

### A. Menjalankan dengan Docker & Docker Compose (Paling Direkomendasikan 🐳)

Cara tercepat dan paling bersih tanpa perlu install PHP, FFmpeg, atau Python secara manual di host:

```bash
# 1. Clone Repositori
git clone https://github.com/frambudi75/musik-player.git
cd musik-player

# 2. Jalankan Container
docker compose up -d --build
```
Akses langsung di browser: `http://localhost:8080` (Semua lagu & covers disimpan persisten di folder `./songs`).
📖 *Lihat panduan lengkap di [docs/docker.md](./docs/docker.md)*.

---

### B. Menjalankan di Localhost (XAMPP / Laragon / PHP Built-in)

1. **Clone Repositori:**
   ```bash
   git clone https://github.com/frambudi75/musik-player.git music
   cd music
   ```

2. **Jalankan PHP Development Server:**
   ```bash
   php -S localhost:8000
   ```

3. **Buka di Browser:**
   Akses `http://localhost:8000` di Google Chrome, Firefox, atau Edge.

---

### B. Deployment di aaPanel / Linux VPS (Nginx + PHP-FPM)

1. **Upload / Clone Project ke Web Directory:**
   ```bash
   cd /www/wwwroot/
   git clone https://github.com/frambudi75/musik-player.git musik.domainanda.com
   cd musik.domainanda.com
   ```

2. **Atur Hak Akses Folder Lagu & Cover:**
   ```bash
   mkdir -p songs/covers
   chown -R www:www /www/wwwroot/musik.domainanda.com
   chmod -R 775 /www/wwwroot/musik.domainanda.com/songs
   chmod -R 777 /www/wwwroot/musik.domainanda.com/songs/covers
   ```

3. **Install Dependencies Downloader (Opsional):**
   ```bash
   pip3 install yt-dlp spotdl requests mutagen eyed3 --break-system-packages
   apt-get install -y ffmpeg
   ```

4. **Aktifkan Fungsi `exec` di PHP (aaPanel):**
   * Masuk ke **aaPanel** -> **App Store** -> **PHP (8.x)** -> **Settings** -> **Disabled Functions**.
   * Hapus `exec` dari daftar disabled functions agar fitur downloader & ID3 tagging dapat berjalan.

5. **Scan Koleksi Lagu Pertama Kali:**
   ```bash
   php /www/wwwroot/musik.domainanda.com/api/scan.php --refresh
   ```

---

## 📂 Struktur Direktori

```text
musik-player/
├── api/                       # RESTful API Backend
│   ├── cache_helper.php       # In-memory & Redis caching layer
│   ├── downloader.py          # Script Python backend yt-dlp & spotdl
│   ├── edit_metadata.php      # Editor ID3 tag audio
│   ├── enrich_metadata.php    # iTunes/MusicBrainz high-res artwork search
│   ├── id3.php                # Pure PHP ID3v2 tag & cover extractor
│   ├── lyrics_search.php      # Online lyrics scraper & LRCLIB API
│   ├── playlist.php           # CRUD playlist manager
│   ├── scan.php               # Audio scanner & library indexer
│   ├── spotify_download.php   # Spotify song & playlist downloader
│   ├── trim_audio.php         # Audio trimmer & ringtone exporter
│   ├── upload.php             # Web audio & LRC uploader
│   └── yt_download.php        # YouTube downloader endpoint
├── assets/                    # Frontend UI Assets
│   ├── css/                   # Stylesheets (Glassmorphic Theme, Player, Modals)
│   ├── icons/                 # PWA Icons (192x192, 512x512, maskable)
│   ├── js/                    # Modular JavaScript Components
│   │   ├── ambient-color.js   # Dynamic accent color extractor
│   │   ├── app.js             # Main UI application controller
│   │   ├── audio-core.js      # Web Audio API, Equalizer & DSP nodes
│   │   ├── lyrics.js          # Synchronized LRC parser & render engine
│   │   ├── offline-storage.js # IndexedDB offline song storage
│   │   ├── playlist.js        # Queue, playlists & listening statistics
│   │   ├── visualizer.js      # Real-time Canvas audio visualizers
│   │   └── waveform.js        # Interactive audio waveform scrubber
│   └── sample_covers/         # Default cover art placeholders
├── docs/                      # Dokumentasi Teknis Lengkap
├── songs/                     # Direktori Koleksi Lagu (.mp3, .flac, .wav, dll)
│   └── covers/                # Cache thumbnail album art
├── index.php                  # Halaman Utama Aplikasi Web
├── manifest.json              # PWA Web App Manifest
├── sw.js                      # Service Worker PWA (Offline Cache Engine)
└── .gitignore                 # Konfigurasi Git Ignore
```

---

## 📖 Dokumentasi Teknis

Dokumentasi lengkap dan spesifikasi arsitektur dapat ditemukan pada folder [`docs/`](./docs/):
* 📋 [PRD (Product Requirements Document)](./docs/prd.md)
* 🏛️ [Arsitektur Sistem](./docs/architecture.md)
* 🔌 [Spesifikasi REST API](./docs/api.md)
* 💾 [Manajemen Data & Penyimpanan](./docs/database.md)
* 🎨 [Desain UI/UX & Interaksi](./docs/ui-ux.md)
* 🚢 [Panduan Deployment & Konfigurasi Server](./docs/deployment.md)
* 📜 [Changelog](./docs/changelog.md)

---

## 🛡️ Lisensi

Didistribusikan di bawah lisensi MIT. Lihat `LICENSE` untuk informasi lebih lanjut.
Dibuat dengan ❤️ oleh [frambudi75](https://github.com/frambudi75).
