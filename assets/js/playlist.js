/**
 * Playlist, Queue, Library & Personal Stats Manager
 */

class PlaylistManager {
  constructor() {
    this.library = []; // Master list of all songs
    this.currentPlaylist = []; // Currently active playlist/context
    this.queue = []; // Up next queue
    this.history = [];
    this.currentIndex = -1;
    this.currentSong = null;

    this.isShuffle = false;
    this.repeatMode = 'off'; // 'off', 'all', 'one'

    this.playlists = [
      {
        id: 'favorites',
        name: 'Liked Songs',
        description: 'Lagu-lagu favorit pilihan Anda',
        song_ids: []
      }
    ];

    // Personal Music Stats
    this.stats = {
      totalPlays: 0,
      totalSeconds: 0,
      trackPlays: {}, // id -> count
      artistPlays: {}, // artist -> count
      history: [] // { songId, title, artist, time }
    };

    this.onTrackChanged = null;
    this.onQueueUpdated = null;
    this.onLibraryLoaded = null;

    this.loadPersistedData();
    this.loadStats();
  }

  loadStats() {
    try {
      const raw = localStorage.getItem('aura_music_stats');
      if (raw) {
        const parsed = JSON.parse(raw);
        this.stats = { ...this.stats, ...parsed };
      }
    } catch (e) {
      console.warn('Failed to load stats:', e);
    }
  }

  saveStats() {
    try {
      localStorage.setItem('aura_music_stats', JSON.stringify(this.stats));
    } catch (e) {
      console.warn('Failed to save stats:', e);
    }
  }

  recordSongPlay(song) {
    if (!song) return;
    this.stats.totalPlays = (this.stats.totalPlays || 0) + 1;
    this.stats.trackPlays[song.id] = (this.stats.trackPlays[song.id] || 0) + 1;
    
    const artist = (song.artist || 'Unknown Artist').trim();
    this.stats.artistPlays[artist] = (this.stats.artistPlays[artist] || 0) + 1;

    this.stats.history.unshift({
      id: song.id,
      title: song.title,
      artist: song.artist,
      playedAt: Date.now()
    });

    if (this.stats.history.length > 50) {
      this.stats.history = this.stats.history.slice(0, 50);
    }

    this.saveStats();
  }

  recordListeningTime(seconds) {
    this.stats.totalSeconds = (this.stats.totalSeconds || 0) + Math.round(seconds);
    this.saveStats();
  }

  getStatsSummary() {
    const totalMinutes = Math.round((this.stats.totalSeconds || 0) / 60);
    
    // Formatted time string (e.g. "1 Jam 15 Menit" or "45 Menit")
    let formattedTime = `${totalMinutes} Menit`;
    if (totalMinutes >= 60) {
      const hours = Math.floor(totalMinutes / 60);
      const mins = totalMinutes % 60;
      formattedTime = mins > 0 ? `${hours} Jam ${mins} Menit` : `${hours} Jam`;
    }

    // Genre count aggregation
    const genreCounts = {};
    
    // Top Tracks
    const topTracks = Object.entries(this.stats.trackPlays || {})
      .map(([id, count]) => {
        const song = this.getSongById(id);
        const genre = song?.genre || 'Audio';
        genreCounts[genre] = (genreCounts[genre] || 0) + count;

        return {
          id,
          count,
          song: song || { id, title: 'Unknown Track', artist: 'Unknown', cover: 'assets/sample_covers/placeholder.svg', genre: 'Audio' }
        };
      })
      .sort((a, b) => b.count - a.count)
      .slice(0, 10);

    // Top Artists
    const topArtists = Object.entries(this.stats.artistPlays || {})
      .map(([name, count]) => ({ name, count }))
      .sort((a, b) => b.count - a.count)
      .slice(0, 8);

    // Top Genre
    const sortedGenres = Object.entries(genreCounts).sort((a, b) => b[1] - a[1]);
    const topGenre = sortedGenres.length > 0 ? sortedGenres[0][0] : 'Audio';

    // Listener Persona
    let persona = '🎧 Penikmat Musik Aktif';
    if (totalMinutes > 120) {
      persona = '⚡ Marathon Listener 🚀';
    } else if (topTracks.length > 0 && topTracks[0].count >= 5) {
      persona = '🔥 Replay On Loop Addict';
    } else if (topArtists.length >= 5) {
      persona = '🌟 Diverse Sound Explorer';
    }

    return {
      totalPlays: this.stats.totalPlays || 0,
      totalMinutes,
      formattedTime,
      topGenre,
      persona,
      favoriteSong: topTracks.length > 0 ? topTracks[0] : null,
      favoriteArtist: topArtists.length > 0 ? topArtists[0] : null,
      topTracks,
      topArtists,
      recentHistory: this.stats.history || []
    };
  }

  async loadPersistedData() {
    // Try fetch from server API
    try {
      const res = await fetch('api/playlist.php');
      if (res.ok) {
        const json = await res.json();
        if (json.status === 'success' && json.data) {
          if (json.data.playlists && json.data.playlists.length > 0) {
            this.playlists = json.data.playlists;
          }
        }
      }
    } catch (e) {
      // Fallback to localStorage
      const local = localStorage.getItem('aura_playlists');
      if (local) {
        this.playlists = JSON.parse(local);
      }
    }
  }

