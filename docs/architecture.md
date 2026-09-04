# Arsitektur Sistem — Aura Music 🏛️🎵

Dokumen ini menjelaskan arsitektur teknis lengkap **Aura Music Web Player**, mulai dari alur pemrosesan Web Audio API, struktur modular JavaScript di sisi klien, hingga arsitektur backend PHP dan worker downloader Python.

---

## 1. Diagram Arsitektur Tingkat Tinggi

```text
+-----------------------------------------------------------------------------------+
|                                  BROWSER (CLIENT)                                 |
|                                                                                   |
|   +---------------------------------------------------------------------------+   |
|   |                           USER INTERFACE (HTML5/CSS3)                     |   |
|   |    Hero Featured | Song Table / Grid | Visualizer | Immersive Lyrics      |   |
|   +---------------------------------------------------------------------------+   |
|         ^                     ^                      ^                 ^          |
|         |                     |                      |                 |          |
|   +-------------+     +---------------+      +---------------+   +------------+   |
|   | App Manager |     | Playlist &    |      | Lyrics Engine |   | Ambient    |   |
|   |  (app.js)   |     | Stats Manager |      |  (lyrics.js)  |   | Color FX   |   |
|   +-------------+     +---------------+      +---------------+   +------------+   |
|         |                     |                                                   |
|         v                     v                                                   |
|   +---------------------------------------+      +----------------------------+   |
|   |         AUDIO ENGINE (audio-core.js)  |      |   OFFLINE ENGINE (IndexedDB|   |
|   |  Web Audio Context -> DSP FX Graph    |      |    & Service Worker sw.js) |   |
|   +---------------------------------------+      +----------------------------+   |
|         |                                                      ^                  |
+---------|------------------------------------------------------|------------------+
          | Audio Stream / Range Requests                        | REST API (JSON)
          v                                                      v
+-----------------------------------------------------------------------------------+
|                             BACKEND SERVER (PHP 8 + NGINX)                        |
|                                                                                   |
|   +-----------------------+   +-----------------------+   +-------------------+   |
|   | Scan & ID3 Extractor  |   | Downloader Controller |   | Audio Trimmer &   |   |
|   | (scan.php, id3.php)   |   | (yt_download.php)     |   | Metadata Editor   |   |
|   +-----------------------+   +-----------------------+   +-------------------+   |
|               |                           |                         |             |
|               v                           v                         v             |
|   +-----------------------+   +-----------------------+   +-------------------+   |
|   | Pure PHP ID3 Parser   |   | Python 3 + yt-dlp /   |   | FFmpeg Trimmer &  |   |
|   | & Disk Cover Cache    |   | spotdl Worker Engine  |   | mutagen ID3 Inject|   |
|   +-----------------------+   +-----------------------+   +-------------------+   |
|               |                           |                         |             |
|               +---------------------------+-------------------------+             |
|                                           |                                       |
|                                           v                                       |
|                         STORAGE DIRECTORY: /songs/                                |
|                         - Audio Files (.mp3, .flac, .wav)                         |
|                         - Synchronized Lyrics (.lrc)                              |
|                         - Album Covers Cache (/songs/covers/*.jpg/png)            |
+-----------------------------------------------------------------------------------+
```

---

## 2. Web Audio API Pipeline (DSP Audio Graph)

Suara diproses secara berurutan melalui node Web Audio API sebelum dikeluarkan ke speaker:

```text
[ <audio> Element Source ]
            │
            ▼
    [ MediaElementAudioSourceNode ]
            │
            ▼
    [ 10-Band BiquadFilterNode Array ]  <--- Equalizer (32Hz ... 16kHz)
            │
            ▼
    [ StereoPannerNode ]                 <--- 3D / 8D Spatial Audio Rotation
            │
            ▼
    [ GainNode (Crossfade & Master Vol) ]
            │
            ├────────────────────────────────────────┐
            ▼                                        ▼
    [ AnalyserNode (FFT) ]                 [ AudioDestinationNode ]
            │                                 (Speakers / Headphones)
            ▼
    [ Visualizer & Waveform Canvas ]
```

---

## 3. Modul Komponen Frontend (Client-Side)

1. **`audio-core.js` (`AudioCore`)**:
   - Mengelola `AudioContext`, `HTMLAudioElement`, dan rantai filter EQ 10-band.
   - Mengatur efek 8D spatial rotasi menggunakan interval sudut sinus/kosinus secara mulus.
   - Menghitung crossfade antar lagu secara bertahap saat lagu hampir selesai.

2. **`playlist.js` (`PlaylistManager`)**:
   - Mengelola antrean lagu (*queue*), riwayat putar (*history*), shuffle, dan repeat modes.
   - Menyimpan dan menyinkronkan playlist pengguna ke server (`api/playlist.php`) dan `localStorage`.
   - Merekam statistik waktu dengar harian, track teratas, dan artis favorit.

3. **`lyrics.js` (`LyricsEngine`)**:
   - Mem-parsing string berkas `.lrc` menjadi array objek `{ time, text }`.
   - Menggunakan interpolasi biner untuk mencocokkan baris lirik aktif dengan waktu putar audio.
   - Melakukan scroll otomatis yang halus pada container lirik di mode desktop dan fullscreen.

4. **`visualizer.js` (`AudioVisualizer`)**:
   - Mengambil data frekuensi dari `AnalyserNode.getByteFrequencyData()`.
   - Merender animasi Canvas 60 FPS dengan 5 mode visualisasi (Neon Bars, Waveform, Circle, Particles, Glow).

5. **`ambient-color.js` (`AmbientColor`)**:
   - Menggunakan Canvas tersembunyi untuk mengambil sampel warna piksel dari cover album lagu yang aktif.
   - Mengubah CSS Custom Properties (`--accent-primary`, `--ambient-glow`) secara dinamis.

6. **`offline-storage.js` (`OfflineDB`)**:
   - Menggunakan IndexedDB untuk menyimpan file audio dalam format `Blob`.
   - Menghasilkan URL Blob lokal (`URL.createObjectURL(blob)`) saat aplikasi offline.

---

## 4. Arsitektur Backend (Server-Side)

* **Pure PHP ID3 Extractor (`api/id3.php`)**:
  * Membaca tag ID3v2 secara langsung dari file binary MP3 tanpa ketergantungan library eksternal.
  * Mendeteksi header gambar APIC (JPEG/PNG) dengan pembacaan *magic bytes* (`\xFF\xD8\xFF` dan `\x89PNG`) untuk mengekstrak cover art ke direktori `songs/covers/`.
  * Memiliki fallback encoding otomatis (`iconv` / native parser) jika ekstensi `mbstring` tidak aktif di server.

* **High-Performance Caching (`api/cache_helper.php` & `api/scan.php`)**:
  * Menyimpan hasil pemindaian library ke berkas JSON terkompresi dan Redis/APCu memory cache.
  * Mendukung HTTP ETag dan header `If-None-Match` (304 Not Modified) untuk menghemat bandwidth server hingga 99%.

* **Downloader Worker (`api/downloader.py` & `api/yt_download.php`)**:
  * Mengeksekusi `yt-dlp` di background dengan pengaturan penamaan file yang aman.
  * Otomatis mengonversi audio ke MP3 320kbps via FFmpeg dan menginjeksi tag ID3 serta cover art resolusi tinggi.
