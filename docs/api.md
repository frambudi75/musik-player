# Spesifikasi REST API — Aura Music 🔌🎵

Seluruh endpoint API Aura Music berada di bawah direktori `/api/` dan mengembalikan respons dalam format **JSON** dengan header `Content-Type: application/json`.

---

## 1. Pemindaian Library Audio

### `GET /api/scan.php`
Memindai seluruh folder `songs/` untuk mengindeks lagu, mengekstrak metadata ID3, cover album, dan lirik pendamping.

* **Query Parameters:**
  * `refresh` (opsional): `1` untuk memaksa scan ulang dan memperbarui cache.

* **Response Success (`200 OK`):**
```json
{
  "status": "success",
  "cached": true,
  "cache_driver": "redis_memory",
  "songs_dir": "/www/wwwroot/musik.overheat.my.id/songs",
  "total": 68,
  "songs": [
    {
      "id": "track_4a0cdbe39b33a5b6f0e92823a0ff9981",
      "title": "Headbanger!!",
      "artist": "BABYMETAL",
      "album": "BABYMETAL",
      "year": "2014",
      "genre": "J-Rock / Metal",
      "url": "songs/BABYMETAL - Headbanger.mp3",
      "filename": "BABYMETAL - Headbanger.mp3",
      "cover": "songs/covers/4a0cdbe39b33a5b6.jpg",
      "lyrics": "songs/BABYMETAL - Headbanger.lrc",
      "size": 13223806,
      "modified": 1725450000
    }
  ]
}
```

---

## 2. YouTube & Spotify Downloader

### `POST /api/yt_download.php`
Mengunduh lagu tunggal dari YouTube dan mengonversinya ke MP3.

* **Request Body (JSON):**
```json
{
  "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
}
```

* **Response Success (`200 OK`):**
```json
{
  "status": "success",
  "message": "Lagu berhasil diunduh!",
  "title": "Never Gonna Give You Up",
  "artist": "Rick Astley",
  "filename": "Rick Astley - Never Gonna Give You Up.mp3",
  "file_url": "songs/Rick Astley - Never Gonna Give You Up.mp3",
  "cover": "songs/covers/9b821a.jpg"
}
```

---

### `GET /api/playlist_info.php?url=<URL>`
Mengambil daftar lagu yang ada di dalam URL playlist YouTube sebelum diunduh.

* **Response Success (`200 OK`):**
```json
{
  "status": "success",
  "playlist_title": "Top Hits 2025",
  "total_tracks": 15,
  "tracks": [
    {
      "id": "abc123xyz",
      "title": "Track Name",
      "artist": "Artist Name",
      "duration": 215,
      "thumbnail": "https://i.ytimg.com/vi/abc123xyz/hqdefault.jpg"
    }
  ]
}
```

---

### `POST /api/spotify_download.php`
Mengunduh lagu berdasarkan link Spotify (Track atau Playlist).

* **Request Body (JSON):**
```json
{
  "url": "https://open.spotify.com/track/4cOdK2wGLETKBW3PvgPWqT"
}
```

---

## 3. Metadata & Cover Art Enricher

### `GET /api/enrich_metadata.php`
Mencari metadata lengkap dan artwork resolusi HD (1000x1000) dari iTunes Store & MusicBrainz API.

* **Query Parameters:**
  * `title`: Judul lagu (wajib).
  * `artist`: Nama artis (opsional).

* **Response Success (`200 OK`):**
```json
{
  "status": "success",
  "cached": true,
  "data": [
    {
      "title": "Bring Me To Life",
      "artist": "Evanescence",
      "album": "Fallen",
      "genre": "Rock",
      "year": "2003",
      "cover_url": "https://is1-ssl.mzstatic.com/image/thumb/Music115/.../1000x1000bb.jpg",
      "source": "iTunes"
    }
  ]
}
```

---

### `POST /api/edit_metadata.php`
Menyimpan perubahan tag ID3 (Judul, Artis, Album, Cover) langsung ke dalam file MP3 di server.

* **Request Body (JSON / Multipart):**
```json
{
  "file_path": "songs/sample.mp3",
  "title": "Judul Baru",
  "artist": "Artis Baru",
  "album": "Album Baru",
  "genre": "Pop",
  "cover_url": "https://is1-ssl.mzstatic.com/.../1000x1000bb.jpg"
}
```

---

## 4. Audio Trimmer & Ringtone Maker

### `POST /api/trim_audio.php`
Memotong file audio pada durasi tertentu menggunakan FFmpeg.

* **Request Body (JSON):**
```json
{
  "file_path": "songs/sample.mp3",
  "start_time": 45.5,
  "end_time": 75.0,
  "output_name": "My_Ringtone"
}
```

* **Response Success (`200 OK`):**
```json
{
  "status": "success",
  "message": "Audio berhasil dipotong!",
  "filename": "My_Ringtone_trimmed.mp3",
  "url": "songs/My_Ringtone_trimmed.mp3"
}
```

---

## 5. Lirik Tersinkronisasi (.LRC)

### `GET /api/lyrics_search.php`
Mencari berkas lirik tersinkronisasi online dari database LRCLIB.

* **Query Parameters:**
  * `title`: Judul lagu.
  * `artist`: Nama artis.

---

### `POST /api/save_lyrics.php`
Menyimpan teks lirik berformat `.lrc` ke dalam folder server.

* **Request Body (JSON):**
```json
{
  "song_path": "songs/sample.mp3",
  "lrc_content": "[00:12.50]Baris pertama lirik lagu\n[00:16.80]Baris kedua lirik lagu"
}
```

---

## 6. Manajemen Playlist

### `GET /api/playlist.php`
Mengambil semua playlist kustom pengguna yang tersimpan di `data_playlists.json`.

### `POST /api/playlist.php`
Membuat, memperbarui, atau menghapus playlist.

* **Request Body (JSON):**
```json
{
  "action": "save_all",
  "playlists": [
    {
      "id": "favorites",
      "name": "Liked Songs",
      "song_ids": ["track_123", "track_456"]
    }
  ]
}
```
