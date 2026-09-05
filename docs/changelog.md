# Catatan Riwayat Versi (Changelog) — NadaKita 📜🎵

Semua pembaruan penting, penambahan fitur, dan perbaikan bug didokumentasikan di berkas ini.

---

## [v2.5.0] - 2026-09-06
### 🎧 DSP Studio: Dolby 3D Surround & Live Concert Hall Modes
* **🌐 Dolby 3D Spatial Surround Widener**:
  * Menggunakan algoritma *Haas Effect Crossfeed Decorrelation* (micro-delay 15ms antar channel L/R berfase inversi) yang melebarkan panggung suara (*soundstage*) ke luar batas fisik headphone/speaker.
  * Dilengkapi *Psychoacoustic High-Air Filter* (+3.5dB pada 7.5kHz) untuk kejernihan vokal dan separasi detail instrumen yang luas dan megah.
* **🏟️ Live Concert & Stadium Hall Mode**:
  * *Synthetic Arena Convolver Reverb*: Mensimulasikan pantulan awal (*early discrete reflections*) dinding dan atap stadion besar dengan ekor gema *decay* 3.4 detik.
  * *Live Stage EQ Punch*: Boost sub-bass 64Hz (+7dB) dan frekuensi panggung terbuka untuk menghadirkan atmosfer konser nyata.
* **Integrasi UI**: Dua tombol chip baru (`Dolby 3D Surround 🌐` dan `Live Concert Hall 🏟️`) langsung diakses di modal **Studio Equalizer & DSP FX**.

---

## [v2.4.0] - 2026-09-06
### 🛠️ Pindai Duplikat & Penghapusan Cerdas
* **Akurat Tanpa False Positive**: Pemisahan judul inti (*core title*) dan artis kini memperhitungkan normalisasi karakter Unicode dash (`–`, `—`, `−`, `|`), mencegah salah grup lagu berbeda dari artis yang sama.
* **Smart ID3 Title Sanitizer**: Otomatis mendeteksi dan memperbaiki tag ID3 palsu atau kosong dengan membaca nama berkas asli (`Title - Artist`).
* **Self-Healing Delete API (`api/delete.php`)**: Menghapus dependensi kaku pada file cache disk, menambahkan fallback otomatis ke memori Redis / pemindaian instan disk, serta penghapusan fisik file audio dan `.lrc` yang aman.
* **Rencana Database & Roadmap (`docs/roadmap.md`)**: Dokumentasi strategi transisi Flat-File ke SQLite saat koleksi mencapai ≥ 500 lagu dengan konsep Hybrid Guest + Account Mode.

---

## [v2.3.0] - 2026-09-05
### 🎨 Rebranding NadaKita & Fitur Interaktif
* **Rebranding NadaKita**: Transformasi identitas dari Aura Music menjadi **NadaKita**, lengkap dengan tipografi modern, logo dinamis, dan warna aksen baru.
* **5 Pilihan Tema Warna**: Dukungan tema warna kustom (Cyber Emerald, Cyber Neon Cyan, Cyber Violet, Sunset Amber, Midnight Obsidian).
* **Smart Shuffle (Fisher-Yates)**: Algoritma pengacakan antrean tanpa pengulangan lagu yang baru saja diputar.
* **Mini Synced Lyrics Ticker**: Indikator baris lirik aktif yang meluncur langsung di player bar bawah.
* **NadaKita Wrapped Story Canvas**: Generator infografis statistik musik siap unduh ke format gambar story.
* **Multi-Tier In-Memory Cache (`api/cache_helper.php`)**: Integrasi Redis / APCu / In-Memory caching dengan ETag HTTP 304 response.

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
