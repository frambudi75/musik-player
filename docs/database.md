# Manajemen Data & Penyimpanan — Aura Music 💾🎵

Aura Music dirancang dengan arsitektur penyimpanan hibrida (*Hybrid Storage Architecture*) yang sangat efisien, menggabungkan sistem file flat-file di server dengan penyimpanan binary IndexedDB di sisi browser klien.

---

## 1. Struktur Penyimpanan Server (Flat-File & JSON)

Tidak memerlukan database SQL yang berat seperti MySQL atau PostgreSQL untuk beroperasi. Semua data dikelola secara cepat melalui:

```text
/songs/
├── [Audio Files]               <- Berkas MP3, FLAC, WAV, M4A, AAC, Opus
├── [Lyric Files .lrc]          <- File lirik tersinkronisasi dengan nama persis sama
├── covers/                     <- Direktori cache thumbnail album art
│   ├── [md5_hash].jpg          <- Gambar cover terekstrak dari tag APIC ID3
│   └── [md5_hash].png
└── .cache_library.json         <- Cache ringkasan JSON seluruh koleksi lagu
```

### Format Berkas `data_playlists.json`
Menyimpan seluruh playlist kustom pengguna dan daftar lagu yang disukai (*Liked Songs*):

```json
{
  "playlists": [
    {
      "id": "favorites",
      "name": "Liked Songs",
      "description": "Lagu-lagu favorit pilihan Anda",
      "cover": null,
      "song_ids": [
        "track_4a0cdbe39b33a5b6f0e92823a0ff9981",
        "track_94d5185d214619bfa780d6b672ea4e64"
      ]
    },
    {
      "id": "pl_1725451200000",
      "name": "Night Vibes Chill",
      "description": "Playlist santai malam hari",
      "cover": "songs/covers/night_art.jpg",
      "song_ids": [
        "track_08b4aa51e7b399201cbef62d9876e511"
      ]
    }
  ]
}
```

---

## 2. Penyimpanan Klien Browser (IndexedDB & LocalStorage)

### A. IndexedDB Database (`AuraOfflineDB`)
Digunakan untuk fitur **Offline Mode**, menyimpan berkas audio lengkap ke browser perangkat pengguna:

* **Database Name**: `AuraMusicDB`
* **Object Store**: `offline_tracks`
* **KeyPath**: `id` (contoh: `track_4a0cdbe39b33a5b6f0e92823a0ff9981`)

**Skema Record Record:**
```javascript
{
  id: "track_4a0cdbe39b33a5b6f0e92823a0ff9981",
  title: "Headbanger!!",
  artist: "BABYMETAL",
  album: "BABYMETAL",
  genre: "J-Rock",
  cover: "songs/covers/4a0cdbe.jpg",
  blob: Blob, // Binary audio file Blob (audio/mpeg)
  savedAt: 1725451200000,
  size: 13223806
}
```

---

### B. LocalStorage Keys

| Key | Tipe Data | Deskripsi |
| :--- | :--- | :--- |
| `aura_cached_library` | JSON Array | Cache daftar lagu untuk pemuatan instan (0ms first render). |
| `aura_playlists` | JSON Array | Cache playlist kustom lokal untuk sinkronisasi offline. |
| `aura_music_stats` | JSON Object | Statistik total jam dengar, top 10 lagu, dan top 5 artis. |
| `aura_eq_preset` | String | Nama preset equalizer yang sedang aktif (contoh: `Bass Boost`). |
| `aura_eq_gains` | JSON Array | Nilai gain 10-band EQ kustom (-12dB s/d +12dB). |
| `aura_dsp_speed` | Number | Kecepatan putar audio aktif (contoh: `1.0`). |
| `aura_dsp_crossfade`| Number | Durasi crossfade antar lagu dalam detik (contoh: `3`). |
| `aura_volume` | Number | Level volume audio (0.0 s/d 1.0). |
