# Aura Music Documentation Index 📚🎵

Selamat datang di pusat dokumentasi teknis **Aura Music Web Player**. Dokumen di dalam folder ini mencakup arsitektur sistem, spesifikasi API, panduan deployment, sistem penyimpanan, serta pedoman desain antarmuka.

---

## 📑 Daftar Berkas Dokumentasi

| Dokumen | Deskripsi |
| :--- | :--- |
| 📋 **[PRD (Product Requirements Document)](./prd.md)** | Penjelasan visi produk, target pengguna, fitur utama, dan kriteria keberhasilan. |
| 🏛️ **[Arsitektur Sistem](./architecture.md)** | Diagram arsitektur, Web Audio API DSP pipeline, modul JavaScript, dan backend PHP/Python. |
| 🔌 **[Spesifikasi REST API](./api.md)** | Dokumentasi lengkap endpoint HTTP API, format payload JSON, dan error handling. |
| 💾 **[Manajemen Data & Penyimpanan](./database.md)** | Struktur penyimpanan file audio, ID3 metadata caching, IndexedDB offline storage, dan JSON playlists. |
| 🎨 **[Desain UI/UX & Interaksi](./ui-ux.md)** | Panduan desain Glassmorphism, adaptive ambient glow, sistem visualizer, dan responsivitas mobile. |
| 🚢 **[Panduan Deployment & Operasional](./deployment.md)** | Panduan instalasi di VPS Linux (aaPanel/Nginx), Docker, tuning performa PHP-FPM, dan troubleshooting. |
| 📜 **[Changelog](./changelog.md)** | Catatan riwayat versi, penambahan fitur baru, dan perbaikan bug dari v1.0.0 hingga v2.1.0. |

---

## 🚀 Gambaran Umum Arsitektur

```text
+-------------------------------------------------------------------------------+
|                           CLIENT BROWSER / PWA                                |
|  +---------------------+  +----------------------+  +----------------------+  |
|  |   UI / Glassmorphism|  | Web Audio DSP Engine |  |  IndexedDB Offline   |  |
|  | (Ambient Glow / VIZ)|  | (10-Band EQ, 3D/8D)  |  |  (Audio Blob Store)  |  |
|  +---------------------+  +----------------------+  +----------------------+  |
+-------------------------------------------------------------------------------+
                                      | HTTP REST / Service Worker
+-------------------------------------------------------------------------------+
|                             WEB SERVER & BACKEND                              |
|  +-------------------------------------------------------------------------+  |
|  | Nginx / Apache Web Server                                               |  |
|  +-------------------------------------------------------------------------+  |
|  | PHP 8 Backend API:                                                      |  |
|  | - Library Scanner & ID3 Extractor (api/scan.php, api/id3.php)           |  |
|  | - Downloader & Metadata Suite (api/yt_download.php, api/enrich_...)     |  |
|  | - Audio Trimmer & Lyric Studio (api/trim_audio.php, api/save_lyrics.php)|  |
|  +-------------------------------------------------------------------------+  |
|  | Background Workers: Python 3 + yt-dlp + FFmpeg Audio Engine             |  |
|  +-------------------------------------------------------------------------+  |
+-------------------------------------------------------------------------------+
```
