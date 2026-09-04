# Product Requirements Document (PRD) — Aura Music 📋🎵

## 1. Ringkasan Eksekutif
**Aura Music** adalah platform pemutar musik web mandiri (*self-hosted*) modern yang menggabungkan kemudahan akses streaming dengan privasi dan kontrol penuh atas koleksi audio lokal pengguna. Dibuat dengan arsitektur **PWA (Progressive Web App)** dan **Web Audio API DSP**, Aura Music memberikan kualitas audio audiophile, responsivitas secepat kilat, dan estetika premium yang setara dengan Spotify dan Apple Music.

---

## 2. Tujuan Produk (Goals)
1. **Zero-Latency Audio Playback**: Pemutaran audio instan dengan caching client-side 0ms dan streaming bitstream native.
2. **True Audiophile Customization**: Memberikan kontrol grafis 10-band equalizer, 8D/3D spatial audio, dan audio effects langsung di browser tanpa plugin tambahan.
3. **All-in-One Audio Management**: Memungkinkan pengguna untuk mengunduh lagu dari YouTube/Spotify, memotong audio, mengedit metadata ID3, dan membuat lirik karaoke `.lrc` dalam satu antarmuka terpadu.
4. **Offline Resilience**: Memungkinkan pemutaran lagu tanpa jaringan internet menggunakan IndexedDB Blob caching dan Service Worker.

---

## 3. Fitur Utama & Kebutuhan Fungsional

### 3.1. Web Audio Engine & DSP
* **10-Band Graphic Equalizer**: Rentang frekuensi 32Hz, 64Hz, 125Hz, 250Hz, 500Hz, 1kHz, 2kHz, 4kHz, 8kHz, 16kHz dengan gain -12dB s/d +12dB.
* **DSP Presets**: 10 preset bawaan (Flat, Bass Boost, Treble Boost, Vocal, Acoustic, Rock, Pop, Electronic, Jazz, Classical).
* **3D / 8D Spatial Audio**: Simulasi panning audio melingkar 360 derajat menggunakan `StereoPannerNode` / `PannerNode` dengan rotasi berkecepatan dinamis.
* **Audio Speed & Nightcore**: Pengubahan playback rate dari 0.5x hingga 2.0x dengan kompensasi pitch.
* **Seamless Crossfade**: Transisi antar lagu otomatis 0-10 detik untuk menghindari jeda keheningan.

### 3.2. Visual & Antarmuka (UI/UX)
* **Glassmorphism Dark Theme**: Tampilan transparan modern dengan efek *frosted glass*, blur dinamis, dan tipografi modern.
* **Adaptive Ambient Glow**: Ekstraksi warna dominan dari cover album secara real-time untuk menghasilkan efek cahaya latar belakang panggung.
* **Interactive Waveform Scrubber**: Canvas waveform yang menampilkan puncak amplitudo audio untuk navigasi posisi lagu yang presisi.
* **Real-time Canvas Visualizer**: 5 mode animasi visualizer audio yang merespons frekuensi bass, mid, dan treble secara instan.

### 3.3. Downloader & Studio Suite
* **YouTube & Spotify Downloader**: Input URL video, playlist, atau track Spotify untuk diunduh otomatis dengan tag ID3 lengkap dan cover art HD.
* **Audio Trimmer**: Pemotong file audio dengan visual slider gelombang untuk mengekspor klip ringtone `.mp3`.
* **Synchronized LRC Studio**: Pembuat berkas lirik `.lrc` interaktif dengan penanda waktu (*timestamping*) sekali klik pada ketukan lagu.
* **ID3 Tag & Metadata Enricher**: Pembaruan judul, artis, album, genre, serta pencarian cover resolusi tinggi otomatis via iTunes Store API.

### 3.4. PWA & Manajemen Playlist
* **PWA & MediaSession API**: Dukungan install di home screen, background playback di mobile, dan integrasi tombol media lockscreen.
* **IndexedDB Offline Engine**: Penyimpanan berkas audio utuh ke browser untuk pemutaran tanpa koneksi internet.
* **Custom Playlists & Favorit**: Pembuatan dan pengelolaan playlist tanpa batas dengan sinkronisasi ke server `data_playlists.json`.
* **Personal Music Statistics**: Pelacak total jam mendengarkan, lagu yang paling sering diputar, dan artis terfavorit.

---

## 4. Kebutuhan Non-Fungsional

| Aspek | Target Spesifikasi |
| :--- | :--- |
| **Kecepatan First Render** | < 100ms via instant localStorage cache pre-warming |
| **Konsumsi Memori Browser** | < 80MB RAM saat memutar audio dan merender visualizer 60 FPS |
| **Kompatibilitas Browser** | Chrome 80+, Edge 80+, Safari 14+, Firefox 75+, Android Chrome, iOS Safari |
| **Kapasitas Library** | Mampu mengindeks hingga 10.000+ lagu tanpa lag menggunakan pagination & virtualized rendering |
| **Server Footprint** | Sangat ringan, menggunakan Pure PHP tanpa dependensi framework berat |