  async savePlaylists() {
    localStorage.setItem('aura_playlists', JSON.stringify(this.playlists));
    try {
      await fetch('api/playlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'save_all',
          playlists: this.playlists
        })
      });
    } catch (e) {
      console.warn('Failed to sync playlists to server, saved locally:', e);
    }
  }

  setLibrary(songs) {
    this.library = (songs || []).map((s) => {
      if (s && s.cover && typeof s.cover === 'string') {
        s.cover = s.cover.replace('songs/.covers/', 'songs/covers/');
      }
      return s;
    });
    if (this.currentPlaylist.length === 0) {
      this.currentPlaylist = [...this.library];
    }
    if (this.onLibraryLoaded) this.onLibraryLoaded(this.library);
  }

  getSongById(id) {
    return this.library.find((s) => s.id === id);
  }

  isLiked(songId) {
    const fav = this.playlists.find((p) => p.id === 'favorites');
    return fav ? fav.song_ids.includes(songId) : false;
  }

  async toggleLike(songId) {
    let fav = this.playlists.find((p) => p.id === 'favorites');
    if (!fav) {
      fav = { id: 'favorites', name: 'Liked Songs', song_ids: [] };
      this.playlists.push(fav);
    }

    const idx = fav.song_ids.indexOf(songId);
    let isNowLiked = false;
    if (idx >= 0) {
      fav.song_ids.splice(idx, 1);
      isNowLiked = false;
    } else {
      fav.song_ids.push(songId);
      isNowLiked = true;
    }

    await this.savePlaylists();
    return isNowLiked;
  }

  async createPlaylist(name, description = '') {
    const newPl = {
      id: 'pl_' + Date.now(),
      name: name.trim() || 'Playlist Baru',
      description: description.trim(),
      created_at: Math.floor(Date.now() / 1000),
      song_ids: []
    };
    this.playlists.push(newPl);
    await this.savePlaylists();
    return newPl;
  }

  async deletePlaylist(id) {
    if (id === 'favorites') return;
    this.playlists = this.playlists.filter((p) => p.id !== id);
    await this.savePlaylists();
  }

  async addSongToPlaylist(playlistId, songId) {
    const pl = this.playlists.find((p) => p.id === playlistId);
    if (pl && !pl.song_ids.includes(songId)) {
      pl.song_ids.push(songId);
      await this.savePlaylists();
      return true;
    }
    return false;
  }

  async removeSongFromPlaylist(playlistId, songId) {
    const pl = this.playlists.find((p) => p.id === playlistId);
    if (pl) {
      const idx = pl.song_ids.indexOf(songId);
      if (idx >= 0) {
        pl.song_ids.splice(idx, 1);
        await this.savePlaylists();
        return true;
      }
    }
    return false;
  }

  async toggleSongInPlaylist(playlistId, songId) {
    const pl = this.playlists.find((p) => p.id === playlistId);
    if (pl) {
      if (pl.song_ids.includes(songId)) {
        await this.removeSongFromPlaylist(playlistId, songId);
        return false;
      } else {
        await this.addSongToPlaylist(playlistId, songId);
        return true;
      }
    }
    return false;
  }

  playTrack(song, newPlaylistContext = null) {
    if (!song) return;

    if (newPlaylistContext) {
      this.currentPlaylist = [...newPlaylistContext];
    }

    this.currentSong = song;
    this.currentIndex = this.currentPlaylist.findIndex((s) => s.id === song.id);

    // Record Stats
    this.recordSongPlay(song);

    // Build upcoming queue
    this.rebuildQueue();

    // Notify track changed
    if (this.onTrackChanged) {
      this.onTrackChanged(song);
    }
  }

  playAtIndex(index) {
    if (index >= 0 && index < this.currentPlaylist.length) {
      this.playTrack(this.currentPlaylist[index]);
    }
  }

  next() {
    if (this.repeatMode === 'one' && this.currentSong) {
      this.playTrack(this.currentSong);
      return;
    }

    // Check if there is anything in queue
    if (this.queue.length > 0) {
      const nextSong = this.queue.shift();
      this.playTrack(nextSong);
      if (this.onQueueUpdated) this.onQueueUpdated(this.queue);
      return;
    }

    if (this.currentPlaylist.length === 0) return;

    if (this.isShuffle) {
      const randIdx = Math.floor(Math.random() * this.currentPlaylist.length);
      this.playTrack(this.currentPlaylist[randIdx]);
    } else {
      let nextIdx = this.currentIndex + 1;
      if (nextIdx >= this.currentPlaylist.length) {
        if (this.repeatMode === 'all') {
          nextIdx = 0;
        } else {
          return; // Stop at end of list
        }
      }
      this.playTrack(this.currentPlaylist[nextIdx]);
    }
  }

  prev() {
    if (window.AudioCore.audio.currentTime > 3) {
      // Seek to start if past 3 seconds
      window.AudioCore.seek(0);
      return;
    }

    if (this.currentPlaylist.length === 0) return;

    let prevIdx = this.currentIndex - 1;
    if (prevIdx < 0) {
      prevIdx = this.repeatMode === 'all' ? this.currentPlaylist.length - 1 : 0;
    }
    this.playTrack(this.currentPlaylist[prevIdx]);
  }

  rebuildQueue() {
    if (this.currentPlaylist.length === 0) {
      this.queue = [];
      return;
    }

    if (this.currentIndex >= 0 && this.currentIndex < this.currentPlaylist.length - 1) {
      this.queue = this.currentPlaylist.slice(this.currentIndex + 1);
    } else if (this.repeatMode === 'all') {
      this.queue = this.currentPlaylist.slice(0, this.currentIndex);
    } else {
      this.queue = [];
    }

    if (this.onQueueUpdated) this.onQueueUpdated(this.queue);
  }

  toggleShuffle() {
    this.isShuffle = !this.isShuffle;
    return this.isShuffle;
  }

  toggleRepeat() {
    if (this.repeatMode === 'off') {
      this.repeatMode = 'all';
    } else if (this.repeatMode === 'all') {
      this.repeatMode = 'one';
    } else {
      this.repeatMode = 'off';
    }
    return this.repeatMode;
  }
}

window.PlaylistManager = new PlaylistManager();
