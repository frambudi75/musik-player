/**
 * Aura Music - Master Application Controller
 * Connects UI, Web Audio API, DSP FX Studio, Visualizer, Lyrics, PiP Mini-Player, Sleep Timer, and Statistics
 */

document.addEventListener('DOMContentLoaded', async () => {
  // UI Elements
  const searchInput = document.getElementById('search-input');
  const songTableBody = document.getElementById('song-table-body');
  const songsGrid = document.getElementById('songs-grid');
  const tableViewWrap = document.getElementById('table-view-wrap');
  const statsViewWrap = document.getElementById('stats-view-wrap');
  const viewToggleBtns = document.querySelectorAll('.view-btn');
  const sectionTitle = document.getElementById('section-title');
  const navItems = document.querySelectorAll('.nav-item');
  const sidebarPlaylistsEl = document.getElementById('sidebar-playlists');

  // Hero Elements
  const heroBanner = document.getElementById('hero-banner');
  const heroCover = document.getElementById('hero-cover');
  const heroTitle = document.getElementById('hero-title');
  const heroArtist = document.getElementById('hero-artist');
  const heroPlayBtn = document.getElementById('hero-play-btn');

  // Player Bar Elements
  const playerCover = document.getElementById('player-cover');
  const playerTitle = document.getElementById('player-title');
  const playerArtist = document.getElementById('player-artist');
  const playerHeartBtn = document.getElementById('player-heart-btn');
  const playPauseBtn = document.getElementById('play-pause-btn');
  const playIcon = document.getElementById('play-icon');
  const pauseIcon = document.getElementById('pause-icon');
  const prevBtn = document.getElementById('prev-btn');
  const nextBtn = document.getElementById('next-btn');
  const shuffleBtn = document.getElementById('shuffle-btn');
  const repeatBtn = document.getElementById('repeat-btn');
  const timeCurrent = document.getElementById('time-current');
  const timeTotal = document.getElementById('time-total');
  const progressBar = document.getElementById('progress-bar');
  const progressFill = document.getElementById('progress-fill');
  const progressThumb = document.getElementById('progress-thumb');
  const volumeBar = document.getElementById('volume-bar');
  const volumeFill = document.getElementById('volume-fill');
  const volumeBtn = document.getElementById('volume-btn');
  const pipBtn = document.getElementById('pip-btn');
  const sleepTimerBtn = document.getElementById('sleep-timer-btn');
  const sleepBadge = document.getElementById('sleep-badge');

  // Right Panel Elements
  const rightPanel = document.getElementById('right-panel');
  const panelToggleBtn = document.getElementById('panel-toggle-btn');
  const panelTabBtns = document.querySelectorAll('.panel-tab-btn');
  const lyricsView = document.getElementById('lyrics-view');
  const visualizerView = document.getElementById('visualizer-view');
  const queueView = document.getElementById('queue-view');
  const visualizerCanvas = document.getElementById('visualizer-canvas');
  const visModeChips = document.querySelectorAll('.vis-mode-chip');

  // Lyrics Timing Sync
  const syncMinusBtn = document.getElementById('sync-minus-btn');
  const syncPlusBtn = document.getElementById('sync-plus-btn');
  const syncResetBtn = document.getElementById('sync-reset-btn');
  const syncOffsetVal = document.getElementById('sync-offset-val');

  // Immersive Mode Elements
  const immersiveOverlay = document.getElementById('immersive-overlay');
  const immersiveCloseBtn = document.getElementById('immersive-close-btn');
  const immersiveBtn = document.getElementById('immersive-btn');
  const immersiveCover = document.getElementById('immersive-cover');
  const immersiveTitle = document.getElementById('immersive-title');
  const immersiveArtist = document.getElementById('immersive-artist');
  const immersiveLyrics = document.getElementById('immersive-lyrics');

  // Sleep Timer Modal
  const sleepModal = document.getElementById('sleep-modal');
  const sleepCloseBtn = document.getElementById('sleep-close-btn');
  const sleepOptBtns = document.querySelectorAll('.sleep-opt-btn');

  // Equalizer & DSP FX Modal
  const eqModal = document.getElementById('eq-modal');
  const eqBtn = document.getElementById('eq-btn');
  const eqCloseBtn = document.getElementById('eq-close-btn');
  const eqPresetChips = document.querySelectorAll('.eq-preset-chip');
  const dspModeChips = document.querySelectorAll('.dsp-mode-chip');
  const dspSpeedSlider = document.getElementById('dsp-speed-slider');
  const dspSpeedVal = document.getElementById('dsp-speed-val');
  const dspCrossfadeSlider = document.getElementById('dsp-crossfade-slider');
  const dspCrossfadeVal = document.getElementById('dsp-crossfade-val');

  // Upload & Playlists Modals
  const uploadModal = document.getElementById('upload-modal');
  const uploadTriggerBtn = document.getElementById('upload-trigger-btn');
  const uploadCloseBtn = document.getElementById('upload-close-btn');
  const audioFileInput = document.getElementById('audio-file-input');
  const lyricsFileInput = document.getElementById('lyrics-file-input');
  const dropzoneBox = document.getElementById('dropzone-box');
  const submitUploadBtn = document.getElementById('submit-upload-btn');
  const newPlaylistModal = document.getElementById('new-playlist-modal');
  const createPlaylistBtn = document.getElementById('create-playlist-btn');
  const newPlaylistCloseBtn = document.getElementById('new-playlist-close-btn');
  const savePlaylistBtn = document.getElementById('save-playlist-btn');
  const playlistNameInput = document.getElementById('playlist-name-input');
  const toastContainer = document.getElementById('toast-container');

  // State
  let currentNavTab = 'library';
  let currentActivePlaylistId = null;
  let isDraggingScrubber = false;
  let visualizer = null;
  let sleepTimerInterval = null;
  let sleepTargetSeconds = 0;
  let sleepMode = 'off'; // 'off', 'countdown', 'end_of_track'
  let lastTimeUpdate = 0;
  let pipAnimationId = null;

  const DEFAULT_COVER = 'assets/sample_covers/placeholder.svg';

  /**
   * auraFetch — Direct Fetch wrapper
   */
  async function auraFetch(url, options = {}) {
    return fetch(url, options);
  }

  // Initialize Visualizer, Waveform Scrubber & Lyrics
  if (visualizerCanvas) {
    visualizer = new window.AudioVisualizer(visualizerCanvas);
  }

  const waveformCanvas = document.getElementById('waveform-canvas');
  const toggleWaveformBtn = document.getElementById('toggle-waveform-btn');
  let waveformScrubber = null;
  let isWaveformActive = false;

  if (waveformCanvas && window.WaveformScrubber) {
    waveformScrubber = new window.WaveformScrubber(waveformCanvas, {
      onSeek: (seconds) => {
        window.AudioCore.seek(seconds);
      }
    });
  }

  window.LyricsEngine.setContainers(lyricsView, immersiveLyrics);

  // Helper: Get safe public cover URL (replaces legacy .covers/ with covers/)
  function getSafeCoverUrl(songOrUrl) {
    if (!songOrUrl) return DEFAULT_COVER;
    let url = (typeof songOrUrl === 'object') ? (songOrUrl.cover || '') : String(songOrUrl);
    if (!url) return DEFAULT_COVER;
    if (url.includes('songs/.covers/')) {
      url = url.replace('songs/.covers/', 'songs/covers/');
    }
    return url;
  }

  // Helper: Escape HTML
  function escapeHTML(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Show Toast Notification
  function showToast(message, icon = '✓') {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `<span>${icon}</span> <span>${escapeHTML(message)}</span>`;
    toastContainer.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transition = 'opacity 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 2800);
  }

  // Format Seconds to MM:SS
  function formatTime(seconds) {
    if (isNaN(seconds) || seconds < 0) return '0:00';
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m}:${s < 10 ? '0' : ''}${s}`;
  }

  let currentSortMode = 'default';
  let currentGenreFilter = '';

  // Update Genre Filter Dropdown Options
  function updateGenreFilterOptions() {
    const genreSelect = document.getElementById('genre-filter-select');
    if (!genreSelect) return;
    const currentVal = genreSelect.value;
    const genres = new Set();
    window.PlaylistManager.library.forEach((s) => {
      if (s.genre && s.genre.trim() && s.genre.toLowerCase() !== 'other' && s.genre.toLowerCase() !== 'audio') {
        genres.add(s.genre.trim());
      }
    });

    genreSelect.innerHTML = '<option value="">Semua Genre</option>';
    Array.from(genres).sort().forEach((g) => {
      const opt = document.createElement('option');
      opt.value = g;
      opt.textContent = g;
      if (g === currentVal) opt.selected = true;
      genreSelect.appendChild(opt);
    });
  }

  // Skeleton Loading Placeholders
  function renderSkeletonLoading() {
    if (songTableBody && window.PlaylistManager.library.length === 0) {
      songTableBody.innerHTML = Array(6).fill(0).map(() => `
        <tr class="song-row skeleton-row" style="pointer-events: none;">
          <td style="width: 40px;"><div class="skeleton" style="width: 18px; height: 14px;"></div></td>
          <td>
            <div style="display: flex; align-items: center; gap: 12px;">
              <div class="skeleton skeleton-cover"></div>
              <div class="skeleton-text-group">
                <div class="skeleton skeleton-line title"></div>
                <div class="skeleton skeleton-line subtitle"></div>
              </div>
            </div>
          </td>
          <td><div class="skeleton skeleton-line" style="width: 50%;"></div></td>
          <td><div class="skeleton skeleton-line" style="width: 35%;"></div></td>
          <td><div class="skeleton skeleton-line" style="width: 30px;"></div></td>
          <td><div class="skeleton" style="width: 24px; height: 24px; border-radius: 50%;"></div></td>
        </tr>
      `).join('');
    }
    if (songsGrid && window.PlaylistManager.library.length === 0) {
      songsGrid.innerHTML = Array(6).fill(0).map(() => `
        <div class="skeleton-card" style="pointer-events: none;">
          <div class="skeleton skeleton-card-cover"></div>
          <div class="skeleton-text-group" style="padding: 4px 0;">
            <div class="skeleton skeleton-line title" style="width: 70%; height: 14px;"></div>
            <div class="skeleton skeleton-line subtitle" style="width: 45%; height: 10px;"></div>
          </div>
        </div>
      `).join('');
    }
  }

  // Load Library with Instant Client-Side Pre-warming Cache
  async function fetchLibrary(forceRefresh = false) {
    // 1. Instant Cache Pre-warming (0ms First Render)
    if (!forceRefresh && window.PlaylistManager.library.length === 0) {
      try {
        const localCached = localStorage.getItem('aura_cached_library');
        if (localCached) {
          const parsed = JSON.parse(localCached);
          if (Array.isArray(parsed) && parsed.length > 0) {
            const sanitized = parsed.map((s) => {
              if (s && s.cover && typeof s.cover === 'string') {
                s.cover = s.cover.replace('songs/.covers/', 'songs/covers/');
              }
              return s;
            });
            window.PlaylistManager.setLibrary(sanitized);
            updateGenreFilterOptions();
            renderCurrentView();
            renderSidebarPlaylists();
            if (!window.PlaylistManager.currentSong) {
              setupHeroFeatured(sanitized[0]);
            }
          }
        }
      } catch (err) {}
    }

    if (window.PlaylistManager.library.length === 0) {
      renderSkeletonLoading();
    }

    // 2. Fetch from High-Performance Server API
    try {
      const res = await fetch(`api/scan.php${forceRefresh ? '?refresh=1' : ''}`);
      const data = await res.json();
      if (data.status === 'success') {
        window.PlaylistManager.setLibrary(data.songs);
        if (data.songs_dir) {
          const pathEl = document.getElementById('server-songs-path');
          if (pathEl) pathEl.textContent = data.songs_dir;
        }
        try {
          localStorage.setItem('aura_cached_library', JSON.stringify(window.PlaylistManager.library));
        } catch (e) {}
        updateGenreFilterOptions();
        renderCurrentView();
        renderSidebarPlaylists();
        if (data.songs.length > 0 && !window.PlaylistManager.currentSong) {
          setupHeroFeatured(data.songs[0]);
        }
      }
    } catch (e) {
      console.error('Error fetching library:', e);
    }
  }

  // Setup Hero Featured Section
  function setupHeroFeatured(song) {
    if (!song) return;
    const coverUrl = getSafeCoverUrl(song);
    heroCover.src = coverUrl;
    heroCover.onerror = () => { heroCover.src = DEFAULT_COVER; };
    heroTitle.textContent = song.title;
    heroArtist.textContent = `${song.artist} • ${song.album || 'Single'}`;
    heroPlayBtn.onclick = () => {
      window.PlaylistManager.playTrack(song);
    };
  }

  // In-Memory Fast Lookup Maps & State
  const songsMap = new Map();
  let currentDisplayedSongs = [];

  // Instant Active Song Highlight (Zero DOM Thrashing)
  function updateActiveIndicators(activeId) {
    document.querySelectorAll('.song-row').forEach((row) => {
      const isPlaying = row.dataset.id === activeId;
      row.classList.toggle('playing', isPlaying);
      const idxCell = row.querySelector('.song-cell-index');
      if (idxCell) {
        if (isPlaying) {
          idxCell.innerHTML = `
            <div class="sound-waves-indicator">
              <div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div>
            </div>
          `;
        } else {
          idxCell.textContent = row.dataset.index || '';
        }
      }
    });

    document.querySelectorAll('.card-item').forEach((card) => {
      card.classList.toggle('playing', card.dataset.id === activeId);
    });
  }

  // Fast Table View Renderer (Single Layout Pass)
  function renderSongTable(songs) {
    currentDisplayedSongs = songs;
    if (songs.length === 0) {
      songTableBody.innerHTML = `
        <tr>
          <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-tertiary);">
            Tidak ada lagu yang ditemukan di koleksi
          </td>
        </tr>
      `;
      return;
    }

    const isInsideCustomPlaylist = currentNavTab === 'playlist' && currentActivePlaylistId && currentActivePlaylistId !== 'favorites';
    const currentSongId = window.PlaylistManager.currentSong ? window.PlaylistManager.currentSong.id : null;

    const rowsHtml = songs.map((song, i) => {
      songsMap.set(song.id, song);
      const isPlaying = currentSongId === song.id;
      const isLiked = window.PlaylistManager.isLiked(song.id);
      const isSaved = song.isOffline || (window.OfflineDB && window.OfflineDB.hasOfflineBlob(song.id));
      const coverSrc = getSafeCoverUrl(song);

      return `
        <tr class="song-row ${isPlaying ? 'playing' : ''}" data-id="${escapeHTML(song.id)}" data-index="${i + 1}">
          <td class="song-cell-index">
            ${isPlaying ? `
              <div class="sound-waves-indicator">
                <div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div>
              </div>
            ` : (i + 1)}
          </td>
          <td>
            <div class="song-cell-main">
              <img class="song-thumbnail" src="${escapeHTML(coverSrc)}" alt="Cover" loading="lazy" onerror="this.src='${DEFAULT_COVER}'" />
              <div class="song-info-stack">
                <span class="song-cell-title">${escapeHTML(song.title)}</span>
                <span class="song-cell-artist">${escapeHTML(song.artist)}</span>
              </div>
            </div>
          </td>
          <td class="song-cell-album col-album">${escapeHTML(song.album || '-')}</td>
          <td class="song-cell-duration col-genre">${escapeHTML(song.genre || 'Audio')}</td>
          <td class="song-cell-actions">
            <div class="action-btn-group">
              <button class="heart-btn ${isLiked ? 'liked' : ''}" title="${isLiked ? 'Batal Suka' : 'Suka'}" data-action="like" data-id="${escapeHTML(song.id)}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="${isLiked ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2">
                  <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
              </button>
              <button class="offline-btn ${isSaved ? 'saved' : ''}" title="${isSaved ? 'Hapus dari Penyimpanan Offline' : 'Simpan Offline (Bisa diputar tanpa internet)'}" data-action="offline" data-id="${escapeHTML(song.id)}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="7 10 12 15 17 10"></polyline>
                  <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
              </button>
              <button class="add-pl-btn" title="Tambahkan ke Playlist" data-action="add-pl" data-id="${escapeHTML(song.id)}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
              </button>
              <button class="trim-btn" title="Potong Ringtone / Audio Trimmer" data-action="trim" data-id="${escapeHTML(song.id)}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="6" cy="6" r="3"></circle>
                  <circle cx="6" cy="18" r="3"></circle>
                  <line x1="20" y1="4" x2="8.12" y2="15.88"></line>
                  <line x1="14.47" y1="14.48" x2="20" y2="20"></line>
                  <line x1="8.12" y1="8.12" x2="12" y2="12"></line>
                </svg>
              </button>
              <button class="lrc-btn" title="Studio Buat Lirik Sinkron (.LRC)" data-action="lrc" data-id="${escapeHTML(song.id)}">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 19l7-7 3 3-7 7-3-3z"></path>
                  <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path>
                  <path d="M2 2l7.586 7.586"></path>
                </svg>
              </button>
              <button class="edit-meta-btn" title="Edit Info Lagu" data-action="edit-meta" data-id="${escapeHTML(song.id)}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 20h9"></path>
                  <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                </svg>
              </button>
              ${isInsideCustomPlaylist ? `
                <button class="remove-pl-btn" title="Hapus dari playlist ini" data-action="remove-pl" data-id="${escapeHTML(song.id)}">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                  </svg>
                </button>
              ` : ''}
            </div>
          </td>
        </tr>
      `;
    }).join('');

    songTableBody.innerHTML = rowsHtml;
  }

  // Fast Grid View Renderer (Single Layout Pass)
  function renderSongGrid(songs) {
    if (songs.length === 0) {
      songsGrid.innerHTML = '';
      return;
    }

    const currentSongId = window.PlaylistManager.currentSong ? window.PlaylistManager.currentSong.id : null;

    const cardsHtml = songs.map((song) => {
      songsMap.set(song.id, song);
      const isPlaying = currentSongId === song.id;
      const isSaved = song.isOffline || (window.OfflineDB && window.OfflineDB.hasOfflineBlob(song.id));
      const coverSrc = getSafeCoverUrl(song);

      return `
        <div class="card-item ${isPlaying ? 'playing' : ''}" data-id="${escapeHTML(song.id)}">
          <div class="card-cover-wrap">
            <img class="card-cover" src="${escapeHTML(coverSrc)}" alt="Cover" loading="lazy" onerror="this.src='${DEFAULT_COVER}'" />
            <div class="card-floating-play">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <polygon points="5 3 19 12 5 21 5 3"></polygon>
              </svg>
            </div>
          </div>
          <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
            <div style="min-width: 0; flex: 1;">
              <div class="card-title">${escapeHTML(song.title)}</div>
              <div class="card-subtitle">${escapeHTML(song.artist)}</div>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
              <button class="offline-btn offline-card-btn ${isSaved ? 'saved' : ''}" style="width: 24px; height: 24px;" title="${isSaved ? 'Tersimpan Offline' : 'Simpan Offline'}" data-action="offline" data-id="${escapeHTML(song.id)}">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="7 10 12 15 17 10"></polyline>
                  <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
              </button>
              <button class="trim-btn trim-card-btn" style="width: 24px; height: 24px;" title="Potong Ringtone" data-action="trim" data-id="${escapeHTML(song.id)}">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="6" cy="6" r="3"></circle>
                  <circle cx="6" cy="18" r="3"></circle>
                  <line x1="20" y1="4" x2="8.12" y2="15.88"></line>
                </svg>
              </button>
              <button class="lrc-btn lrc-card-btn" style="width: 24px; height: 24px;" title="Buat Lirik LRC" data-action="lrc" data-id="${escapeHTML(song.id)}">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 19l7-7 3 3-7 7-3-3z"></path>
                  <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path>
                </svg>
              </button>
              <button class="edit-meta-btn edit-card-btn" style="width: 24px; height: 24px;" title="Edit Info" data-action="edit-meta" data-id="${escapeHTML(song.id)}">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 20h9"></path>
                  <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                </svg>
              </button>
              <button class="icon-btn add-pl-card-btn" style="width: 24px; height: 24px; flex-shrink: 0;" title="Tambah ke Playlist" data-action="add-pl" data-id="${escapeHTML(song.id)}">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
              </button>
            </div>
          </div>
        </div>
      `;
    }).join('');

    songsGrid.innerHTML = cardsHtml;
  }

  // Centralized High-Performance Event Delegation (Table)
  songTableBody.addEventListener('click', async (e) => {
    const btn = e.target.closest('button');
    const tr = e.target.closest('.song-row');
    if (!tr) return;

    const songId = tr.dataset.id;
    const song = songsMap.get(songId) || window.PlaylistManager.getSongById(songId);
    if (!song) return;

    if (btn) {
      const action = btn.dataset.action;
      if (action === 'like') {
        const liked = await window.PlaylistManager.toggleLike(song.id);
        btn.classList.toggle('liked', liked);
        btn.querySelector('svg').setAttribute('fill', liked ? 'currentColor' : 'none');
        if (window.PlaylistManager.currentSong && window.PlaylistManager.currentSong.id === song.id) {
          playerHeartBtn.classList.toggle('liked', liked);
          playerHeartBtn.querySelector('svg').setAttribute('fill', liked ? 'currentColor' : 'none');
        }
        showToast(liked ? 'Ditambahkan ke Liked Songs' : 'Dihapus dari Liked Songs');
        if (currentNavTab === 'liked') renderCurrentView();
      } else if (action === 'offline') {
        const isSaved = await window.OfflineDB.isSaved(song.id);
        if (isSaved) {
          await window.OfflineDB.removeTrack(song.id);
          btn.classList.remove('saved');
          showToast(`Lagu offline "${song.title}" dihapus`, '🗑️');
        } else {
          showToast('Mengunduh lagu untuk offline...', '⏳');
          btn.style.opacity = '0.5';
          try {
            await window.OfflineDB.saveTrack(song);
            btn.classList.add('saved');
            btn.style.opacity = '1';
            showToast(`"${song.title}" tersimpan offline!`, '💾');
          } catch (err) {
            btn.style.opacity = '1';
            showToast('Gagal mengunduh offline: ' + err.message, '⚠️');
          }
        }
        if (currentNavTab === 'offline') renderCurrentView();
      } else if (action === 'add-pl') {
        openAddToPlaylistModal(song);
      } else if (action === 'trim') {
        openTrimmerModal(song);
      } else if (action === 'lrc') {
        openLrcMakerModal(song);
      } else if (action === 'edit-meta') {
        openEditMetadataModal(song);
      } else if (action === 'remove-pl') {
        await window.PlaylistManager.removeSongFromPlaylist(currentActivePlaylistId, song.id);
        showToast('Lagu dihapus dari playlist', '✓');
        renderSidebarPlaylists();
        renderCurrentView();
      }
    } else {
      window.PlaylistManager.playTrack(song, currentDisplayedSongs);
    }
  });

  // Centralized High-Performance Event Delegation (Grid)
  songsGrid.addEventListener('click', async (e) => {
    const btn = e.target.closest('button');
    const card = e.target.closest('.card-item');
    if (!card) return;

    const songId = card.dataset.id;
    const song = songsMap.get(songId) || window.PlaylistManager.getSongById(songId);
    if (!song) return;

    if (btn) {
      const action = btn.dataset.action;
      if (action === 'offline') {
        const isSaved = await window.OfflineDB.isSaved(song.id);
        if (isSaved) {
          await window.OfflineDB.removeTrack(song.id);
          btn.classList.remove('saved');
          showToast(`Lagu offline "${song.title}" dihapus`, '🗑️');
        } else {
          showToast('Mengunduh lagu untuk offline...', '⏳');
          btn.style.opacity = '0.5';
          try {
            await window.OfflineDB.saveTrack(song);
            btn.classList.add('saved');
            btn.style.opacity = '1';
            showToast(`"${song.title}" tersimpan offline!`, '💾');
          } catch (err) {
            btn.style.opacity = '1';
            showToast('Gagal mengunduh offline: ' + err.message, '⚠️');
          }
        }
        if (currentNavTab === 'offline') renderCurrentView();
      } else if (action === 'add-pl') {
        openAddToPlaylistModal(song);
      } else if (action === 'trim') {
        openTrimmerModal(song);
      } else if (action === 'lrc') {
        openLrcMakerModal(song);
      } else if (action === 'edit-meta') {
        openEditMetadataModal(song);
      }
    } else {
      window.PlaylistManager.playTrack(song, currentDisplayedSongs);
    }
  });

  // Render Statistics View
  function renderStatsView() {
    const summary = window.PlaylistManager.getStatsSummary();
    const statMinutesEl = document.getElementById('stat-total-minutes');
    const statPlaysEl = document.getElementById('stat-total-plays');
    const statArtistsEl = document.getElementById('stat-total-artists');
    const topTracksContainer = document.getElementById('top-tracks-container');
    const topArtistsContainer = document.getElementById('top-artists-container');

    if (statMinutesEl) statMinutesEl.textContent = summary.totalMinutes;
    if (statPlaysEl) statPlaysEl.textContent = summary.totalPlays;
    if (statArtistsEl) statArtistsEl.textContent = summary.topArtists.length;

    if (topTracksContainer) {
      topTracksContainer.innerHTML = '';
      if (summary.topTracks.length === 0) {
        topTracksContainer.innerHTML = `<div style="color: var(--text-tertiary); font-size: 0.85rem;">Belum ada riwayat pemutaran. Putar lagu untuk melihat statistik Anda!</div>`;
      } else {
        summary.topTracks.forEach((item, i) => {
          const row = document.createElement('div');
          row.className = 'top-track-card';
          row.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
              <span style="font-family: var(--font-mono); font-weight: 700; color: ${i === 0 ? 'var(--accent-amber)' : 'var(--text-tertiary)'}; font-size: 0.95rem; width: 22px;">#${i + 1}</span>
              <div style="display: flex; flex-direction: column; min-width: 0;">
                <span style="font-weight: 600; color: var(--text-primary); font-size: 0.88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHTML(item.song.title)}</span>
                <span style="font-size: 0.75rem; color: var(--text-secondary);">${escapeHTML(item.song.artist)}</span>
              </div>
            </div>
            <span style="font-family: var(--font-mono); font-size: 0.78rem; font-weight: 700; color: var(--accent-primary); background: var(--accent-subtle); padding: 4px 8px; border-radius: var(--radius-full);">${item.count}x</span>
          `;
          row.onclick = () => {
            const track = window.PlaylistManager.getSongById(item.id);
            if (track) window.PlaylistManager.playTrack(track);
          };
          topTracksContainer.appendChild(row);
        });
      }
    }

    if (topArtistsContainer) {
      topArtistsContainer.innerHTML = '';
      if (summary.topArtists.length === 0) {
        topArtistsContainer.innerHTML = `<div style="color: var(--text-tertiary); font-size: 0.85rem;">Belum ada data artis.</div>`;
      } else {
        summary.topArtists.forEach((item, i) => {
          const row = document.createElement('div');
          row.className = 'top-artist-item';
          row.innerHTML = `
            <span style="font-weight: 600; color: var(--text-primary); font-size: 0.88rem;">${escapeHTML(item.name)}</span>
            <span style="font-family: var(--font-mono); font-size: 0.78rem; color: var(--text-secondary);">${item.count} lagu diputar</span>
          `;
          topArtistsContainer.appendChild(row);
        });
      }
    }
  }

  // Render Current Tab Content
  async function renderCurrentView() {
    if (currentNavTab === 'stats') {
      heroBanner.style.display = 'none';
      document.getElementById('section-header').style.display = 'none';
      tableViewWrap.style.display = 'none';
      songsGrid.style.display = 'none';
      statsViewWrap.style.display = 'block';
      renderStatsView();
      return;
    }

    heroBanner.style.display = 'flex';
    document.getElementById('section-header').style.display = 'flex';
    statsViewWrap.style.display = 'none';

    let list = [...window.PlaylistManager.library];
    const query = (searchInput.value || '').trim().toLowerCase();

    if (currentNavTab === 'offline') {
      try {
        const savedTracks = await window.OfflineDB.getAllSavedSongs();
        list = savedTracks;
        sectionTitle.textContent = `Lagu Tersimpan Offline (${savedTracks.length})`;
      } catch (e) {
        list = [];
        sectionTitle.textContent = 'Lagu Tersimpan Offline (0)';
      }
    } else if (currentNavTab === 'liked') {
      const fav = window.PlaylistManager.playlists.find((p) => p.id === 'favorites');
      const likedIds = fav ? fav.song_ids : [];
      list = list.filter((s) => likedIds.includes(s.id));
      sectionTitle.innerHTML = 'Liked Songs';
    } else if (currentNavTab === 'playlist' && currentActivePlaylistId) {
      const pl = window.PlaylistManager.playlists.find((p) => p.id === currentActivePlaylistId);
      if (pl) {
        list = list.filter((s) => pl.song_ids.includes(s.id));
        sectionTitle.innerHTML = `
          <span>${escapeHTML(pl.name)}</span>
          <button id="delete-curr-playlist-btn" class="btn-subtle-scan" style="margin-left: 12px; color: #ef4444; border-color: rgba(239, 68, 68, 0.3); font-size: 0.75rem;" title="Hapus playlist ini">
            🗑️ Hapus Playlist
          </button>
        `;
        setTimeout(() => {
          const delBtn = document.getElementById('delete-curr-playlist-btn');
          if (delBtn) {
            delBtn.onclick = async () => {
              if (confirm(`Hapus playlist "${pl.name}"?`)) {
                await window.PlaylistManager.deletePlaylist(pl.id);
                showToast(`Playlist "${pl.name}" dihapus`, 'ℹ️');
                currentNavTab = 'library';
                currentActivePlaylistId = null;
                navItems.forEach((n) => {
                  if (n.dataset.tab === 'library') n.classList.add('active');
                  else n.classList.remove('active');
                });
                renderSidebarPlaylists();
                renderCurrentView();
              }
            };
          }
        }, 50);
      }
    } else {
      sectionTitle.textContent = 'Koleksi Musik';
    }

    if (query) {
      list = list.filter((s) =>
        s.title.toLowerCase().includes(query) ||
        s.artist.toLowerCase().includes(query) ||
        (s.album && s.album.toLowerCase().includes(query))
      );
      sectionTitle.textContent = `Hasil Pencarian "${query}"`;
    }

    // Genre Filter
    if (currentGenreFilter) {
      list = list.filter((s) => s.genre && s.genre.toLowerCase() === currentGenreFilter.toLowerCase());
    }

    // Sort Modes
    if (currentSortMode === 'newest') {
      list.sort((a, b) => (b.modified || 0) - (a.modified || 0));
    } else if (currentSortMode === 'top') {
      list.sort((a, b) => (window.PlaylistManager.stats.trackPlays[b.id] || 0) - (window.PlaylistManager.stats.trackPlays[a.id] || 0));
    } else if (currentSortMode === 'title') {
      list.sort((a, b) => a.title.localeCompare(b.title));
    } else if (currentSortMode === 'artist') {
      list.sort((a, b) => a.artist.localeCompare(b.artist));
    }

    renderSongTable(list);
    renderSongGrid(list);
  }

  // Render Sidebar Playlists List
  function renderSidebarPlaylists() {
    sidebarPlaylistsEl.innerHTML = '';
    const userPlaylists = window.PlaylistManager.playlists.filter((p) => p.id !== 'favorites');

    userPlaylists.forEach((pl) => {
      const item = document.createElement('a');
      item.className = `nav-item ${currentNavTab === 'playlist' && currentActivePlaylistId === pl.id ? 'active' : ''}`;
      item.innerHTML = `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <line x1="8" y1="6" x2="21" y2="6"></line>
          <line x1="8" y1="12" x2="21" y2="6"></line>
          <line x1="8" y1="18" x2="21" y2="18"></line>
          <line x1="3" y1="6" x2="3.01" y2="6"></line>
          <line x1="3" y1="12" x2="3.01" y2="12"></line>
          <line x1="3" y1="18" x2="3.01" y2="18"></line>
        </svg>
        <span>${escapeHTML(pl.name)}</span>
        <span class="nav-badge">${pl.song_ids.length}</span>
      `;
      item.addEventListener('click', (e) => {
        e.preventDefault();
        currentNavTab = 'playlist';
        currentActivePlaylistId = pl.id;
        navItems.forEach((n) => n.classList.remove('active'));
        renderSidebarPlaylists();
        renderCurrentView();
      });
      sidebarPlaylistsEl.appendChild(item);
    });
  }

  // Render Up Next Queue
  function renderQueue(queueList) {
    queueView.innerHTML = '';
    if (queueList.length === 0) {
      queueView.innerHTML = `
        <div class="lyric-empty-state">
          <p>Antrean lagu kosong</p>
        </div>
      `;
      return;
    }

    let dragSrcIndex = null;

    queueList.forEach((song, i) => {
      const qItem = document.createElement('div');
      qItem.className = 'queue-item';
      qItem.draggable = true;
      qItem.dataset.queueIndex = i;
      const coverSrc = getSafeCoverUrl(song);
      qItem.innerHTML = `
        <span class="queue-drag-handle" title="Tarik untuk mengatur ulang">⣿</span>
        <img class="song-thumbnail" src="${escapeHTML(coverSrc)}" alt="Cover" />
        <div class="song-info-stack" style="flex:1; min-width: 0;">
          <span class="song-cell-title">${escapeHTML(song.title)}</span>
          <span class="song-cell-artist">${escapeHTML(song.artist)}</span>
        </div>
        <span style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--text-tertiary);">#${i + 1}</span>
      `;
      const img = qItem.querySelector('.song-thumbnail');
      if (img) img.onerror = () => { img.src = DEFAULT_COVER; };

      // Drag & Drop events
      qItem.addEventListener('dragstart', (e) => {
        dragSrcIndex = i;
        qItem.classList.add('queue-dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', i);
      });
      qItem.addEventListener('dragend', () => {
        qItem.classList.remove('queue-dragging');
        document.querySelectorAll('.queue-item.queue-drag-over').forEach(el => el.classList.remove('queue-drag-over'));
      });
      qItem.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        qItem.classList.add('queue-drag-over');
      });
      qItem.addEventListener('dragleave', () => {
        qItem.classList.remove('queue-drag-over');
      });
      qItem.addEventListener('drop', (e) => {
        e.preventDefault();
        qItem.classList.remove('queue-drag-over');
        const fromIdx = dragSrcIndex;
        const toIdx = i;
        if (fromIdx !== null && fromIdx !== toIdx) {
          const queue = window.PlaylistManager.queue;
          const [moved] = queue.splice(fromIdx, 1);
          queue.splice(toIdx, 0, moved);
          renderQueue(queue);
          showToast(`Antrean diubah: "${moved.title}"`, '↕️');
        }
      });

      // Click to play
      qItem.addEventListener('click', (e) => {
        if (e.target.closest('.queue-drag-handle')) return;
        window.PlaylistManager.playTrack(song);
      });
      queueView.appendChild(qItem);
    });
  }

  // Handle Track Playback Change
  window.PlaylistManager.onTrackChanged = async (song) => {
    const coverSrc = getSafeCoverUrl(song);
    playerCover.src = coverSrc;
    playerTitle.textContent = song.title;
    playerArtist.textContent = song.artist;

    // Immersive screen
    immersiveCover.src = coverSrc;
    immersiveTitle.textContent = song.title;
    immersiveArtist.textContent = song.artist;

    // Hero update
    setupHeroFeatured(song);

    // Heart Like State
    const isLiked = window.PlaylistManager.isLiked(song.id);
    playerHeartBtn.classList.toggle('liked', isLiked);
    playerHeartBtn.querySelector('svg').setAttribute('fill', isLiked ? 'currentColor' : 'none');

    // Dynamic Ambient Colors
    window.AmbientColor.applyToRoot(coverSrc);

    // Update MediaSession API for Windows / OS overlay & Lockscreen
    if ('mediaSession' in navigator) {
      navigator.mediaSession.metadata = new MediaMetadata({
        title: song.title,
        artist: song.artist,
        album: song.album || 'Aura Music',
        artwork: [
          { src: song.cover || DEFAULT_COVER, sizes: '512x512', type: 'image/png' },
          { src: song.cover || DEFAULT_COVER, sizes: '192x192', type: 'image/png' }
        ]
      });

      try {
        navigator.mediaSession.setActionHandler('play', () => window.AudioCore.play());
        navigator.mediaSession.setActionHandler('pause', () => window.AudioCore.pause());
        navigator.mediaSession.setActionHandler('previoustrack', () => window.PlaylistManager.prev());
        navigator.mediaSession.setActionHandler('nexttrack', () => window.PlaylistManager.next());
        navigator.mediaSession.setActionHandler('seekto', (details) => {
          if (details.seekTime !== undefined) window.AudioCore.seek(details.seekTime);
        });
        navigator.mediaSession.setActionHandler('seekbackward', (details) => {
          const skip = details.seekOffset || 5;
          window.AudioCore.seek(Math.max(window.AudioCore.audio.currentTime - skip, 0));
        });
        navigator.mediaSession.setActionHandler('seekforward', (details) => {
          const skip = details.seekOffset || 5;
          window.AudioCore.seek(Math.min(window.AudioCore.audio.currentTime + skip, window.AudioCore.audio.duration || 0));
        });
      } catch (err) {}
    }

    // Reset lyrics offset badge
    if (syncOffsetVal) syncOffsetVal.textContent = '0.0s';

    // Load Audio Source & Handle Offline Fallback & Crossfade
    let audioSrc = song.url;
    if (window.OfflineDB && (window.OfflineDB.hasOfflineBlob(song.id) || !navigator.onLine)) {
      try {
        const blobUrl = await window.OfflineDB.getTrackBlobUrl(song.id);
        if (blobUrl) audioSrc = blobUrl;
      } catch (e) {
        console.warn('Offline blob fallback error:', e);
      }
    }

    window.AudioCore.loadTrack(audioSrc);

    // Sync Waveform Scrubber with new track
    if (waveformScrubber) {
      waveformScrubber.setTrack(song);
    }

    if (window.AudioCore.crossfadeDuration > 0) {
      const currentTargetVolume = window.AudioCore.getVolume() || 0.8;
      window.AudioCore.setVolume(0);
      await window.AudioCore.play();
      window.AudioCore.fadeVolume(currentTargetVolume, window.AudioCore.crossfadeDuration * 1000);
    } else {
      await window.AudioCore.play();
    }

    // Load Lyrics
    if (song.lyrics) {
      window.LyricsEngine.loadLyrics(song.lyrics);
    } else {
      window.LyricsEngine.loadLyrics(null);
    }

    // Start Visualizer
    if (visualizer) visualizer.start();

    // Instant Active Song Highlight (Zero Lag)
    updateActiveIndicators(song.id);
  };

  window.PlaylistManager.onQueueUpdated = (queue) => {
    renderQueue(queue);
  };

  // Audio Core Callbacks
  window.AudioCore.onPlay = () => {
    playIcon.style.display = 'none';
    pauseIcon.style.display = 'block';
    if (visualizer) visualizer.start();
    if ('mediaSession' in navigator) navigator.mediaSession.playbackState = 'playing';
    requestWakeLock();
    savePlaybackSessionState();
  };

  window.AudioCore.onPause = () => {
    playIcon.style.display = 'block';
    pauseIcon.style.display = 'none';
    if ('mediaSession' in navigator) navigator.mediaSession.playbackState = 'paused';
    releaseWakeLock();
    savePlaybackSessionState();
  };

  window.AudioCore.onError = (errMsg, err) => {
    console.warn('AudioCore reported error:', errMsg, err);
    showToast(`Kendala Audio: ${errMsg}. Mencoba putar ulang / lanjut...`, '⚠️');
    // Auto-heal attempt: skip to next song smoothly after 1.8s
    setTimeout(() => {
      if (window.PlaylistManager.library.length > 1) {
        window.PlaylistManager.next();
      }
    }, 1800);
  };

  window.AudioCore.onTimeUpdate = (currentTime, duration) => {
    if (!isDraggingScrubber) {
      timeCurrent.textContent = formatTime(currentTime);
      timeTotal.textContent = formatTime(duration);
      const percent = duration > 0 ? (currentTime / duration) * 100 : 0;
      progressFill.style.width = `${percent}%`;
      progressThumb.style.left = `${percent}%`;
    }
    if (waveformScrubber) {
      waveformScrubber.setProgress(currentTime, duration);
    }
    window.LyricsEngine.updateTime(currentTime);

    // Sync MediaSession position state for Lockscreen/OS timeline
    if ('mediaSession' in navigator && 'setPositionState' in navigator.mediaSession) {
      if (duration > 0 && currentTime <= duration) {
        try {
          navigator.mediaSession.setPositionState({
            duration: duration,
            playbackRate: window.AudioCore.playbackRate || 1.0,
            position: currentTime
          });
        } catch (e) {}
      }
    }

    // Auto-save session periodically & record listening stats
    const now = Date.now();
    if (now - lastTimeUpdate >= 5000) {
      window.PlaylistManager.recordListeningTime(5);
      savePlaybackSessionState();
      lastTimeUpdate = now;
    }
  };

  window.AudioCore.onEnded = () => {
    if (sleepMode === 'end_of_track') {
      window.AudioCore.pause();
      clearSleepTimer();
      showToast('Sleep Timer: Musik dihentikan di akhir lagu', '🌙');
      return;
    }
    window.PlaylistManager.next();
  };

  // Playback Control Listeners
  playPauseBtn.addEventListener('click', () => {
    if (!window.PlaylistManager.currentSong && window.PlaylistManager.library.length > 0) {
      window.PlaylistManager.playTrack(window.PlaylistManager.library[0]);
    } else {
      window.AudioCore.togglePlay();
    }
  });

  prevBtn.addEventListener('click', () => window.PlaylistManager.prev());
  nextBtn.addEventListener('click', () => window.PlaylistManager.next());

  shuffleBtn.addEventListener('click', () => {
    const isShuff = window.PlaylistManager.toggleShuffle();
    shuffleBtn.classList.toggle('active', isShuff);
    showToast(isShuff ? 'Shuffle Aktif' : 'Shuffle Nonaktif');
  });

  repeatBtn.addEventListener('click', () => {
    const mode = window.PlaylistManager.toggleRepeat();
    repeatBtn.classList.toggle('active', mode !== 'off');
    repeatBtn.title = `Repeat: ${mode}`;
    showToast(`Repeat: ${mode.toUpperCase()}`);
  });

  playerHeartBtn.addEventListener('click', async () => {
    if (!window.PlaylistManager.currentSong) return;
    const liked = await window.PlaylistManager.toggleLike(window.PlaylistManager.currentSong.id);
    playerHeartBtn.classList.toggle('liked', liked);
    playerHeartBtn.querySelector('svg').setAttribute('fill', liked ? 'currentColor' : 'none');
    showToast(liked ? 'Ditambahkan ke Liked Songs' : 'Dihapus dari Liked Songs');
    if (currentNavTab === 'liked') renderCurrentView();
  });

  // Progress Bar Scrubbing
  function seekToPosition(e) {
    const rect = progressBar.getBoundingClientRect();
    const pos = (e.clientX - rect.left) / rect.width;
    const clamped = Math.max(0, Math.min(1, pos));
    const targetTime = clamped * (window.AudioCore.audio.duration || 0);
    window.AudioCore.seek(targetTime);
  }

  progressBar.addEventListener('click', seekToPosition);

  // Acoustic Waveform Scrubber View Switcher
  if (toggleWaveformBtn) {
    toggleWaveformBtn.addEventListener('click', () => {
      isWaveformActive = !isWaveformActive;
      if (isWaveformActive) {
        progressBar.style.display = 'none';
        waveformCanvas.style.display = 'block';
        toggleWaveformBtn.style.color = 'var(--accent-primary)';
        toggleWaveformBtn.style.borderColor = 'var(--accent-primary)';
        toggleWaveformBtn.style.background = 'var(--accent-subtle)';
        if (waveformScrubber) {
          waveformScrubber.resize();
          if (window.PlaylistManager.currentSong) {
            waveformScrubber.setTrack(window.PlaylistManager.currentSong);
            waveformScrubber.setProgress(window.AudioCore.audio.currentTime || 0, window.AudioCore.audio.duration || 0);
          }
        }
        showToast('Waveform Scrubber Akustik Aktif 🌊', '✓');
      } else {
        progressBar.style.display = 'block';
        waveformCanvas.style.display = 'none';
        toggleWaveformBtn.style.color = 'var(--text-secondary)';
        toggleWaveformBtn.style.borderColor = 'var(--border-subtle)';
        toggleWaveformBtn.style.background = 'none';
        showToast('Progress Bar Standar Aktif', '✓');
      }
    });
  }

  // Volume Bar
  volumeBar.addEventListener('click', (e) => {
    const rect = volumeBar.getBoundingClientRect();
    const pos = (e.clientX - rect.left) / rect.width;
    const clamped = Math.max(0, Math.min(1, pos));
    window.AudioCore.setVolume(clamped);
    volumeFill.style.width = `${clamped * 100}%`;
  });

  volumeBtn.addEventListener('click', () => {
    if (window.AudioCore.getVolume() > 0) {
      window.AudioCore.setVolume(0);
      volumeFill.style.width = '0%';
    } else {
      window.AudioCore.setVolume(0.8);
      volumeFill.style.width = '80%';
    }
  });

  // Navigation Tabs
  navItems.forEach((item) => {
    item.addEventListener('click', (e) => {
      e.preventDefault();
      const tab = item.dataset.tab;
      if (!tab) return;
      currentNavTab = tab;
      currentActivePlaylistId = null;
      navItems.forEach((n) => n.classList.remove('active'));
      item.classList.add('active');
      renderSidebarPlaylists();
      renderCurrentView();
    });
  });

  // Search Input Event (Debounced for 60 FPS typing)
  let searchDebounceTimer = null;
  searchInput.addEventListener('input', () => {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
      renderCurrentView();
    }, 80);
  });

  // Sort & Filter Bar
  const filterChips = document.querySelectorAll('.filter-chip');
  const genreFilterSelect = document.getElementById('genre-filter-select');

  filterChips.forEach((chip) => {
    chip.addEventListener('click', () => {
      filterChips.forEach((c) => c.classList.remove('active'));
      chip.classList.add('active');
      currentSortMode = chip.dataset.sort || 'default';
      renderCurrentView();
    });
  });

  if (genreFilterSelect) {
    genreFilterSelect.addEventListener('change', () => {
      currentGenreFilter = genreFilterSelect.value;
      renderCurrentView();
    });
  }

  // View Toggle (Table vs Grid)
  viewToggleBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      viewToggleBtns.forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');
      const mode = btn.dataset.view;
      if (mode === 'grid') {
        tableViewWrap.style.display = 'none';
        songsGrid.style.display = 'grid';
      } else {
        tableViewWrap.style.display = 'table';
        songsGrid.style.display = 'none';
      }
    });
  });

  // Right Panel Tabs
  panelTabBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      panelTabBtns.forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');
      const tab = btn.dataset.tab;

      lyricsView.style.display = tab === 'lyrics' ? 'flex' : 'none';
      visualizerView.style.display = tab === 'visualizer' ? 'flex' : 'none';
      queueView.style.display = tab === 'queue' ? 'flex' : 'none';

      if (tab === 'visualizer' && visualizer) {
        visualizer.initCanvasSize();
      }
    });
  });

  // Panel Collapsible / Mobile Drawer Toggle
  const mobilePanelCloseBtn = document.getElementById('mobile-panel-close-btn');

  panelToggleBtn.addEventListener('click', () => {
    if (window.innerWidth <= 1024) {
      rightPanel.classList.toggle('drawer-open');
      if (rightPanel.classList.contains('drawer-open') && visualizer) {
        setTimeout(() => visualizer.initCanvasSize(), 300);
      }
    } else {
      rightPanel.classList.toggle('collapsed');
      panelToggleBtn.classList.toggle('active', !rightPanel.classList.contains('collapsed'));
      if (!rightPanel.classList.contains('collapsed') && visualizer) {
        setTimeout(() => visualizer.initCanvasSize(), 300);
      }
    }
  });

  if (mobilePanelCloseBtn) {
    mobilePanelCloseBtn.addEventListener('click', () => {
      rightPanel.classList.remove('drawer-open');
    });
  }

  // On Mobile: Tap player-left card to open Immersive screen
  const playerLeftEl = document.querySelector('.player-left');
  if (playerLeftEl) {
    playerLeftEl.addEventListener('click', (e) => {
      if (window.innerWidth <= 768 && !e.target.closest('button')) {
        immersiveOverlay.classList.add('open');
      }
    });
  }

  // Visualizer Mode Chips
  visModeChips.forEach((chip) => {
    chip.addEventListener('click', () => {
      visModeChips.forEach((c) => c.classList.remove('active'));
      chip.classList.add('active');
      if (visualizer) visualizer.setMode(chip.dataset.mode);
    });
  });

  // Lyrics Timing Sync Adjuster
  if (syncMinusBtn) {
    syncMinusBtn.addEventListener('click', () => {
      const offset = window.LyricsEngine.adjustOffset(-0.5);
      syncOffsetVal.textContent = (offset > 0 ? '+' : '') + offset.toFixed(1) + 's';
      showToast(`Offset Lirik: ${syncOffsetVal.textContent}`, '⏱️');
    });
  }
  if (syncPlusBtn) {
    syncPlusBtn.addEventListener('click', () => {
      const offset = window.LyricsEngine.adjustOffset(0.5);
      syncOffsetVal.textContent = (offset > 0 ? '+' : '') + offset.toFixed(1) + 's';
      showToast(`Offset Lirik: ${syncOffsetVal.textContent}`, '⏱️');
    });
  }
  if (syncResetBtn) {
    syncResetBtn.addEventListener('click', () => {
      window.LyricsEngine.resetOffset();
      syncOffsetVal.textContent = '0.0s';
      showToast('Offset Lirik di-reset ke 0.0s', '✓');
    });
  }

  // Auto Lyrics Online Search Handler (LRCLIB API)
  window.fetchOnlineLyricsForCurrentSong = async function() {
    const song = window.PlaylistManager.currentSong;
    if (!song) {
      showToast('Pilih lagu terlebih dahulu', '⚠️');
      return;
    }

    const btn = document.getElementById('fetch-online-lyrics-btn');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `<span>Mencari lirik online...</span>`;
    }

    showToast(`Mencari lirik online untuk "${song.title}"...`, '🔍');

    try {
      const res = await fetch('api/lyrics_search.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          title: song.title,
          artist: song.artist,
          filename: song.filename || ''
        })
      });

      const data = await res.json();
      if (data.status === 'success' && data.lyrics) {
        song.lyrics = data.lyrics;
        window.LyricsEngine.loadLyrics(data.lyrics);
        showToast(data.message || 'Lirik sinkron berhasil ditemukan!', '✓');
      } else {
        showToast(data.message || 'Lirik tidak ditemukan di database online', 'ℹ️');
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = `
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <span>Coba Cari Lagi</span>
          `;
        }
      }
    } catch (err) {
      console.error('Error fetching online lyrics:', err);
      showToast('Gagal menghubungi server lirik', '⚠️');
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = `<span>Cari Lirik Online</span>`;
      }
    }
  };

  // ==========================================
  // SLEEP TIMER SYSTEM
  // ==========================================
  function clearSleepTimer() {
    if (sleepTimerInterval) {
      clearInterval(sleepTimerInterval);
      sleepTimerInterval = null;
    }
    sleepMode = 'off';
    sleepTargetSeconds = 0;
    if (sleepBadge) sleepBadge.style.display = 'none';
    sleepOptBtns.forEach((btn) => {
      btn.classList.toggle('active', btn.dataset.minutes === '0');
    });
  }

  function startSleepCountdown(minutes) {
    clearSleepTimer();
    sleepMode = 'countdown';
    sleepTargetSeconds = minutes * 60;
    if (sleepBadge) {
      sleepBadge.style.display = 'block';
      sleepBadge.textContent = `${minutes}m`;
    }

    sleepTimerInterval = setInterval(async () => {
      sleepTargetSeconds--;
      if (sleepTargetSeconds <= 0) {
        clearInterval(sleepTimerInterval);
        sleepTimerInterval = null;
        if (sleepBadge) sleepBadge.style.display = 'none';
        
        // Soft volume fade-out before pause
        const curVol = window.AudioCore.getVolume();
        await window.AudioCore.fadeVolume(0, 4000);
        window.AudioCore.pause();
        window.AudioCore.setVolume(curVol);
        clearSleepTimer();
        showToast('Sleep Timer habis: Musik dijeda otomatis', '🌙');
      } else {
        const minsLeft = Math.ceil(sleepTargetSeconds / 60);
        if (sleepBadge) sleepBadge.textContent = minsLeft > 1 ? `${minsLeft}m` : `${sleepTargetSeconds}s`;
      }
    }, 1000);
  }

  sleepTimerBtn.addEventListener('click', () => {
    sleepModal.classList.add('open');
  });

  sleepCloseBtn.addEventListener('click', () => {
    sleepModal.classList.remove('open');
  });

  sleepOptBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      sleepOptBtns.forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');
      const val = btn.dataset.minutes;
      if (val === '0') {
        clearSleepTimer();
        showToast('Sleep Timer dinonaktifkan', 'ℹ️');
      } else if (val === 'end_of_track') {
        clearSleepTimer();
        sleepMode = 'end_of_track';
        if (sleepBadge) {
          sleepBadge.style.display = 'block';
          sleepBadge.textContent = '1 Lagu';
        }
        showToast('Sleep Timer aktif: Berhenti di akhir lagu ini', '🌙');
      } else {
        const mins = parseInt(val, 10);
        startSleepCountdown(mins);
        showToast(`Sleep Timer aktif: ${mins} Menit`, '🌙');
      }
      sleepModal.classList.remove('open');
    });
  });

  // ==========================================
  // PICTURE-IN-PICTURE (PiP) FLOATING MINI PLAYER
  // ==========================================
  const pipCanvas = document.getElementById('pip-canvas');
  const pipVideo = document.getElementById('pip-video');
  const pipCtx = pipCanvas ? pipCanvas.getContext('2d') : null;
  const pipImg = new Image();

  function drawPiPFrame() {
    if (!pipCtx) return;
    const w = pipCanvas.width;
    const h = pipCanvas.height;

    // Background gradient
    const grad = pipCtx.createLinearGradient(0, 0, w, h);
    grad.addColorStop(0, '#0c0e15');
    grad.addColorStop(1, '#050608');
    pipCtx.fillStyle = grad;
    pipCtx.fillRect(0, 0, w, h);

    // Dynamic glowing ambient circle
    pipCtx.save();
    pipCtx.beginPath();
    pipCtx.arc(w / 2, h / 2, 140, 0, Math.PI * 2);
    pipCtx.fillStyle = 'rgba(59, 130, 246, 0.12)';
    pipCtx.filter = 'blur(40px)';
    pipCtx.fill();
    pipCtx.restore();

    // Album Artwork
    const currentSong = window.PlaylistManager.currentSong;
    if (currentSong && currentSong.cover) {
      if (pipImg.src !== currentSong.cover) {
        pipImg.src = currentSong.cover;
      }
      if (pipImg.complete && pipImg.naturalWidth > 0) {
        pipCtx.save();
        pipCtx.beginPath();
        pipCtx.roundRect(30, 35, 110, 110, 12);
        pipCtx.clip();
        pipCtx.drawImage(pipImg, 30, 35, 110, 110);
        pipCtx.restore();
      }
    }

    // Title & Artist
    pipCtx.fillStyle = '#ffffff';
    pipCtx.font = 'bold 22px "Plus Jakarta Sans", sans-serif';
    pipCtx.fillText((currentSong ? currentSong.title : 'Aura Music').substring(0, 24), 160, 75);

    pipCtx.fillStyle = '#9ca3af';
    pipCtx.font = '500 15px "Plus Jakarta Sans", sans-serif';
    pipCtx.fillText((currentSong ? currentSong.artist : 'Personal Player').substring(0, 28), 160, 105);

    // Live Audio Frequency Spectrum Wave
    const freqData = window.AudioCore.getFrequencyData();
    const barCount = 36;
    const barWidth = 6;
    const barGap = 4;
    const startX = 160;
    const baseY = 145;

    for (let i = 0; i < barCount; i++) {
      const val = freqData[i * 2] || 0;
      const barH = Math.max(3, (val / 255) * 32);
      const x = startX + i * (barWidth + barGap);
      if (x < w - 20) {
        pipCtx.fillStyle = i % 2 === 0 ? '#3b82f6' : '#60a5fa';
        pipCtx.beginPath();
        pipCtx.roundRect(x, baseY - barH, barWidth, barH, 2);
        pipCtx.fill();
      }
    }

    // Live Current Synchronized Lyric Line
    const curLyric = window.LyricsEngine.getCurrentLyricText();
    if (curLyric) {
      pipCtx.fillStyle = 'rgba(255, 255, 255, 0.08)';
      pipCtx.beginPath();
      pipCtx.roundRect(24, 185, w - 48, 120, 12);
      pipCtx.fill();

      pipCtx.fillStyle = '#f3f4f6';
      pipCtx.font = '600 17px "Plus Jakarta Sans", sans-serif';
      pipCtx.textAlign = 'center';
      pipCtx.fillText(`"${curLyric.substring(0, 48)}"`, w / 2, 252);
      pipCtx.textAlign = 'left';
    }

    pipAnimationId = requestAnimationFrame(drawPiPFrame);
  }

  if (pipBtn) {
    pipBtn.addEventListener('click', async () => {
      try {
        if (document.pictureInPictureElement) {
          await document.exitPictureInPicture();
          return;
        }

        drawPiPFrame();
        const stream = pipCanvas.captureStream(25);
        pipVideo.srcObject = stream;
        await pipVideo.play();
        await pipVideo.requestPictureInPicture();
        showToast('Floating Mini-Player aktif!', '🖼️');
      } catch (err) {
        console.warn('PiP not supported or cancelled:', err);
        showToast('Fitur Picture-in-Picture tidak didukung di browser ini', '⚠️');
      }
    });
  }

  // ==========================================
  // EQUALIZER & DSP FX CONTROLLER
  // ==========================================
  eqBtn.addEventListener('click', () => eqModal.classList.add('open'));
  eqCloseBtn.addEventListener('click', () => eqModal.classList.remove('open'));

  // Speed Slider
  if (dspSpeedSlider) {
    dspSpeedSlider.addEventListener('input', () => {
      const val = parseFloat(dspSpeedSlider.value);
      dspSpeedVal.textContent = val.toFixed(2) + 'x';
      window.AudioCore.setPlaybackRate(val);
    });
  }

  // Crossfade Slider
  if (dspCrossfadeSlider) {
    dspCrossfadeSlider.addEventListener('input', () => {
      const val = parseInt(dspCrossfadeSlider.value, 10);
      dspCrossfadeVal.textContent = val > 0 ? `${val} detik` : '0 detik (Off)';
      window.AudioCore.crossfadeDuration = val;
    });
  }

  // Preamp Booster Slider (100% to 300%)
  const dspPreampSlider = document.getElementById('dsp-preamp-slider');
  const dspPreampVal = document.getElementById('dsp-preamp-val');
  if (dspPreampSlider) {
    dspPreampSlider.addEventListener('input', () => {
      const val = parseFloat(dspPreampSlider.value);
      const percent = Math.round(val * 100);
      dspPreampVal.textContent = `${percent}% ${val > 1.0 ? '🔥' : '(Normal)'}`;
      window.AudioCore.setPreamp(val);
    });
  }

  // 8D Spatial Audio Speed Slider
  const dsp8dSpeedSlider = document.getElementById('dsp-8d-speed-slider');
  const dsp8dSpeedVal = document.getElementById('dsp-8d-speed-val');
  if (dsp8dSpeedSlider) {
    dsp8dSpeedSlider.addEventListener('input', () => {
      const val = parseFloat(dsp8dSpeedSlider.value);
      if (dsp8dSpeedVal) dsp8dSpeedVal.textContent = val.toFixed(1) + 'x';
      if (window.AudioCore.is8DActive) {
        window.AudioCore.set8DAudio(true, val);
      }
    });
  }

  // Ambient White Noise Layer Mixer
  const ambientChips = document.querySelectorAll('.ambient-chip');
  const ambientVolSlider = document.getElementById('ambient-vol-slider');
  const ambientVolVal = document.getElementById('ambient-vol-val');
  let currentAmbientType = 'off';

  ambientChips.forEach((chip) => {
    chip.addEventListener('click', () => {
      ambientChips.forEach((c) => c.classList.remove('active'));
      chip.classList.add('active');
      currentAmbientType = chip.dataset.ambient || 'off';
      const vol = parseFloat(ambientVolSlider ? ambientVolSlider.value : 0.4);
      window.AudioCore.setAmbientSound(currentAmbientType, vol);
      showToast(currentAmbientType !== 'off' ? `Suara Latar ${chip.textContent.trim()} Dimulai` : 'Suara Latar Dimatikan', '🌧️');
    });
  });

  if (ambientVolSlider) {
    ambientVolSlider.addEventListener('input', () => {
      const vol = parseFloat(ambientVolSlider.value);
      if (ambientVolVal) ambientVolVal.textContent = Math.round(vol * 100) + '%';
      window.AudioCore.setAmbientSound(currentAmbientType, vol);
    });
  }

  // DSP FX Modes (Clean, 8D Spatial, Slowed+Reverb, Nightcore, Vaporwave, Bass Master, Karaoke, Haptic)
  dspModeChips.forEach((chip) => {
    chip.addEventListener('click', () => {
      dspModeChips.forEach((c) => c.classList.remove('active'));
      chip.classList.add('active');
      const mode = chip.dataset.mode;

      // Reset specific modes first
      window.AudioCore.setVocalRemover(false);
      window.AudioCore.setHapticBass(false);
      window.AudioCore.set8DAudio(false);

      if (mode === '8d') {
        // 8D Spatial Audio 360 rotation
        const speed = parseFloat(dsp8dSpeedSlider ? dsp8dSpeedSlider.value : 1.0);
        window.AudioCore.set8DAudio(true, speed);
        showToast('8D Spatial Audio 360° Aktif 🎧', '✨');
      } else if (mode === 'slowed') {
        // Slowed + Reverb
        dspSpeedSlider.value = 0.85;
        dspSpeedVal.textContent = '0.85x';
        window.AudioCore.setPlaybackRate(0.85);
        window.AudioCore.setReverb(true, 0.65);
        window.AudioCore.setBassBoost(6);
        window.AudioCore.applyPreset('flat');
        showToast('Mode Slowed + Reverb Aktif 🌌', '✨');
      } else if (mode === 'nightcore') {
        // Nightcore
        dspSpeedSlider.value = 1.25;
        dspSpeedVal.textContent = '1.25x';
        window.AudioCore.setPlaybackRate(1.25);
        window.AudioCore.setReverb(false);
        window.AudioCore.setTrebleBoost(5);
        window.AudioCore.applyPreset('electronic');
        showToast('Mode Nightcore Aktif ⚡', '✨');
      } else if (mode === 'vaporwave') {
        // Vaporwave
        dspSpeedSlider.value = 0.78;
        dspSpeedVal.textContent = '0.78x';
        window.AudioCore.setPlaybackRate(0.78);
        window.AudioCore.setReverb(true, 0.45);
        window.AudioCore.setTrebleBoost(-4);
        window.AudioCore.applyPreset('flat');
        showToast('Mode Vaporwave Nostalgia Aktif 📼', '✨');
      } else if (mode === 'bassmaster') {
        // Bass Master 808
        dspSpeedSlider.value = 1.0;
        dspSpeedVal.textContent = '1.0x';
        window.AudioCore.setPlaybackRate(1.0);
        window.AudioCore.setReverb(false);
        window.AudioCore.setBassBoost(14);
        window.AudioCore.applyPreset('bass_boost');
        showToast('Mode Bass Master 808 Aktif 🔊', '✨');
      } else if (mode === 'karaoke') {
        // Karaoke Mode (Center-channel vocal cancel)
        window.AudioCore.setVocalRemover(true);
        window.AudioCore.setReverb(false);
        showToast('Karaoke Mode (Peredam Vokal) Aktif 🎤', '✨');
      } else if (mode === 'haptic') {
        // Haptic Bass Vibration
        window.AudioCore.setHapticBass(true);
        window.AudioCore.setBassBoost(8);
        showToast('Haptic Bass Vibration Aktif (HP Bergetar) 📳', '✨');
      } else {
        // Clean
        dspSpeedSlider.value = 1.0;
        dspSpeedVal.textContent = '1.0x';
        window.AudioCore.setPlaybackRate(1.0);
        window.AudioCore.setReverb(false);
        window.AudioCore.setBassBoost(0);
        window.AudioCore.setTrebleBoost(0);
        window.AudioCore.applyPreset('flat');
        showToast('Mode Studio Clean Aktif', '✓');
      }
    });
  });

  // EQ Preset Selector
  eqPresetChips.forEach((chip) => {
    chip.addEventListener('click', () => {
      eqPresetChips.forEach((c) => c.classList.remove('active'));
      chip.classList.add('active');
      const presetName = chip.dataset.preset;
      window.AudioCore.applyPreset(presetName);

      const gains = window.AudioCore.presets[presetName];
      if (gains) {
        gains.forEach((g, i) => {
          const slider = document.getElementById(`eq-slider-${i}`);
          const label = document.getElementById(`eq-val-${i}`);
          if (slider) slider.value = g;
          if (label) label.textContent = (g > 0 ? '+' : '') + g + 'dB';
        });
      }
    });
  });

  // 10-Band EQ Sliders
  for (let i = 0; i < 10; i++) {
    const slider = document.getElementById(`eq-slider-${i}`);
    const label = document.getElementById(`eq-val-${i}`);
    if (slider) {
      slider.addEventListener('input', () => {
        const val = parseFloat(slider.value);
        if (label) label.textContent = (val > 0 ? '+' : '') + val + 'dB';
        window.AudioCore.setEQBandGain(i, val);
      });
    }
  }

  // Bass & Treble Sliders
  const bassSlider = document.getElementById('bass-boost-slider');
  const bassVal = document.getElementById('bass-boost-val');
  if (bassSlider) {
    bassSlider.addEventListener('input', () => {
      const val = parseFloat(bassSlider.value);
      if (bassVal) bassVal.textContent = '+' + val + 'dB';
      window.AudioCore.setBassBoost(val);
    });
  }

  const trebleSlider = document.getElementById('treble-boost-slider');
  const trebleVal = document.getElementById('treble-boost-val');
  if (trebleSlider) {
    trebleSlider.addEventListener('input', () => {
      const val = parseFloat(trebleSlider.value);
      if (trebleVal) trebleVal.textContent = (val > 0 ? '+' : '') + val + 'dB';
      window.AudioCore.setTrebleBoost(val);
    });
  }

  // Volume Normalizer (Dynamics Compressor) Toggle
  const toggleNormalizerBtn = document.getElementById('toggle-normalizer-btn');
  const normalizerStatusVal = document.getElementById('normalizer-status-val');
  if (toggleNormalizerBtn) {
    toggleNormalizerBtn.addEventListener('click', () => {
      const nextState = !window.AudioCore.isNormalizerActive;
      window.AudioCore.setNormalizer(nextState);
      if (normalizerStatusVal) {
        normalizerStatusVal.textContent = nextState ? 'ON' : 'OFF';
        normalizerStatusVal.style.color = nextState ? '#10b981' : 'var(--text-tertiary)';
      }
      toggleNormalizerBtn.textContent = nextState ? 'Auto Leveling Aktif' : 'Auto Leveling Mati';
      toggleNormalizerBtn.style.background = nextState ? 'rgba(16, 185, 129, 0.15)' : 'var(--bg-surface)';
      toggleNormalizerBtn.style.borderColor = nextState ? '#10b981' : 'var(--border-subtle)';
      toggleNormalizerBtn.style.color = nextState ? '#10b981' : 'var(--text-secondary)';
      showToast(nextState ? 'Volume Leveling (Normalizer) Aktif' : 'Volume Leveling Nonaktif', nextState ? '🔊' : 'ℹ️');
    });
  }

  // Immersive Fullscreen Mode & Dynamic Spotify Canvas Background
  const immersiveCanvas = document.getElementById('immersive-canvas');
  let immersiveVisualizer = null;
  if (immersiveCanvas && window.ImmersiveCanvasVisualizer) {
    immersiveVisualizer = new window.ImmersiveCanvasVisualizer(immersiveCanvas);
  }

  immersiveBtn.addEventListener('click', () => {
    immersiveOverlay.classList.add('open');
    if (immersiveVisualizer) immersiveVisualizer.start();
  });
  immersiveCloseBtn.addEventListener('click', () => {
    immersiveOverlay.classList.remove('open');
    if (immersiveVisualizer) immersiveVisualizer.stop();
  });

  // Upload & Downloader Handlers
  const openUploadModal = () => uploadModal.classList.add('open');
  uploadTriggerBtn.addEventListener('click', openUploadModal);
  
  const headerUploadBtn = document.getElementById('header-upload-btn');
  if (headerUploadBtn) headerUploadBtn.addEventListener('click', openUploadModal);
  
  const heroUploadBtn = document.getElementById('hero-upload-btn');
  if (heroUploadBtn) heroUploadBtn.addEventListener('click', openUploadModal);
  
  const sidebarNavUpload = document.getElementById('sidebar-nav-upload');
  if (sidebarNavUpload) {
    sidebarNavUpload.addEventListener('click', (e) => {
      e.preventDefault();
      openUploadModal();
    });
  }

  // Rescan Handlers
  const handleRescan = async () => {
    showToast('Memindai folder musik...', '🔄');
    await fetchLibrary(true);
    showToast('Koleksi musik diperbarui!', '✓');
  };

  const refreshScanBtn = document.getElementById('refresh-scan-btn');
  if (refreshScanBtn) refreshScanBtn.addEventListener('click', handleRescan);

  const quickRescanBtn = document.getElementById('quick-rescan-btn');
  if (quickRescanBtn) quickRescanBtn.addEventListener('click', handleRescan);

  const modalRescanBtn = document.getElementById('modal-rescan-btn');
  if (modalRescanBtn) {
    modalRescanBtn.addEventListener('click', async () => {
      uploadModal.classList.remove('open');
      await handleRescan();
    });
  }

  // Modal Tab Switching
  const modalTabBtns = document.querySelectorAll('.modal-tab-btn');
  modalTabBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      modalTabBtns.forEach((b) => {
        b.classList.remove('active');
        b.style.background = 'var(--bg-surface)';
        b.style.borderColor = 'var(--border-subtle)';
        b.style.color = 'var(--text-secondary)';
      });
      btn.classList.add('active');
      btn.style.background = 'var(--accent-subtle)';
      btn.style.borderColor = 'var(--accent-primary)';
      btn.style.color = 'var(--accent-primary)';

      const target = btn.dataset.target;
      document.querySelectorAll('.modal-tab-panel').forEach((p) => p.style.display = 'none');
      const targetPanel = document.getElementById(target);
      if (targetPanel) targetPanel.style.display = 'block';
    });
  });

  // YouTube / Spotify Downloader & Batch Playlist Importer
  const ytUrlInput = document.getElementById('yt-url-input');
  const ytDownloadBtn = document.getElementById('yt-start-download-btn');
  const ytStatusBox = document.getElementById('yt-status-box');
  const ytStatusText = document.getElementById('yt-status-text');

  // Batch Playlist Elements
  const batchPlaylistContainer = document.getElementById('batch-playlist-container');
  const batchPlaylistCover = document.getElementById('batch-playlist-cover');
  const batchPlatformBadge = document.getElementById('batch-platform-badge');
  const batchTotalCount = document.getElementById('batch-total-count');
  const batchPlaylistTitle = document.getElementById('batch-playlist-title');
  const batchAutoPlaylist = document.getElementById('batch-auto-playlist');
  const batchProgressWrap = document.getElementById('batch-progress-wrap');
  const batchProgressStatus = document.getElementById('batch-progress-status');
  const batchProgressPercent = document.getElementById('batch-progress-percent');
  const batchProgressBarFill = document.getElementById('batch-progress-bar-fill');
  const batchTracklist = document.getElementById('batch-tracklist');
  const batchCancelBtn = document.getElementById('batch-cancel-btn');
  const batchStartAllBtn = document.getElementById('batch-start-all-btn');

  let currentBatchData = null;
  let isBatchDownloading = false;

  function isPlaylistUrl(url) {
    if (!url) return false;
    const u = url.toLowerCase();
    return u.includes('spotify.com/playlist') || 
           u.includes('spotify.com/album') || 
           u.includes('youtube.com/playlist') || 
           (u.includes('youtube.com/') && u.includes('list='));
  }

  function resetBatchUI() {
    currentBatchData = null;
    isBatchDownloading = false;
    if (batchPlaylistContainer) batchPlaylistContainer.style.display = 'none';
    if (batchProgressWrap) batchProgressWrap.style.display = 'none';
    if (batchProgressBarFill) batchProgressBarFill.style.width = '0%';
    if (batchTracklist) batchTracklist.innerHTML = '';
    if (batchStartAllBtn) {
      batchStartAllBtn.disabled = false;
      batchStartAllBtn.innerHTML = `
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
          <polyline points="7 10 12 15 17 10"></polyline>
          <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
        <span>Download Semua Lagu</span>
      `;
    }
  }

  async function handleYouTubeDownload() {
    const query = (ytUrlInput.value || '').trim();
    if (!query) {
      showToast('Masukkan link Spotify Playlist atau YouTube', '⚠️');
      return;
    }

    // Check if link is a Spotify or YouTube Playlist
    if (isPlaylistUrl(query)) {
      await handleFetchPlaylist(query);
      return;
    }

    // Otherwise standard single track download
    ytDownloadBtn.disabled = true;
    ytDownloadBtn.style.opacity = '0.6';
    ytStatusBox.style.display = 'block';
    ytStatusText.textContent = 'Memproses dan mengunduh audio kualitas 320kbps MP3 + cover...';
    resetBatchUI();

    try {
      const res = await auraFetch('api/yt_download.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'download', query: query })
      });

      const data = await res.json();
      if (data.status === 'success' || data.status === 'already_exists') {
        const icon = data.status === 'already_exists' ? 'ℹ️' : '✓';
        showToast(data.message, icon);
        ytUrlInput.value = '';
        ytStatusBox.style.display = 'none';
        uploadModal.classList.remove('open');
        
        await fetchLibrary(true);
        if (data.song) {
          const songToPlay = window.PlaylistManager.library.find((s) => s.filename === data.song.filename) || data.song;
          window.PlaylistManager.playTrack(songToPlay);
        }
      } else {
        ytStatusText.textContent = 'Gagal: ' + (data.message || 'Terjadi kesalahan');
        showToast(data.message || 'Gagal mendownload audio', '⚠️');
      }
    } catch (e) {
      ytStatusText.textContent = 'Terjadi kesalahan koneksi saat mengunduh.';
      showToast('Kesalahan koneksi ke server', '⚠️');
    } finally {
      ytDownloadBtn.disabled = false;
      ytDownloadBtn.style.opacity = '1';
    }
  }

  // Fetch playlist preview metadata
  async function handleFetchPlaylist(url) {
    ytDownloadBtn.disabled = true;
    ytDownloadBtn.style.opacity = '0.6';
    ytStatusBox.style.display = 'block';
    ytStatusText.textContent = 'Sedang membaca daftar lagu dari playlist...';
    resetBatchUI();

    try {
      const res = await fetch('api/playlist_info.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url: url })
      });

      const data = await res.json();
      if (data.status === 'success' && data.tracks && data.tracks.length > 0) {
        ytStatusBox.style.display = 'none';
        renderBatchPreview(data);
      } else {
        ytStatusText.textContent = 'Gagal: ' + (data.message || 'Tidak dapat membaca isi playlist.');
        showToast(data.message || 'Gagal membaca playlist', '⚠️');
      }
    } catch (e) {
      ytStatusText.textContent = 'Gagal terhubung ke server saat membaca playlist.';
      showToast('Gagal memproses link playlist', '⚠️');
    } finally {
      ytDownloadBtn.disabled = false;
      ytDownloadBtn.style.opacity = '1';
    }
  }

  // Render Playlist Preview UI
  function renderBatchPreview(data) {
    currentBatchData = data;
    batchPlaylistTitle.textContent = data.playlist_name || 'Playlist';
    batchTotalCount.textContent = `${data.total_tracks} Lagu`;
    
    if (data.cover) {
      batchPlaylistCover.src = data.cover;
      batchPlaylistCover.style.display = 'block';
    } else {
      batchPlaylistCover.src = 'assets/sample_covers/placeholder.svg';
    }

    if (data.platform === 'spotify') {
      batchPlatformBadge.textContent = 'Spotify';
      batchPlatformBadge.style.background = '#1db954';
      batchPlatformBadge.style.color = '#000';
    } else {
      batchPlatformBadge.textContent = 'YouTube';
      batchPlatformBadge.style.background = '#ff0000';
      batchPlatformBadge.style.color = '#fff';
    }

    // Render Track items in queue preview
    batchTracklist.innerHTML = '';
    data.tracks.forEach((t, i) => {
      const div = document.createElement('div');
      div.className = 'batch-track-item';
      div.id = `batch-item-${i}`;
      div.innerHTML = `
        <span style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-tertiary); width: 20px;">${i + 1}</span>
        <div class="batch-track-info">
          <span class="batch-track-title">${escapeHTML(t.title)}</span>
          <span class="batch-track-artist">${escapeHTML(t.artist || 'Unknown Artist')}</span>
        </div>
        <span class="batch-track-badge waiting" id="batch-badge-${i}">⏳ Antre</span>
      `;
      batchTracklist.appendChild(div);
    });

    batchPlaylistContainer.style.display = 'block';
  }

  // Start Batch Sequential Queue Download
  async function startBatchDownload() {
    if (!currentBatchData || !currentBatchData.tracks || isBatchDownloading) return;

    isBatchDownloading = true;
    batchStartAllBtn.disabled = true;
    batchStartAllBtn.innerHTML = `<span>Mengunduh...</span>`;
    batchProgressWrap.style.display = 'block';

    const tracks = currentBatchData.tracks;
    const total = tracks.length;
    let completedCount = 0;
    let downloadedSongIds = [];

    for (let i = 0; i < total; i++) {
      if (!isBatchDownloading) break; // User cancelled

      const track = tracks[i];
      const itemEl = document.getElementById(`batch-item-${i}`);
      const badgeEl = document.getElementById(`batch-badge-${i}`);

      // Highlight current item
      if (itemEl) {
        itemEl.classList.add('active');
        itemEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
      if (badgeEl) {
        badgeEl.className = 'batch-track-badge downloading';
        badgeEl.textContent = '⏬ Mengunduh...';
      }

      // Update total progress
      const percent = Math.round(((i) / total) * 100);
      batchProgressPercent.textContent = `${percent}%`;
      batchProgressBarFill.style.width = `${percent}%`;
      batchProgressStatus.textContent = `Mengunduh (${i + 1}/${total}): ${track.title}`;

      try {
        const res = await auraFetch('api/yt_download.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'download', query: track.query })
        });
        const result = await res.json();

        if (result.status === 'success') {
          if (badgeEl) {
            badgeEl.className = 'batch-track-badge success';
            badgeEl.textContent = '✓ Selesai';
          }
          if (result.song && result.song.id) {
            downloadedSongIds.push(result.song.id);
          }
          completedCount++;
        } else if (result.status === 'already_exists') {
          if (badgeEl) {
            badgeEl.className = 'batch-track-badge exists';
            badgeEl.textContent = '⚠️ Ada di Koleksi';
          }
          if (result.song && result.song.id) {
            downloadedSongIds.push(result.song.id);
          }
          completedCount++;
        } else {
          if (badgeEl) {
            badgeEl.className = 'batch-track-badge error';
            badgeEl.textContent = '❌ Gagal';
          }
        }
      } catch (err) {
        if (badgeEl) {
          badgeEl.className = 'batch-track-badge error';
          badgeEl.textContent = '❌ Error';
        }
      } finally {
        if (itemEl) {
          itemEl.classList.remove('active');
          itemEl.classList.add('done');
        }
      }
    }

    // Finish Batch
    batchProgressBarFill.style.width = '100%';
    batchProgressPercent.textContent = '100%';
    batchProgressStatus.textContent = `Selesai! Berhasil memproses ${completedCount} dari ${total} lagu.`;
    
    // Refresh Master Library Cache
    await fetchLibrary(true);

    // Auto Create Playlist if checked
    if (batchAutoPlaylist && batchAutoPlaylist.checked && downloadedSongIds.length > 0) {
      const plName = currentBatchData.playlist_name || 'Imported Playlist';
      try {
        const newPl = await window.PlaylistManager.createPlaylist(plName, `Di-import dari ${currentBatchData.platform === 'spotify' ? 'Spotify' : 'YouTube'}`);
        for (const sid of downloadedSongIds) {
          await window.PlaylistManager.addSongToPlaylist(newPl.id, sid);
        }
        renderSidebarPlaylists();
        showToast(`Playlist "${plName}" berhasil dibuat (${downloadedSongIds.length} lagu)!`, '🎉');
      } catch (err) {
        console.warn('Failed to auto-create playlist:', err);
      }
    } else {
      showToast(`Berhasil mengunduh ${completedCount} lagu ke koleksi!`, '✓');
    }

    isBatchDownloading = false;
    batchStartAllBtn.disabled = false;
    batchStartAllBtn.innerHTML = `<span>✓ Selesai Di-Import</span>`;
    
    setTimeout(() => {
      uploadModal.classList.remove('open');
      resetBatchUI();
      ytUrlInput.value = '';
    }, 2000);
  }

  if (batchStartAllBtn) batchStartAllBtn.addEventListener('click', startBatchDownload);
  if (batchCancelBtn) {
    batchCancelBtn.addEventListener('click', () => {
      isBatchDownloading = false;
      resetBatchUI();
    });
  }

  if (ytDownloadBtn) ytDownloadBtn.addEventListener('click', handleYouTubeDownload);
  if (ytUrlInput) {
    ytUrlInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        handleYouTubeDownload();
      }
    });
    // Detect paste on URL input
    ytUrlInput.addEventListener('paste', (e) => {
      setTimeout(() => {
        const val = (ytUrlInput.value || '').trim();
        if (isPlaylistUrl(val)) {
          handleFetchPlaylist(val);
        }
      }, 100);
    });
  }

  uploadCloseBtn.addEventListener('click', () => {
    uploadModal.classList.remove('open');
    if (ytStatusBox) ytStatusBox.style.display = 'none';
  });

  dropzoneBox.addEventListener('click', () => audioFileInput.click());
  dropzoneBox.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzoneBox.classList.add('drag-over');
  });
  dropzoneBox.addEventListener('dragleave', () => dropzoneBox.classList.remove('drag-over'));
  dropzoneBox.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzoneBox.classList.remove('drag-over');
    if (e.dataTransfer.files.length > 0) {
      audioFileInput.files = e.dataTransfer.files;
      dropzoneBox.querySelector('p').textContent = `${e.dataTransfer.files.length} file dipilih`;
    }
  });

  audioFileInput.addEventListener('change', () => {
    if (audioFileInput.files.length > 0) {
      dropzoneBox.querySelector('p').textContent = `${audioFileInput.files.length} file dipilih`;
    }
  });

  submitUploadBtn.addEventListener('click', async () => {
    if (!audioFileInput.files.length) {
      showToast('Pilih file audio terlebih dahulu', '⚠️');
      return;
    }

    const formData = new FormData();
    for (let i = 0; i < audioFileInput.files.length; i++) {
      formData.append('audio[]', audioFileInput.files[i]);
    }
    if (lyricsFileInput.files.length > 0) {
      formData.append('lyrics', lyricsFileInput.files[0]);
    }

    submitUploadBtn.disabled = true;
    submitUploadBtn.textContent = 'Mengunggah...';

    try {
      const res = await auraFetch('api/upload.php', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (data.status === 'success') {
        showToast(data.message, '✓');
        uploadModal.classList.remove('open');
        audioFileInput.value = '';
        lyricsFileInput.value = '';
        dropzoneBox.querySelector('p').textContent = 'Klik atau drag & drop file MP3, FLAC, WAV ke sini';
        await fetchLibrary(true);
      } else {
        showToast(data.message || 'Gagal mengunggah file', '⚠️');
      }
    } catch (e) {
      showToast('Terjadi kesalahan koneksi saat upload', '⚠️');
    } finally {
      submitUploadBtn.disabled = false;
      submitUploadBtn.textContent = 'Mulai Upload';
    }
  });

  // Create Playlist Modal
  createPlaylistBtn.addEventListener('click', () => {
    newPlaylistModal.classList.add('open');
    playlistNameInput.focus();
  });

  newPlaylistCloseBtn.addEventListener('click', () => newPlaylistModal.classList.remove('open'));

  savePlaylistBtn.addEventListener('click', async () => {
    const name = playlistNameInput.value.trim();
    if (!name) {
      showToast('Nama playlist tidak boleh kosong', '⚠️');
      return;
    }
    await window.PlaylistManager.createPlaylist(name);
    playlistNameInput.value = '';
    newPlaylistModal.classList.remove('open');
    renderSidebarPlaylists();
    showToast(`Playlist "${name}" berhasil dibuat`, '✓');
  });

  // Add to Playlist Modal Handlers
  const addToPlaylistModal = document.getElementById('add-to-playlist-modal');
  const addToPlaylistCloseBtn = document.getElementById('add-to-playlist-close-btn');
  const targetSongNameEl = document.getElementById('target-song-name');
  const playlistSelectionList = document.getElementById('playlist-selection-list');
  const quickCreatePlInput = document.getElementById('quick-create-pl-input');
  const quickCreatePlBtn = document.getElementById('quick-create-pl-btn');
  let currentTargetSong = null;

  function openAddToPlaylistModal(song) {
    currentTargetSong = song;
    targetSongNameEl.textContent = `Pilih playlist untuk lagu: "${song.title}"`;
    renderPlaylistOptions();
    addToPlaylistModal.classList.add('open');
  }

  function renderPlaylistOptions() {
    playlistSelectionList.innerHTML = '';
    const userPlaylists = window.PlaylistManager.playlists.filter((p) => p.id !== 'favorites');

    if (userPlaylists.length === 0) {
      playlistSelectionList.innerHTML = `
        <div style="text-align: center; padding: 20px; color: var(--text-tertiary); font-size: 0.85rem;">
          Belum ada playlist kustom. Buat playlist baru di bawah ini!
        </div>
      `;
      return;
    }

    userPlaylists.forEach((pl) => {
      const isAdded = currentTargetSong && pl.song_ids.includes(currentTargetSong.id);
      const row = document.createElement('div');
      row.style.cssText = 'display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm);';
      
      row.innerHTML = `
        <div style="display: flex; flex-direction: column;">
          <span style="font-weight: 600; color: var(--text-primary); font-size: 0.88rem;">${escapeHTML(pl.name)}</span>
          <span style="font-size: 0.75rem; color: var(--text-tertiary);">${pl.song_ids.length} lagu</span>
        </div>
        <button class="btn-subtle-scan" style="padding: 6px 12px; ${isAdded ? 'background: var(--accent-subtle); border-color: var(--accent-primary); color: var(--accent-primary);' : ''}">
          ${isAdded ? '✓ Ditambahkan' : '+ Tambah'}
        </button>
      `;

      const actionBtn = row.querySelector('button');
      actionBtn.addEventListener('click', async () => {
        if (!currentTargetSong) return;
        const added = await window.PlaylistManager.toggleSongInPlaylist(pl.id, currentTargetSong.id);
        showToast(added ? `Ditambahkan ke "${pl.name}"` : `Dihapus dari "${pl.name}"`, added ? '✓' : 'ℹ️');
        renderPlaylistOptions();
        renderSidebarPlaylists();
        if (currentNavTab === 'playlist' && currentActivePlaylistId === pl.id) {
          renderCurrentView();
        }
      });

      playlistSelectionList.appendChild(row);
    });
  }

  if (addToPlaylistCloseBtn) {
    addToPlaylistCloseBtn.addEventListener('click', () => {
      addToPlaylistModal.classList.remove('open');
      currentTargetSong = null;
    });
  }

  if (quickCreatePlBtn) {
    quickCreatePlBtn.addEventListener('click', async () => {
      const name = (quickCreatePlInput.value || '').trim();
      if (!name) {
        showToast('Masukkan nama playlist baru', '⚠️');
        return;
      }
      const newPl = await window.PlaylistManager.createPlaylist(name);
      if (currentTargetSong) {
        await window.PlaylistManager.addSongToPlaylist(newPl.id, currentTargetSong.id);
        showToast(`Playlist "${name}" dibuat & lagu ditambahkan!`, '✓');
      } else {
        showToast(`Playlist "${name}" berhasil dibuat`, '✓');
      }
      quickCreatePlInput.value = '';
      renderPlaylistOptions();
      renderSidebarPlaylists();
    });
  }

  // ==========================================
  // EDIT SONG METADATA MODAL HANDLERS
  // ==========================================
  const editMetadataModal = document.getElementById('edit-metadata-modal');
  const editMetadataCloseBtn = document.getElementById('edit-metadata-close-btn');
  const editSongIdInput = document.getElementById('edit-song-id');
  const editSongTitleInput = document.getElementById('edit-song-title');
  const editSongArtistInput = document.getElementById('edit-song-artist');
  const editSongAlbumInput = document.getElementById('edit-song-album');
  const editSongGenreInput = document.getElementById('edit-song-genre');
  const saveMetadataBtn = document.getElementById('save-metadata-btn');

  const autoEnrichBtn = document.getElementById('auto-enrich-btn');
  const enrichResultsWrap = document.getElementById('enrich-results-wrap');

  function openEditMetadataModal(song) {
    if (!song) return;
    editSongIdInput.value = song.id;
    editSongTitleInput.value = song.title || '';
    editSongArtistInput.value = (song.artist === 'Unknown Artist') ? '' : (song.artist || '');
    editSongAlbumInput.value = song.album || '';
    editSongGenreInput.value = song.genre || '';
    if (enrichResultsWrap) {
      enrichResultsWrap.style.display = 'none';
      enrichResultsWrap.innerHTML = '';
    }
    editMetadataModal.classList.add('open');
    editSongTitleInput.focus();
  }

  if (autoEnrichBtn) {
    autoEnrichBtn.addEventListener('click', async () => {
      const title = editSongTitleInput.value.trim();
      const artist = editSongArtistInput.value.trim();
      if (!title) {
        showToast('Ketik judul lagu untuk mencari metadata', '⚠️');
        return;
      }

      autoEnrichBtn.disabled = true;
      autoEnrichBtn.innerHTML = `<span>Mencari iTunes HD...</span>`;
      if (enrichResultsWrap) {
        enrichResultsWrap.style.display = 'flex';
        enrichResultsWrap.innerHTML = `<div style="color: var(--text-secondary); font-size: 0.78rem; text-align: center; padding: 10px;">🔍 Mencari cover HD 1000px & metadata...</div>`;
      }

      try {
        const res = await fetch('api/enrich_metadata.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ title, artist })
        });
        const data = await res.json();
        if (data.status === 'success' && data.results && data.results.length > 0) {
          enrichResultsWrap.innerHTML = '';
          data.results.forEach((match) => {
            const card = document.createElement('div');
            card.className = 'enrich-item-card';
            card.innerHTML = `
              <img src="${escapeHTML(match.cover_hd || match.cover)}" alt="Art" />
              <div class="enrich-item-info">
                <span class="enrich-item-title">${escapeHTML(match.title)}</span>
                <span class="enrich-item-meta">${escapeHTML(match.artist)} • ${escapeHTML(match.album || '')} (${match.year || ''})</span>
              </div>
              <button class="enrich-apply-btn">Pilih</button>
            `;
            card.querySelector('.enrich-apply-btn').addEventListener('click', () => {
              editSongTitleInput.value = match.title;
              editSongArtistInput.value = match.artist;
              if (match.album) editSongAlbumInput.value = match.album;
              if (match.genre) editSongGenreInput.value = match.genre;
              showToast('Data & Cover HD dipilih! Klik Simpan Perubahan', '✨');
              enrichResultsWrap.style.display = 'none';
            });
            enrichResultsWrap.appendChild(card);
          });
        } else {
          enrichResultsWrap.innerHTML = `<div style="color: var(--text-tertiary); font-size: 0.78rem; text-align: center; padding: 8px;">Tidak ada kecocokan ditemukan di iTunes</div>`;
        }
      } catch (err) {
        showToast('Gagal menghubungi server enrich', '⚠️');
        if (enrichResultsWrap) enrichResultsWrap.style.display = 'none';
      } finally {
        autoEnrichBtn.disabled = false;
        autoEnrichBtn.innerHTML = `<span>🪄 Auto-Enrich</span>`;
      }
    });
  }

  if (editMetadataCloseBtn) {
    editMetadataCloseBtn.addEventListener('click', () => {
      editMetadataModal.classList.remove('open');
    });
  }

  if (saveMetadataBtn) {
    saveMetadataBtn.addEventListener('click', async () => {
      const id = editSongIdInput.value;
      const title = editSongTitleInput.value.trim();
      const artist = editSongArtistInput.value.trim();
      const album = editSongAlbumInput.value.trim();
      const genre = editSongGenreInput.value.trim();

      if (!title) {
        showToast('Judul lagu tidak boleh kosong', '⚠️');
        return;
      }

      saveMetadataBtn.disabled = true;
      saveMetadataBtn.textContent = 'Menyimpan...';

      try {
        const res = await auraFetch('api/edit_metadata.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id: id,
            title: title,
            artist: artist,
            album: album,
            genre: genre
          })
        });

        const data = await res.json();
        if (data.status === 'success' && data.song) {
          showToast(data.message || 'Metadata berhasil diperbarui!', '✓');
          editMetadataModal.classList.remove('open');

          // Update in-memory song in PlaylistManager
          const songIdx = window.PlaylistManager.library.findIndex((s) => s.id === id);
          if (songIdx !== -1) {
            window.PlaylistManager.library[songIdx] = {
              ...window.PlaylistManager.library[songIdx],
              ...data.song
            };
          }

          // If current playing track was edited, update player bar too
          if (window.PlaylistManager.currentSong && window.PlaylistManager.currentSong.id === id) {
            window.PlaylistManager.currentSong = {
              ...window.PlaylistManager.currentSong,
              ...data.song
            };
            playerTitle.textContent = data.song.title;
            playerArtist.textContent = data.song.artist;
            heroTitle.textContent = data.song.title;
            heroArtist.textContent = `${data.song.artist} • ${data.song.album || 'Single'}`;
            if ('mediaSession' in navigator) {
              navigator.mediaSession.metadata = new MediaMetadata({
                title: data.song.title,
                artist: data.song.artist,
                album: data.song.album || 'Aura Music',
                artwork: [
                  { src: data.song.cover || DEFAULT_COVER, sizes: '512x512', type: 'image/png' }
                ]
              });
            }
          }

          updateGenreFilterOptions();
          renderCurrentView();
        } else {
          showToast(data.message || 'Gagal menyimpan perubahan', '⚠️');
        }
      } catch (err) {
        console.error('Error saving metadata:', err);
        showToast('Gagal menghubungi server untuk simpan metadata', '⚠️');
      } finally {
        saveMetadataBtn.disabled = false;
        saveMetadataBtn.textContent = 'Simpan Perubahan';
      }
    });
  }

  // ==========================================
  // AUDIO TRIMMER & RINGTONE MAKER HANDLERS
  // ==========================================
  const trimmerModal = document.getElementById('trimmer-modal');
  const trimmerCloseBtn = document.getElementById('trimmer-close-btn');
  const trimmerSongTitle = document.getElementById('trimmer-song-title');
  const trimStartSlider = document.getElementById('trim-start-slider');
  const trimDurSlider = document.getElementById('trim-dur-slider');
  const trimStartDisplay = document.getElementById('trim-start-display');
  const trimDurDisplay = document.getElementById('trim-dur-display');
  const trimEndDisplay = document.getElementById('trim-end-display');
  const trimPreviewBtn = document.getElementById('trim-preview-btn');
  const trimDownloadBtn = document.getElementById('trim-download-btn');
  let currentTrimmerSong = null;
  let trimPreviewTimeout = null;

  function updateTrimmerDisplays() {
    const start = parseInt(trimStartSlider.value, 10) || 0;
    const dur = parseInt(trimDurSlider.value, 10) || 30;
    trimStartDisplay.textContent = formatTime(start);
    trimDurDisplay.textContent = `${dur}s`;
    trimEndDisplay.textContent = formatTime(start + dur);
  }

  function openTrimmerModal(song) {
    if (!song) return;
    currentTrimmerSong = song;
    trimmerSongTitle.textContent = `${song.title} - ${song.artist}`;
    const maxSec = Math.max(60, Math.floor(song.duration || 180));
    trimStartSlider.max = Math.max(0, maxSec - 30);
    trimStartSlider.value = 0;
    trimDurSlider.value = 30;
    updateTrimmerDisplays();
    trimmerModal.classList.add('open');
  }

  if (trimmerCloseBtn) {
    trimmerCloseBtn.addEventListener('click', () => {
      trimmerModal.classList.remove('open');
      if (trimPreviewTimeout) clearTimeout(trimPreviewTimeout);
    });
  }

  if (trimStartSlider) trimStartSlider.addEventListener('input', updateTrimmerDisplays);
  if (trimDurSlider) trimDurSlider.addEventListener('input', updateTrimmerDisplays);

  if (trimPreviewBtn) {
    trimPreviewBtn.addEventListener('click', async () => {
      if (!currentTrimmerSong) return;
      const start = parseInt(trimStartSlider.value, 10) || 0;
      const dur = parseInt(trimDurSlider.value, 10) || 30;

      if (window.PlaylistManager.currentSong?.id !== currentTrimmerSong.id) {
        window.PlaylistManager.playTrack(currentTrimmerSong);
      }

      window.AudioCore.seek(start);
      await window.AudioCore.play();
      showToast(`Memutar preview ${dur} detik dari menit ${formatTime(start)}`, '▶');

      if (trimPreviewTimeout) clearTimeout(trimPreviewTimeout);
      trimPreviewTimeout = setTimeout(() => {
        window.AudioCore.pause();
        showToast('Preview potongan selesai', '⏹️');
      }, dur * 1000);
    });
  }

  if (trimDownloadBtn) {
    trimDownloadBtn.addEventListener('click', () => {
      if (!currentTrimmerSong) return;
      const start = parseInt(trimStartSlider.value, 10) || 0;
      const dur = parseInt(trimDurSlider.value, 10) || 30;
      const fn = currentTrimmerSong.filename || '';
      const tokenParam = _auraAuthToken ? `&token=${encodeURIComponent(_auraAuthToken)}` : '';
      const url = `api/trim_audio.php?filename=${encodeURIComponent(fn)}&start=${start}&duration=${dur}${tokenParam}`;
      showToast('Menyiapkan download potongan ringtone...', '⏬');
      window.location.href = url;
    });
  }

  // ==========================================
  // INTERACTIVE LRC LYRIC MAKER STUDIO HANDLERS
  // ==========================================
  const lrcMakerModal = document.getElementById('lrc-maker-modal');
  const lrcMakerCloseBtn = document.getElementById('lrc-maker-close-btn');
  const lrcMakerSongTitle = document.getElementById('lrc-maker-song-title');
  const lrcStepInput = document.getElementById('lrc-step-input');
  const lrcStepTapper = document.getElementById('lrc-step-tapper');
  const lrcRawInput = document.getElementById('lrc-raw-input');
  const lrcStartSyncBtn = document.getElementById('lrc-start-sync-btn');
  const lrcTapperLinesContainer = document.getElementById('lrc-tapper-lines-container');
  const lrcTapButton = document.getElementById('lrc-tap-button');
  const lrcResetBtn = document.getElementById('lrc-reset-btn');
  const lrcSaveBtn = document.getElementById('lrc-save-btn');

  let currentLrcSong = null;
  let lrcLines = []; // Array of { text, time: null }
  let currentLrcLineIndex = 0;

  function openLrcMakerModal(song) {
    if (!song) return;
    currentLrcSong = song;
    lrcMakerSongTitle.textContent = `${song.title} - ${song.artist}`;
    lrcStepInput.style.display = 'block';
    lrcStepTapper.style.display = 'none';
    lrcRawInput.value = '';
    currentLrcLineIndex = 0;
    lrcLines = [];

    if (song.lyrics && typeof song.lyrics === 'string') {
      lrcRawInput.value = song.lyrics.replace(/\[\d{2}:\d{2}(?:\.\d{2,3})?\]/g, '').trim();
    }

    lrcMakerModal.classList.add('open');
  }

  if (lrcMakerCloseBtn) {
    lrcMakerCloseBtn.addEventListener('click', () => {
      lrcMakerModal.classList.remove('open');
    });
  }

  if (lrcStartSyncBtn) {
    lrcStartSyncBtn.addEventListener('click', async () => {
      const text = lrcRawInput.value.trim();
      if (!text) {
        showToast('Masukkan baris lirik terlebih dahulu', '⚠️');
        return;
      }

      lrcLines = text.split('\n')
        .map((l) => l.trim())
        .filter((l) => l.length > 0)
        .map((line) => ({ text: line, time: null }));

      if (lrcLines.length === 0) {
        showToast('Teks lirik kosong', '⚠️');
        return;
      }

      currentLrcLineIndex = 0;
      renderTapperLines();
      lrcStepInput.style.display = 'none';
      lrcStepTapper.style.display = 'block';

      // Start playing song
      if (currentLrcSong) {
        if (window.PlaylistManager.currentSong?.id !== currentLrcSong.id) {
          window.PlaylistManager.playTrack(currentLrcSong);
        }
        window.AudioCore.seek(0);
        await window.AudioCore.play();
      }

      showToast('Musik dimulai! Tekan tombol TAP setiap lirik dinyanyikan', '🎙️');
    });
  }

  function renderTapperLines() {
    lrcTapperLinesContainer.innerHTML = '';
    lrcLines.forEach((item, idx) => {
      const row = document.createElement('div');
      const isCurrent = idx === currentLrcLineIndex;
      const isPassed = idx < currentLrcLineIndex;
      row.className = `tapper-line-item ${isCurrent ? 'active' : ''} ${isPassed ? 'passed' : ''}`;
      row.id = `tapper-row-${idx}`;

      const timeStr = item.time !== null ? formatLrcTimestamp(item.time) : '--:--';
      row.innerHTML = `
        <div style="display: flex; align-items: center; gap: 8px; min-width: 0; flex: 1;">
          <span style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-tertiary);">${idx + 1}.</span>
          <span style="font-size: 0.86rem; color: ${isCurrent ? 'var(--accent-primary)' : 'var(--text-primary)'}; font-weight: ${isCurrent ? '700' : '400'}; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHTML(item.text)}</span>
        </div>
        <span class="tapper-line-time">${timeStr}</span>
      `;
      lrcTapperLinesContainer.appendChild(row);

      if (isCurrent) {
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  }

  function formatLrcTimestamp(sec) {
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60);
    const ms = Math.floor((sec % 1) * 100);
    return `[${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}.${ms < 10 ? '0' : ''}${ms}]`;
  }

  function handleLrcTap() {
    if (!lrcMakerModal.classList.contains('open') || lrcStepTapper.style.display === 'none') return;
    if (currentLrcLineIndex >= lrcLines.length) {
      showToast('Semua baris sudah diberi waktu! Klik Simpan Lirik', '🎉');
      return;
    }

    const curTime = window.AudioCore.audio.currentTime || 0;
    lrcLines[currentLrcLineIndex].time = curTime;
    currentLrcLineIndex++;

    renderTapperLines();

    if (currentLrcLineIndex >= lrcLines.length) {
      lrcTapButton.innerHTML = `<span>✓ SEMUA BARIS SELESAI DICAP!</span>`;
      lrcTapButton.style.background = 'var(--accent-emerald)';
      showToast('Selesai! Klik Simpan Lirik Sinkron', '✓');
    }
  }

  if (lrcTapButton) lrcTapButton.addEventListener('click', handleLrcTap);

  if (lrcResetBtn) {
    lrcResetBtn.addEventListener('click', () => {
      currentLrcLineIndex = 0;
      lrcLines.forEach((l) => (l.time = null));
      lrcTapButton.innerHTML = `<span>⏱️ [TAP / TEKAN SPASI] UNTUK BARIS INI</span>`;
      lrcTapButton.style.background = 'linear-gradient(135deg, var(--accent-primary), #6366f1)';
      renderTapperLines();
      window.AudioCore.seek(0);
      showToast('Waktu di-reset. Mulai dari baris 1', '🔄');
    });
  }

  if (lrcSaveBtn) {
    lrcSaveBtn.addEventListener('click', async () => {
      if (!currentLrcSong) return;
      if (lrcLines.length === 0) {
        showToast('Tidak ada lirik untuk disimpan', '⚠️');
        return;
      }

      // Build full .lrc text
      const outputLrc = lrcLines.map((l) => {
        const timeTag = l.time !== null ? formatLrcTimestamp(l.time) : '[00:00.00]';
        return `${timeTag} ${l.text}`;
      }).join('\n');

      lrcSaveBtn.disabled = true;
      lrcSaveBtn.textContent = 'Menyimpan...';

      try {
        const res = await auraFetch('api/save_lyrics.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            filename: currentLrcSong.filename,
            lyrics: outputLrc
          })
        });

        const data = await res.json();
        if (data.status === 'success') {
          showToast('Lirik sinkron berhasil dibuat dan disimpan!', '🎉');
          currentLrcSong.lyrics = outputLrc;
          window.LyricsEngine.loadLyrics(outputLrc);
          lrcMakerModal.classList.remove('open');
          await fetchLibrary(true);
        } else {
          showToast(data.message || 'Gagal menyimpan lirik', '⚠️');
        }
      } catch (err) {
        console.error('Error saving LRC:', err);
        showToast('Gagal menghubungi server untuk simpan lirik', '⚠️');
      } finally {
        lrcSaveBtn.disabled = false;
        lrcSaveBtn.textContent = '💾 Simpan Lirik Sinkron';
      }
    });
  }

  // Global Keyboard Shortcuts
  const shortcutsModal = document.getElementById('shortcuts-modal');
  const shortcutsCloseBtn = document.getElementById('shortcuts-close-btn');
  if (shortcutsCloseBtn) shortcutsCloseBtn.addEventListener('click', () => shortcutsModal.classList.remove('open'));
  if (shortcutsModal) shortcutsModal.addEventListener('click', (e) => {
    if (e.target === shortcutsModal) shortcutsModal.classList.remove('open');
  });

  window.addEventListener('keydown', (e) => {
    // Skip when typing in inputs
    if (['input', 'textarea'].includes(e.target.tagName.toLowerCase())) return;
    // Skip when login modal is open
    if (loginModal && loginModal.classList.contains('open')) return;

    if (e.code === 'Space') {
      e.preventDefault();
      // If LRC studio tapper is active, space acts as TAP
      if (lrcMakerModal.classList.contains('open') && lrcStepTapper.style.display !== 'none') {
        handleLrcTap();
        return;
      }
      playPauseBtn.click();
    } else if (e.code === 'ArrowRight') {
      e.preventDefault();
      window.AudioCore.seek(window.AudioCore.audio.currentTime + 5);
    } else if (e.code === 'ArrowLeft') {
      e.preventDefault();
      window.AudioCore.seek(window.AudioCore.audio.currentTime - 5);
    } else if (e.code === 'ArrowUp') {
      e.preventDefault();
      const curVol = window.AudioCore.getVolume();
      const newVol = Math.min(1, curVol + 0.05);
      window.AudioCore.setVolume(newVol);
      if (volumeFill) volumeFill.style.width = `${newVol * 100}%`;
      showToast(`Volume: ${Math.round(newVol * 100)}%`, '🔊');
    } else if (e.code === 'ArrowDown') {
      e.preventDefault();
      const curVol2 = window.AudioCore.getVolume();
      const newVol2 = Math.max(0, curVol2 - 0.05);
      window.AudioCore.setVolume(newVol2);
      if (volumeFill) volumeFill.style.width = `${newVol2 * 100}%`;
      showToast(`Volume: ${Math.round(newVol2 * 100)}%`, newVol2 === 0 ? '🔇' : '🔉');
    } else if (e.key === 'n' || e.key === 'N') {
      window.PlaylistManager.next();
    } else if (e.key === 'p' || e.key === 'P') {
      window.PlaylistManager.prev();
    } else if (e.key === 's' || e.key === 'S') {
      shuffleBtn.click();
    } else if (e.key === 'r' || e.key === 'R') {
      repeatBtn.click();
    } else if (e.key === 'q' || e.key === 'Q') {
      if (panelToggleBtn) panelToggleBtn.click();
    } else if (e.key === 'l' || e.key === 'L') {
      if (immersiveOverlay.classList.contains('open')) {
        immersiveCloseBtn.click();
      } else {
        immersiveBtn.click();
      }
    } else if (e.key === 'f' || e.key === 'F') {
      immersiveBtn.click();
    } else if (e.key === 'm' || e.key === 'M') {
      volumeBtn.click();
    } else if (e.key === '?') {
      if (shortcutsModal) {
        shortcutsModal.classList.toggle('open');
      }
    }
  });

  // ==========================================
  // SCREEN WAKE LOCK API (Prevents Display Sleep)
  // ==========================================
  let wakeLockObj = null;
  async function requestWakeLock() {
    if ('wakeLock' in navigator && !wakeLockObj) {
      try {
        wakeLockObj = await navigator.wakeLock.request('screen');
        wakeLockObj.addEventListener('release', () => { wakeLockObj = null; });
      } catch (err) {}
    }
  }

  async function releaseWakeLock() {
    if (wakeLockObj) {
      try {
        await wakeLockObj.release();
        wakeLockObj = null;
      } catch (e) {}
    }
  }

  // ==========================================
  // CRASH RECOVERY & SESSION AUTO-SAVE ENGINE
  // ==========================================
  function savePlaybackSessionState() {
    const song = window.PlaylistManager.currentSong;
    if (!song) return;
    const sessionData = {
      songId: song.id,
      currentTime: window.AudioCore.audio.currentTime || 0,
      duration: window.AudioCore.audio.duration || song.duration || 0,
      volume: window.AudioCore.getVolume(),
      isShuffle: window.PlaylistManager.isShuffle,
      repeatMode: window.PlaylistManager.repeatMode,
      currentNavTab: currentNavTab,
      timestamp: Date.now()
    };
    try {
      localStorage.setItem('aura_last_session', JSON.stringify(sessionData));
    } catch (e) {}
  }

  function restoreLastSessionState() {
    try {
      const raw = localStorage.getItem('aura_last_session');
      if (!raw) return;
      const saved = JSON.parse(raw);
      if (!saved || !saved.songId) return;

      const song = window.PlaylistManager.getSongById(saved.songId);
      if (!song) return;

      // If user hasn't started playing another song yet, restore previous state
      if (!window.PlaylistManager.currentSong) {
        window.PlaylistManager.currentSong = song;
        const coverSrc = getSafeCoverUrl(song);
        playerCover.src = coverSrc;
        playerTitle.textContent = song.title;
        playerArtist.textContent = song.artist;
        setupHeroFeatured(song);

        // Preload audio and seek position without auto-blasting audio
        const savedPos = saved.currentTime || 0;
        const totalDur = saved.duration || song.duration || 0;
        timeCurrent.textContent = formatTime(savedPos);
        timeTotal.textContent = formatTime(totalDur);
        const percent = totalDur > 0 ? (savedPos / totalDur) * 100 : 0;
        progressFill.style.width = `${percent}%`;
        progressThumb.style.left = `${percent}%`;

        // Waveform preview if active
        if (waveformScrubber) {
          waveformScrubber.setTrack(song);
          waveformScrubber.setProgress(savedPos, totalDur);
        }

        window.AudioCore.loadTrack(song.url);
        window.AudioCore.seek(savedPos);

        // Ambient colors
        window.AmbientColor.applyToRoot(coverSrc);

        // Lyrics preload
        if (song.lyrics) {
          window.LyricsEngine.loadLyrics(song.lyrics);
          window.LyricsEngine.updateTime(savedPos);
        }

        showToast(`Sesi sebelumnya dipulihkan: "${song.title}" (${formatTime(savedPos)})`, '🔄');
      }
    } catch (e) {
      console.warn('Failed to restore session:', e);
    }
  }

  // ==========================================
  // ANTI-ACCIDENTAL TAB CLOSE GUARD
  // ==========================================
  window.addEventListener('beforeunload', (e) => {
    const isPlaying = window.AudioCore && !window.AudioCore.audio.paused;
    const isDownloading = isBatchDownloading;
    if (isPlaying || isDownloading) {
      savePlaybackSessionState();
      e.preventDefault();
      e.returnValue = 'Musik atau pengunduhan sedang aktif. Yakin ingin menutup Aura Music?';
      return e.returnValue;
    }
  });

  // ==========================================
  // PAGE VISIBILITY (BATTERY & MEMORY SHIELD)
  // ==========================================
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      // Tab in background: throttle heavy visualizer rendering loops
      if (visualizer) visualizer.stop();
      if (immersiveVisualizer) immersiveVisualizer.stop();
    } else {
      // Tab in foreground: resume visualizers and ensure audio context is active
      if (window.AudioCore && !window.AudioCore.audio.paused) {
        if (visualizer && visualizerView.style.display !== 'none') visualizer.start();
        if (immersiveOverlay.classList.contains('open') && immersiveVisualizer) {
          immersiveVisualizer.start();
        }
        requestWakeLock();
      }
    }
  });

  // ==========================================
  // GLOBAL ERROR BOUNDARY (CRASH SHIELD)
  // ==========================================
  window.addEventListener('unhandledrejection', (event) => {
    console.warn('[Aura Crash Shield] Handled unhandledrejection:', event.reason);
    event.preventDefault();
  });

  window.addEventListener('error', (event) => {
    console.warn('[Aura Crash Shield] Handled uncaught exception:', event.message);
  });

  // PWA Service Worker Registration & Install Prompt
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('./sw.js')
        .then(() => console.log('Aura Music Service Worker Registered'))
        .catch((err) => console.warn('Service Worker registration failed:', err));
    });
  }

  let deferredPrompt = null;
  const pwaInstallBtn = document.getElementById('pwa-install-btn');

  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    if (pwaInstallBtn) {
      pwaInstallBtn.style.display = 'inline-flex';
      pwaInstallBtn.addEventListener('click', async () => {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        const choice = await deferredPrompt.userChoice;
        if (choice.outcome === 'accepted') {
          showToast('Aplikasi Aura Music berhasil di-install!', '✓');
          pwaInstallBtn.style.display = 'none';
        }
        deferredPrompt = null;
      });
    }
  });

  window.addEventListener('appinstalled', () => {
    if (pwaInstallBtn) pwaInstallBtn.style.display = 'none';
    showToast('Aura Music siap digunakan sebagai aplikasi!', '✨');
  });

  // Initial Data Load & Session Restore
  await fetchLibrary();
  restoreLastSessionState();
});
