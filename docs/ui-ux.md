# Desain UI/UX & Interaksi — Aura Music 🎨🎵

Aura Music mengusung filosofi desain modern dengan gaya visual **Dark Glassmorphism**, terinspirasi dari standar antarmuka pemutar musik kelas dunia (Spotify, Apple Music, dan Tidal).

---

## 1. Palet Warna & Token Desain

Antarmuka menggunakan sistem variabel CSS yang dinamis dan mendukung *Adaptive Ambient Glow*:

```css
:root {
  /* Surface & Backgrounds */
  --bg-main: #0b0c10;
  --bg-card: rgba(22, 27, 34, 0.7);
  --bg-card-hover: rgba(33, 38, 45, 0.85);
  --bg-surface: rgba(13, 17, 23, 0.8);
  --glass-border: rgba(255, 255, 255, 0.08);

  /* Typography */
  --text-primary: #f0f6fc;
  --text-secondary: #8b949e;
  --text-tertiary: #484f58;

  /* Dynamic Accents */
  --accent-primary: #7928ca;
  --accent-gradient: linear-gradient(135deg, #7928ca, #ff0080);
  --accent-glow: rgba(121, 40, 202, 0.35);

  /* Layout & Curves */
  --radius-sm: 8px;
  --radius-md: 14px;
  --radius-lg: 20px;
  --radius-full: 9999px;
  --glass-blur: blur(20px);
}
```

---

## 2. Fitur Visual Unggulan

### A. Adaptive Ambient Glow
Setiap kali lagu berganti, modul `ambient-color.js` mengekstrak warna dominan dari gambar cover album, lalu menginjeksikan nilai RGB tersebut ke `--accent-primary` dan latar belakang panggung secara halus (*smooth CSS transition*).

### B. Interactive Waveform Scrubber
* Canvas gelombang suara interaktif yang menggambarkan dinamika amplitudo file audio.
* Pengguna dapat mengarahkan kursor (*hover*) untuk melihat indikator waktu dan mengeklik langsung (*seek*) ke bagian bridge atau chorus lagu.

### C. Canvas Audio Visualizer
* Dilengkapi 5 mode visualisasi frekuensi:
  1. **Neon Bars**: Batang spektrum audio dengan gradien warna elektrik.
  2. **Smooth Wave**: Gelombang sinus berosilasi mengikuti ritme nada.
  3. **Circle Pulse**: Lingkaran detak jantung frekuensi bass.
  4. **Cyber Particles**: Partikel mengambang yang meledak saat beat drop terjadi.
  5. **Ambient Glow**: Efek pendaran cahaya lembut di sekeliling artwork.

---

## 3. Tata Letak Responsif (Mobile & Desktop)

* **Desktop (1024px+)**:
  * Sidebar navigasi kiri tetap (*sticky*).
  * Hero Section menampilkan lagu unggulan.
  * Tampilan ganda: Tabel Detail (*Table View*) dan Galeri Kartu (*Grid Cards*).
  * Now Playing Bar bawah permanen dengan kontrol EQ dan scrubber gelombang.

* **Mobile & Tablet (< 768px)**:
  * Bottom Navigation Bar untuk navigasi cepat (Koleksi, Playlist, Statistik, Offline).
  * Mini Player mengambang (*floating*) di atas navigation bar.
  * *Fullscreen Immersive Player Modal* saat mini player disentuh, lengkap dengan penampil lirik karaoke besar dan kontrol gestur.
