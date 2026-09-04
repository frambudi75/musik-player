# Catatan Riwayat Versi (Changelog) — Aura Music 📜🎵

Semua pembaruan penting, penambahan fitur, dan perbaikan bug didokumentasikan di berkas ini.

---

## [v2.2.0] - 2026-09-04
### 🔒 Keamanan API & Performa
* **Auth Guard & Rate Limiter Middleware (`api/auth_guard.php`)**: Melindungi endpoint sensitif (upload, YouTube/Spotify download, edit metadata, save lyrics, audio trimmer) dengan token-based authentication (`X-Aura-Token`) dan IP rate limiting.
* **Auto-Login Modal & Frontend Fetch Wrapper**: Integrasi dialog login admin otomatis saat mengakses fitur terlindungi dan wrapper `auraFetch()`.
* **Skeleton Loading Animation**: Placeholder shimmer memuat instan saat mengambil pustaka musik pertama kali untuk visual UX yang lebih halus.
* **Queue Drag & Drop Reordering**: Kemampuan mengatur ulang urutan antrean lagu secara interaktif melalui handle drag `⣿`.
* **Expanded Keyboard Shortcuts & Modal Cheatsheet**: Shortcut lengkap (`Space`, `N`, `P`, `↑/↓`, `M`, `S`, `R`, `L`, `Q`, `F`, `?`) beserta dialog bantuan pintasan.
* **PWA Cache v15**: Pembaruan Service Worker cache untuk sinkronisasi pembaruan terkini.

---

## [v2.1.0] - 2026-09-04
### 🚀 Fitur Baru & Peningkatan
* **Pure PHP ID3 Magic Byte Parser**: Ekstraksi cover album APIC ID3v2 kini mendeteksi binary header JPEG (`\xFF\xD8\xFF`) dan PNG (`\x89PNG`) langsung, menghindari korupsi gambar akibat variasi encoding ID3v2.
* **Fallback Ekstensi String**: Menambahkan fallback otomatis `iconv` dan native parser pada `api/id3.php` jika ekstensi `mbstring` tidak aktif di server.
* **Zero-404 Validation**: API `api/scan.php` kini memverifikasi eksistensi berkas cover art di disk sebelum merespons ke frontend, mencegah broken image request.
* **Path Server Dinamis**: Menampilkan direktori penyimpanan server aktual secara dinamis di antarmuka web.
* **PWA Cache v14**: Pembaruan Service Worker cache untuk penghematan bandwidth dan sinkronisasi instan.

---

## [v2.0.0] - 2026-08-30
### 🌟 Fitur Utama
* **Web Audio API 10-Band Graphic Equalizer**: Penambahan kustomisasi EQ 32Hz-16kHz dengan 10 preset bawaan.
* **3D & 8D Spatial Audio Rotating Engine**: Efek audio melingkar 360 derajat dengan kontrol kecepatan rotasi.
* **Interactive Waveform Scrubber**: Canvas visualisasi bentuk gelombang audio interaktif dengan scrubbing presisi.
* **5-Mode Real-Time Audio Visualizer**: Visualizer audio neon bars, smooth wave, circular pulse, cyber particles, dan ambient glow.
* **Synchronized Karaoke Lyrics Engine**: Penampil lirik kata-per-kata otomatis dengan dukungan berkas `.lrc`.
* **Integrated YouTube & Spotify Downloader**: Unduh lagu dan playlist langsung ke server dengan metadata lengkap dan thumbnail HD 1000x1000 via `yt-dlp` dan `spotdl`.
* **Audio Trimmer & Ringtone Maker**: Pemotong audio visual langsung dari antarmuka web.
* **In-Browser ID3 Tag Editor**: Pengedit tag ID3 terintegrasi dengan pencarian cover art resmi iTunes Store.
* **IndexedDB Offline Audio Storage**: Dukungan penuh pengunduhan lagu ke penyimpanan lokal browser untuk pemutaran tanpa internet.

---

## [v1.0.0] - 2026-08-15
### 初始 Initial Release
* Pemutar musik HTML5/CSS3/JavaScript responsif.
* Backend pemindaian lagu flat-file PHP.
* Pengelolaan playlist dasar dan penyimpanan status lagu yang disukai (*Liked Songs*).
* Desain antarmuka gelap (*Dark Theme*) standar.
