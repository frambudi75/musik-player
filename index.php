<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>NadaKita - Personal Audio Experience</title>
  <meta name="description" content="Personal High-Fidelity Music Player with Dynamic Ambient Glow, Realtime Synced Lyrics, and 10-Band Graphic Equalizer">
  
  <!-- PWA Manifest & Meta -->
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#0f121a">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="NadaKita">
  <link rel="apple-touch-icon" href="assets/icons/icon-192.png">
  <link rel="icon" type="image/png" sizes="192x192" href="assets/icons/icon-192.png">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🎵</text></svg>">
  
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/player.css">
</head>
<body>

  <!-- Dynamic Ambient Glow Layer -->
  <div class="ambient-glow-wrapper">
    <div class="ambient-glow-mesh" id="ambient-mesh"></div>
  </div>

  <div class="app-container">
    <div class="app-body">
      
      <!-- ==========================================
           LEFT SIDEBAR NAVIGATION
           ========================================== -->
      <aside class="sidebar">
        <div class="brand-logo" id="brand-home-btn">
          <div class="brand-icon-box">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 18V5l12-2v13"></path>
              <circle cx="6" cy="18" r="3"></circle>
              <circle cx="18" cy="16" r="3"></circle>
            </svg>
          </div>
          <span class="brand-text">NadaKita</span>
          <span class="brand-badge">STUDIO</span>
        </div>

        <nav class="nav-group main-nav-group">
          <span class="nav-label">Menu Utama</span>
          <a href="#" class="nav-item active" data-tab="library">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
              <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            <span class="nav-text-desktop">Koleksi Lagu</span>
            <span class="nav-text-mobile">Koleksi</span>
          </a>
          <a href="#" class="nav-item" data-tab="liked">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            <span class="nav-text-desktop">Liked Songs</span>
            <span class="nav-text-mobile">Favorit</span>
          </a>
          <a href="#" class="nav-item" data-tab="stats">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 20V10"></path>
              <path d="M12 20V4"></path>
              <path d="M6 20v-6"></path>
            </svg>
            <span class="nav-text-desktop">Statistik & Wrapped</span>
            <span class="nav-text-mobile">Statistik</span>
          </a>
          <a href="#" class="nav-item" data-tab="offline" title="Lagu yang disimpan ke memori HP/PC untuk diputar tanpa internet">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
              <polyline points="7 10 12 15 17 10"></polyline>
              <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            <span class="nav-text-desktop">Lagu Offline</span>
            <span class="nav-text-mobile">Offline</span>
          </a>
          <a href="#" class="nav-item" id="sidebar-nav-upload">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
              <polyline points="17 8 12 3 7 8"></polyline>
              <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            <span class="nav-text-desktop">Upload Musik</span>
            <span class="nav-text-mobile">Tambah</span>
          </a>
        </nav>

        <nav class="nav-group playlist-nav-group" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
          <div style="display: flex; align-items: center; justify-content: space-between; padding: 0 12px 6px;">
            <span class="nav-label" style="padding: 0;">Playlist Saya</span>
            <button id="create-playlist-btn" class="icon-btn" style="width: 24px; height: 24px;" title="Buat Playlist Baru">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
            </button>
          </div>
          <div class="sidebar-playlists-scroll" id="sidebar-playlists">
            <!-- Dynamic Playlists Rendered Here -->
          </div>
        </nav>

        <div class="sidebar-footer">
          <button class="btn-upload-trigger" id="upload-trigger-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
              <polyline points="17 8 12 3 7 8"></polyline>
              <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            <span>Upload Musik / Lirik</span>
          </button>
        </div>
      </aside>

      <!-- ==========================================
           MAIN CONTENT VIEW
           ========================================== -->
      <main class="main-content">
        <!-- Top Sticky Header -->
        <header class="main-header">
          <div class="header-left">
            <div class="search-box-wrapper">
              <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input type="text" id="search-input" class="search-input" placeholder="Cari lagu, artis, album, nama file..." />
              <button type="button" id="search-clear-btn" class="search-clear-btn" title="Hapus pencarian" style="display: none;">&times;</button>
            </div>
          </div>
          <div class="header-right">
            <button class="btn-header-action" id="pwa-install-btn" style="display: none; background: rgba(59, 130, 246, 0.15); border-color: var(--accent-primary); color: #60a5fa;" title="Install Aplikasi ke Desktop / HP">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
              </svg>
              <span>Install App</span>
            </button>
            <button class="btn-header-action" id="header-upload-btn" title="Upload / Download Musik">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
              </svg>
              <span>+ Tambah Musik</span>
            </button>
            <button class="icon-btn" id="refresh-scan-btn" title="Pindai Ulang Folder Lagu (Rescan)">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="23 4 23 10 17 10"></polyline>
                <polyline points="1 20 1 14 7 14"></polyline>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
              </svg>
            </button>
            <button class="icon-btn" id="eq-btn" title="Studio Equalizer & DSP FX">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="4" y1="21" x2="4" y2="14"></line>
                <line x1="4" y1="10" x2="4" y2="3"></line>
                <line x1="12" y1="21" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12" y2="3"></line>
                <line x1="20" y1="21" x2="20" y2="16"></line>
                <line x1="20" y1="12" x2="20" y2="3"></line>
                <line x1="1" y1="14" x2="7" y2="14"></line>
                <line x1="9" y1="8" x2="15" y2="8"></line>
                <line x1="17" y1="16" x2="23" y2="16"></line>
              </svg>
            </button>
            <button class="icon-btn" id="panel-toggle-btn" title="Toggle Panel Lirik & Visualizer">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="15" y1="3" x2="15" y2="21"></line>
              </svg>
            </button>
          </div>
        </header>

        <!-- Hero Featured Card -->
        <section class="hero-banner" id="hero-banner">
          <img class="hero-cover" id="hero-cover" src="assets/sample_covers/placeholder.svg" alt="Cover Album" />
          <div class="hero-content">
            <span class="hero-tag">NADAKITA NOW PLAYING</span>
            <h1 class="hero-title" id="hero-title">Pilih lagu untuk memulai</h1>
            <p class="hero-meta" id="hero-artist">Koleksi audio lokal Anda</p>
            <div class="hero-actions">
              <button class="btn-primary-play" id="hero-play-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                  <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
                <span>Putar Sekarang</span>
              </button>
              <button class="btn-secondary" id="hero-upload-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="17 8 12 3 7 8"></polyline>
                  <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <span>Upload Musik Baru</span>
              </button>
            </div>
          </div>
        </section>

        <!-- Library Header & View Switcher -->
        <div class="section-header" id="section-header">
          <div style="display: flex; align-items: center; gap: 14px;">
            <h2 class="section-title" id="section-title">Koleksi Musik</h2>
            <button class="btn-subtle-scan" id="quick-rescan-btn" title="Pindai ulang folder">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="23 4 23 10 17 10"></polyline>
                <polyline points="1 20 1 14 7 14"></polyline>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
              </svg>
              <span>Scan Folder</span>
            </button>
            <button class="btn-subtle-scan" id="health-check-btn" title="Cek & perbaiki lagu rusak" style="border-color: rgba(34, 197, 94, 0.3);">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
              </svg>
              <span>Cek Kesehatan Lagu</span>
            </button>
          </div>
          <div class="view-toggle">
            <button class="view-btn active" data-view="table" title="Tampilan Tabel">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="8" y1="6" x2="21" y2="6"></line>
                <line x1="8" y1="12" x2="21" y2="12"></line>
                <line x1="8" y1="18" x2="21" y2="18"></line>
                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                <line x1="3" y1="18" x2="3.01" y2="18"></line>
              </svg>
            </button>
            <button class="view-btn" data-view="grid" title="Tampilan Grid Cover">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
              </svg>
            </button>
          </div>
        </div>

        <!-- Sort & Quick Filter Bar -->
        <div class="filter-sort-bar" id="filter-sort-bar">
          <div class="filter-chips-scroll">
            <span class="filter-chip active" data-sort="default">Semua</span>
            <span class="filter-chip" data-sort="newest">⏱️ Terbaru</span>
            <span class="filter-chip" data-sort="top">🔥 Paling Sering Diputar</span>
            <span class="filter-chip" data-sort="title">🔤 A–Z Judul</span>
            <span class="filter-chip" data-sort="artist">🎤 Artis</span>
          </div>
          <div class="genre-filter-wrap">
            <select id="genre-filter-select" class="genre-filter-select">
              <option value="">Semua Genre</option>
            </select>
          </div>
        </div>

        <!-- Table View -->
        <div id="table-view-wrap">
          <table class="song-table">
            <colgroup>
              <col style="width: 46px;">
              <col style="width: auto;">
              <col style="width: 20%;" class="col-album">
              <col style="width: 12%;" class="col-genre">
              <col style="width: 210px;">
            </colgroup>
            <thead>
              <tr>
                <th style="text-align: center;">#</th>
                <th>Judul & Artis</th>
                <th class="col-album">Album</th>
                <th class="col-genre">Genre</th>
                <th style="text-align: right; padding-right: 14px;">Aksi</th>
              </tr>
            </thead>
            <tbody id="song-table-body">
              <!-- Dynamic Rows -->
            </tbody>
          </table>
        </div>

        <!-- Grid View (Hidden by default) -->
        <div class="songs-grid" id="songs-grid" style="display: none;">
          <!-- Dynamic Grid Items -->
        </div>

        <!-- Statistics & Wrapped View (Hidden by default) -->
        <div id="stats-view-wrap" style="display: none;">
          <!-- Wrapped Banner Hero -->
          <div class="stats-hero-banner">
            <div class="stats-hero-glow"></div>
            <div class="stats-hero-content">
              <div class="stats-hero-left">
                <span class="stats-hero-tag">✨ NADAKITA STATS & WRAPPED</span>
                <h2 class="stats-hero-title">Kilasan Musik Anda</h2>
                <p class="stats-hero-subtitle">Berdasarkan kebiasaan mendengarkan lagu di pemutar NadaKita.</p>
                <div class="stats-badges-row">
                  <span class="persona-chip" id="stat-persona-badge">🎧 Penikmat Musik Aktif</span>
                  <span class="persona-chip" id="stat-genre-badge">🎵 Genre: Audio</span>
                </div>
              </div>
              <div class="stats-hero-spotlight" id="stats-top-spotlight">
                <!-- Rendered dynamically by JS -->
              </div>
            </div>
          </div>

          <!-- 4 Rich KPI Metric Cards -->
          <div class="stats-overview-grid">
            <div class="stat-card stat-card-time">
              <div class="stat-card-top">
                <div class="stat-icon-box stat-icon-blue">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </div>
                <span class="stat-badge-sub" id="stat-time-detail">0 Menit</span>
              </div>
              <div class="stat-num" id="stat-total-minutes">0</div>
              <div class="stat-label">Total Menit Didengar</div>
            </div>

            <div class="stat-card stat-card-plays">
              <div class="stat-card-top">
                <div class="stat-icon-box stat-icon-purple">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                </div>
                <span class="stat-badge-sub">Riwayat Putar</span>
              </div>
              <div class="stat-num" id="stat-total-plays">0</div>
              <div class="stat-label">Total Pemutaran Lagu</div>
            </div>

            <div class="stat-card stat-card-artists">
              <div class="stat-card-top">
                <div class="stat-icon-box stat-icon-pink">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="22"></line></svg>
                </div>
                <span class="stat-badge-sub">Musisi Unik</span>
              </div>
              <div class="stat-num" id="stat-total-artists">0</div>
              <div class="stat-label">Artis Berbeda</div>
            </div>

            <div class="stat-card stat-card-top-artist">
              <div class="stat-card-top">
                <div class="stat-icon-box stat-icon-amber">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                </div>
                <span class="stat-badge-sub" id="stat-top-artist-plays">0x Putar</span>
              </div>
              <div class="stat-num stat-artist-name" id="stat-top-artist-name">-</div>
              <div class="stat-label">Artis Paling Sering Diputar</div>
            </div>
          </div>

          <!-- Detailed 2-Column Analytics Showcase -->
          <div class="stats-sections-flex">
            <!-- Left: Top 10 Tracks with artwork & instant play -->
            <div class="stats-col-left">
              <div class="stats-section-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span style="font-size: 1.2rem;">🔥</span>
                  <h3 class="stats-section-title">Top 10 Lagu Paling Sering Diputar</h3>
                </div>
                <span class="stats-section-hint">Klik untuk langsung putar</span>
              </div>
              <div class="top-tracks-list" id="top-tracks-container">
                <!-- Dynamic Rich Top Tracks -->
              </div>
            </div>

            <!-- Right: Top Artists & Music Personality Card -->
            <div class="stats-col-right">
              <div class="stats-section-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span style="font-size: 1.2rem;">⭐</span>
                  <h3 class="stats-section-title">Top Artis Favorit</h3>
                </div>
              </div>
              <div class="top-artists-list" id="top-artists-container">
                <!-- Dynamic Top Artists -->
              </div>

              <!-- Quick Listening Insight Card -->
              <div class="stats-insight-card">
                <div class="insight-header">
                  <span style="font-size: 1.15rem;">💡</span>
                  <span style="font-weight: 700; font-size: 0.92rem; color: var(--text-primary);">Wawasan Koleksi Musik</span>
                </div>
                <p class="insight-text" id="stat-insight-text">
                  Lagu di NadaKita disimpan secara lokal dengan responsivitas tinggi, visualizer realtime, dan pemutar audio fidelitas tinggi.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom Spacer to guarantee zero cutoff above bottom player bar -->
        <div class="content-bottom-spacer" style="height: 24px; min-height: 24px; flex-shrink: 0; pointer-events: none; width: 100%;"></div>
      </main>

      <!-- ==========================================
           RIGHT PANEL (Lyrics / Visualizer / Queue)
           ========================================== -->
      <aside class="right-panel" id="right-panel">
        <div class="panel-header">
          <div class="panel-tabs">
            <button class="panel-tab-btn active" data-tab="lyrics">Lirik</button>
            <button class="panel-tab-btn" data-tab="visualizer">Visualizer</button>
            <button class="panel-tab-btn" data-tab="queue">Antrean</button>
          </div>
          <button class="icon-btn mobile-panel-close-btn" id="mobile-panel-close-btn" title="Tutup Panel">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <div class="panel-content">
          <!-- Lyrics Tab -->
          <div class="lyrics-container" id="lyrics-view">
            <div class="lyrics-sync-bar" id="lyrics-sync-bar">
              <span style="font-size: 0.72rem; color: var(--text-tertiary); font-weight: 600;">TIMING:</span>
              <button class="sync-offset-btn" id="sync-minus-btn" title="Mundurkan lirik 0.5 detik">-0.5s</button>
              <span class="sync-offset-badge" id="sync-offset-val">0.0s</span>
              <button class="sync-offset-btn" id="sync-plus-btn" title="Majukan lirik 0.5 detik">+0.5s</button>
              <button class="sync-offset-btn" id="sync-reset-btn" title="Reset offset ke 0s" style="color: var(--text-tertiary);">Reset</button>
            </div>
            <div class="lyric-empty-state">
              <p>Pilih lagu untuk menampilkan lirik sinkron</p>
            </div>
          </div>

          <!-- Visualizer Tab -->
          <div class="visualizer-container" id="visualizer-view" style="display: none;">
            <canvas id="visualizer-canvas" class="visualizer-canvas"></canvas>
            <div class="visualizer-modes-bar">
              <span class="vis-mode-chip active" data-mode="bars">Spectrum Bars</span>
              <span class="vis-mode-chip" data-mode="wave">Fluid Wave</span>
              <span class="vis-mode-chip" data-mode="radial">Radial Ring</span>
            </div>
          </div>

          <!-- Queue Tab -->
          <div class="queue-container" id="queue-view" style="display: none;">
            <!-- Dynamic Queue Items -->
          </div>
        </div>
      </aside>

    </div>

    <!-- ==========================================
         BOTTOM FLOATING MASTER PLAYER BAR
         ========================================== -->
    <footer class="bottom-player-bar">
      <!-- Left Track Info -->
      <div class="player-left">
        <div class="player-cover-wrap" id="player-cover-wrap">
          <img class="player-cover-img" id="player-cover" src="assets/sample_covers/placeholder.svg" alt="Cover" />
        </div>
        <div class="player-track-info">
          <span class="player-title" id="player-title">Belum ada lagu</span>
          <span class="player-artist" id="player-artist">NadaKita Player</span>
        </div>
        <button class="player-heart-btn" id="player-heart-btn" title="Tambah ke Favorit">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
          </svg>
        </button>
      </div>

      <!-- Center Playback Controls & Scrubber -->
      <div class="player-center">
        <div class="controls-row">
          <button class="ctrl-btn" id="shuffle-btn" title="Acak Lagu (Shuffle)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="16 3 21 3 21 8"></polyline>
              <line x1="4" y1="20" x2="21" y2="3"></line>
              <polyline points="21 16 21 21 16 21"></polyline>
              <line x1="15" y1="15" x2="21" y2="21"></line>
              <line x1="4" y1="4" x2="9" y2="9"></line>
            </svg>
          </button>
          <button class="ctrl-btn" id="prev-btn" title="Sebelumnya">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <polygon points="19 20 9 12 19 4 19 20"></polygon>
              <line x1="5" y1="19" x2="5" y2="5" stroke="currentColor" stroke-width="2.5"></line>
            </svg>
          </button>
          <button class="ctrl-btn ctrl-btn-play" id="play-pause-btn" title="Putar / Jeda (Space)">
            <svg id="play-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <polygon points="5 3 19 12 5 21 5 3"></polygon>
            </svg>
            <svg id="pause-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="display: none;">
              <rect x="6" y="4" width="4" height="16"></rect>
              <rect x="14" y="4" width="4" height="16"></rect>
            </svg>
          </button>
          <button class="ctrl-btn" id="next-btn" title="Berikutnya">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <polygon points="5 4 15 12 5 20 5 4"></polygon>
              <line x1="19" y1="5" x2="19" y2="19" stroke="currentColor" stroke-width="2.5"></line>
            </svg>
          </button>
          <button class="ctrl-btn" id="repeat-btn" title="Ulangi Lagu (Repeat)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="17 1 21 5 17 9"></polyline>
              <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
              <polyline points="7 23 3 19 7 15"></polyline>
              <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
            </svg>
          </button>
        </div>

        <div class="progress-row">
          <span class="time-stamp" id="time-current">0:00</span>
          <div class="progress-wrapper-box" style="flex: 1; position: relative; display: flex; align-items: center;">
            <div class="custom-range-slider" id="progress-bar">
              <div class="slider-track">
                <div class="slider-fill" id="progress-fill"></div>
              </div>
              <div class="slider-thumb" id="progress-thumb"></div>
            </div>
            <canvas id="waveform-canvas" class="waveform-canvas" style="display: none; width: 100%; height: 26px; cursor: pointer; border-radius: 4px;"></canvas>
          </div>
          <button id="toggle-waveform-btn" class="icon-btn-waveform" title="Ganti Tampilan Waveform Akustik / Bar Biasa" style="background: none; border: 1px solid var(--border-subtle); border-radius: 4px; padding: 2px 6px; color: var(--text-secondary); cursor: pointer; font-size: 0.72rem; display: flex; align-items: center; gap: 2px; transition: var(--transition-fast);">
            <span>🌊</span>
          </button>
          <span class="time-stamp" id="time-total">0:00</span>
        </div>
      </div>

      <!-- Right Utility Actions -->
      <div class="player-right">
        <!-- Floating PiP Button -->
        <button class="ctrl-btn" id="pip-btn" title="Mini Floating Player (Picture-in-Picture)">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
            <rect x="12" y="9" width="8" height="6" rx="1" ry="1" fill="rgba(255,255,255,0.2)"></rect>
            <line x1="8" y1="21" x2="16" y2="21"></line>
            <line x1="12" y1="17" x2="12" y2="21"></line>
          </svg>
        </button>

        <!-- Sleep Timer Trigger Button -->
        <button class="ctrl-btn" id="sleep-timer-btn" title="Pengatur Waktu Tidur (Sleep Timer)" style="position: relative;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
          </svg>
          <span id="sleep-badge" class="sleep-active-badge" style="display: none;">30m</span>
        </button>

        <div class="volume-wrapper">
          <button class="ctrl-btn" id="volume-btn" title="Mute / Unmute (M)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
              <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
            </svg>
          </button>
          <div class="custom-range-slider" id="volume-bar">
            <div class="slider-track">
              <div class="slider-fill" id="volume-fill" style="width: 100%;"></div>
            </div>
          </div>
        </div>

        <button class="ctrl-btn" id="immersive-btn" title="Mode Layar Penuh Fokus (F)">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path>
          </svg>
        </button>
      </div>

      <!-- Mobile Mini Player Live Progress Bar -->
      <div class="mobile-player-progress" id="mobile-player-progress">
        <div class="mobile-progress-fill" id="mobile-progress-fill"></div>
      </div>
    </footer>
  </div>

  <!-- Offscreen Canvas and Video for Picture-in-Picture -->
  <canvas id="pip-canvas" width="600" height="340" style="display: none;"></canvas>
  <video id="pip-video" muted playsinline style="display: none;"></video>

  <!-- ==========================================
       IMMERSIVE FULLSCREEN MODE OVERLAY (Apple Music Style Karaoke)
       ========================================== -->
  <div class="immersive-overlay" id="immersive-overlay">
    <div class="immersive-bg-mesh"></div>
    <canvas id="immersive-canvas" class="immersive-canvas-bg"></canvas>
    <div class="immersive-top-bar">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-weight: 700; font-size: 0.9rem; letter-spacing: 0.05em; color: var(--text-secondary);">NADAKITA IMMERSIVE KARAOKE</span>
      </div>
      <button class="icon-btn" id="immersive-close-btn" title="Tutup Mode Layar Penuh (Esc)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <div class="immersive-body">
      <div class="immersive-left-art">
        <img class="immersive-album-cover" id="immersive-cover" src="assets/sample_covers/placeholder.svg" alt="Artwork" />
        <div>
          <h2 class="immersive-track-title" id="immersive-title">Judul Lagu</h2>
          <p class="immersive-track-artist" id="immersive-artist">Nama Artis</p>
        </div>
      </div>
      <div class="immersive-right-lyrics" id="immersive-lyrics">
        <!-- Live lyrics here -->
      </div>
    </div>
  </div>

  <!-- ==========================================
       SLEEP TIMER MODAL
       ========================================== -->
  <div class="modal-overlay" id="sleep-modal">
    <div class="modal-card" style="max-width: 420px;">
      <div class="modal-header">
        <h3 class="modal-title" style="display: flex; align-items: center; gap: 8px;">
          <span>🌙</span> Pengatur Waktu Tidur
        </h3>
        <button class="modal-close-btn" id="sleep-close-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <p style="color: var(--text-secondary); font-size: 0.86rem; margin-bottom: 18px;">
        Musik akan meredup (*fade-out*) secara lembut dan berhenti otomatis saat waktu habis.
      </p>
      
      <div class="sleep-options-list">
        <button class="sleep-opt-btn active" data-minutes="0">
          <span>Nonaktif</span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
        </button>
        <button class="sleep-opt-btn" data-minutes="15">
          <span>15 Menit</span>
        </button>
        <button class="sleep-opt-btn" data-minutes="30">
          <span>30 Menit</span>
        </button>
        <button class="sleep-opt-btn" data-minutes="45">
          <span>45 Menit</span>
        </button>
        <button class="sleep-opt-btn" data-minutes="60">
          <span>1 Jam (60 Menit)</span>
        </button>
        <button class="sleep-opt-btn" data-minutes="end_of_track">
          <span>Selesai Lagu Ini (End of Track)</span>
        </button>
      </div>
    </div>
  </div>

  <!-- ==========================================
       EQUALIZER & STUDIO FX MODAL
       ========================================== -->
  <div class="modal-overlay" id="eq-modal">
    <div class="modal-card" style="max-width: 680px;">
      <div class="modal-header">
        <h3 class="modal-title">Studio Equalizer & DSP FX</h3>
        <button class="modal-close-btn" id="eq-close-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- DSP Special Presets Bar -->
      <div style="margin-bottom: 16px;">
        <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 8px;">Mode Audio Studio (DSP FX Presets):</span>
        <div class="dsp-modes-grid" style="display: flex; gap: 8px; flex-wrap: wrap;">
          <button class="dsp-mode-chip active" id="dsp-clean-btn" data-mode="clean">Studio Clean</button>
          <button class="dsp-mode-chip" id="dsp-8d-btn" data-mode="8d" title="Audio berputar mengelilingi kepala 360 derajat">8D Spatial Audio 🎧</button>
          <button class="dsp-mode-chip" id="dsp-slowed-btn" data-mode="slowed">Slowed + Reverb 🌌</button>
          <button class="dsp-mode-chip" id="dsp-nightcore-btn" data-mode="nightcore">Nightcore ⚡</button>
          <button class="dsp-mode-chip" id="dsp-vaporwave-btn" data-mode="vaporwave">Vaporwave 📼</button>
          <button class="dsp-mode-chip" id="dsp-bassmaster-btn" data-mode="bassmaster">Bass Master 808 🔊</button>
          <button class="dsp-mode-chip" id="dsp-karaoke-btn" data-mode="karaoke" title="Meredam vokal penyanyi asli">Karaoke Mode 🎤</button>
          <button class="dsp-mode-chip" id="dsp-haptic-btn" data-mode="haptic" title="HP Bergetar Ikuti Dentuman Bass">Haptic Bass 📳</button>
        </div>
      </div>

      <!-- Ambient White Noise Layer Mixer -->
      <div style="margin-bottom: 16px; background: rgba(59, 130, 246, 0.06); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: var(--radius-sm); padding: 12px 14px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <span style="font-size: 0.78rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 6px;">
            <span>🌧️ Ambient White Noise Layer (Mixer Suara Latar)</span>
          </span>
          <span id="ambient-vol-val" style="font-size: 0.75rem; color: var(--text-secondary); font-family: var(--font-mono);">40%</span>
        </div>
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
          <div class="ambient-chips-wrap" style="display: flex; gap: 6px;">
            <button class="eq-preset-chip active ambient-chip" data-ambient="off">Off</button>
            <button class="eq-preset-chip ambient-chip" data-ambient="rain">🌧️ Hujan</button>
            <button class="eq-preset-chip ambient-chip" data-ambient="fire">🔥 Api Unggun</button>
            <button class="eq-preset-chip ambient-chip" data-ambient="vinyl">☕ Vinyl</button>
          </div>
          <div style="display: flex; align-items: center; gap: 8px; flex: 1; min-width: 140px;">
            <input type="range" id="ambient-vol-slider" min="0" max="1" step="0.05" value="0.4" style="width: 100%; accent-color: #38bdf8;" title="Volume Suara Latar">
          </div>
        </div>
      </div>

      <!-- Speed, Crossfade, Preamp Booster, & 8D Speed Controls -->
      <div class="dsp-tuning-row" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; background: var(--bg-surface); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); margin-bottom: 20px;">
        <div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <span style="font-size: 0.76rem; font-weight: 600; color: var(--text-secondary);">Speed</span>
            <span id="dsp-speed-val" style="font-size: 0.76rem; font-weight: 700; color: var(--accent-primary);">1.0x</span>
          </div>
          <input type="range" id="dsp-speed-slider" min="0.5" max="1.5" step="0.05" value="1.0" style="width: 100%; accent-color: var(--accent-primary);">
        </div>
        <div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <span style="font-size: 0.76rem; font-weight: 600; color: var(--text-secondary);">Crossfade</span>
            <span id="dsp-crossfade-val" style="font-size: 0.76rem; font-weight: 700; color: var(--accent-primary);">0 detik</span>
          </div>
          <input type="range" id="dsp-crossfade-slider" min="0" max="6" step="2" value="0" style="width: 100%; accent-color: var(--accent-primary);">
        </div>
        <div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <span style="font-size: 0.76rem; font-weight: 600; color: var(--text-secondary);">Preamp Boost</span>
            <span id="dsp-preamp-val" style="font-size: 0.76rem; font-weight: 700; color: #f59e0b;">100%</span>
          </div>
          <input type="range" id="dsp-preamp-slider" min="1.0" max="3.0" step="0.1" value="1.0" style="width: 100%; accent-color: #f59e0b;" title="Tingkatkan volume ekstra hingga 300%">
        </div>
        <div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <span style="font-size: 0.76rem; font-weight: 600; color: var(--text-secondary);">8D Orbit Speed</span>
            <span id="dsp-8d-speed-val" style="font-size: 0.76rem; font-weight: 700; color: #8b5cf6;">1.0x</span>
          </div>
          <input type="range" id="dsp-8d-speed-slider" min="0.3" max="2.5" step="0.1" value="1.0" style="width: 100%; accent-color: #8b5cf6;" title="Kecepatan rotasi 8D Spatial Audio">
        </div>
      </div>

      <div class="eq-layout">
        <!-- Preset Selector -->
        <div class="eq-presets-bar">
          <span class="eq-preset-chip active" data-preset="flat">Flat</span>
          <span class="eq-preset-chip" data-preset="bass_boost">Bass Boost</span>
          <span class="eq-preset-chip" data-preset="electronic">Electronic</span>
          <span class="eq-preset-chip" data-preset="rock">Rock</span>
          <span class="eq-preset-chip" data-preset="pop">Pop</span>
          <span class="eq-preset-chip" data-preset="jazz">Jazz</span>
          <span class="eq-preset-chip" data-preset="vocal">Vocal</span>
          <span class="eq-preset-chip" data-preset="acoustic">Acoustic</span>
        </div>

        <!-- 10 Vertical EQ Sliders -->
        <div class="eq-sliders-grid">
          <?php
            $freqs = ['32Hz', '64Hz', '125Hz', '250Hz', '500Hz', '1kHz', '2kHz', '4kHz', '8kHz', '16kHz'];
            foreach ($freqs as $i => $f):
          ?>
            <div class="eq-col">
              <span class="eq-gain-val" id="eq-val-<?= $i ?>">0dB</span>
              <input type="range" class="eq-vertical-slider" id="eq-slider-<?= $i ?>" min="-12" max="12" step="0.5" value="0">
              <span class="eq-freq-label"><?= $f ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Bass & Treble Sub-Boosters & Normalizer -->
        <div class="eq-booster-row" style="grid-template-columns: 1fr 1fr 1fr;">
          <div class="booster-card">
            <div class="booster-label">
              <span>Deep Sub-Bass</span>
              <span id="bass-boost-val" style="color: var(--accent-primary);">+0dB</span>
            </div>
            <input type="range" id="bass-boost-slider" min="0" max="15" step="1" value="0" style="accent-color: var(--accent-primary); width: 100%;">
          </div>
          <div class="booster-card">
            <div class="booster-label">
              <span>High Air Treble</span>
              <span id="treble-boost-val" style="color: var(--accent-primary);">0dB</span>
            </div>
            <input type="range" id="treble-boost-slider" min="-10" max="10" step="1" value="0" style="accent-color: var(--accent-primary); width: 100%;">
          </div>
          <div class="booster-card" style="justify-content: space-between;">
            <div class="booster-label">
              <span>Volume Leveling</span>
              <span id="normalizer-status-val" style="color: #10b981; font-weight: 700;">ON</span>
            </div>
            <button id="toggle-normalizer-btn" class="btn-subtle-scan" style="width: 100%; padding: 4px 8px; font-size: 0.78rem; text-align: center; justify-content: center; background: rgba(16, 185, 129, 0.15); border-color: #10b981; color: #10b981;">
              Auto Leveling Aktif
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ==========================================
       IMPORT & DOWNLOAD MUSIC MODAL (YouTube + Upload)
       ========================================== -->
  <div class="modal-overlay" id="upload-modal">
    <div class="modal-card" style="max-width: 580px;">
      <div class="modal-header">
        <h3 class="modal-title">Tambah Musik ke Koleksi</h3>
        <button class="modal-close-btn" id="upload-close-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Tab Switcher -->
      <div class="modal-tabs" style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid var(--border-subtle); padding-bottom: 12px;">
        <button class="modal-tab-btn active" id="tab-btn-yt" data-target="panel-yt" style="display: flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: var(--radius-full); background: var(--accent-subtle); border: 1px solid var(--accent-primary); color: var(--accent-primary); font-weight: 600; font-size: 0.85rem; cursor: pointer;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.485 17.306c-.215.353-.674.464-1.026.25-2.812-1.718-6.352-2.107-10.52-1.155-.403.092-.807-.16-.899-.562-.093-.404.16-.807.563-.9 4.568-1.042 8.48-.6 11.632 1.34.352.215.464.674.25 1.027zm1.464-3.255c-.27.44-.848.58-1.288.31-3.22-1.98-8.127-2.55-11.933-1.393-.496.15-1.025-.13-1.176-.625-.15-.496.13-1.026.626-1.177 4.35-1.32 9.75-.688 13.46 1.597.44.27.58.847.31 1.288zm.126-3.39c-3.86-2.293-10.233-2.505-13.914-1.387-.59.18-1.22-.16-1.4-.75-.18-.59.16-1.22.75-1.4 4.23-1.284 11.272-1.04 15.698 1.587.53.315.705 1.004.39 1.534-.315.53-1.004.706-1.524.416z"/>
          </svg>
          <span>Spotify & YouTube</span>
        </button>
        <button class="modal-tab-btn" id="tab-btn-upload" data-target="panel-upload" style="display: flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: var(--radius-full); background: var(--bg-surface); border: 1px solid var(--border-subtle); color: var(--text-secondary); font-weight: 600; font-size: 0.85rem; cursor: pointer;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="17 8 12 3 7 8"></polyline>
            <line x1="12" y1="3" x2="12" y2="15"></line>
          </svg>
          <span>Upload File Lokal</span>
        </button>
      </div>

      <!-- Panel 1: Spotify & YouTube Downloader -->
      <div id="panel-yt" class="modal-tab-panel">
        <div class="form-group">
          <label class="form-label">Link Spotify (Playlist / Album / Lagu) atau Link YouTube</label>
          <div style="display: flex; gap: 8px;">
            <input type="text" id="yt-url-input" class="form-control" placeholder="Tempel link Spotify Playlist/Lagu, YouTube URL, atau ketik Judul..." />
            <button class="btn-primary-play" id="yt-start-download-btn" style="border-radius: var(--radius-sm); white-space: nowrap; padding: 10px 18px; font-size: 0.88rem;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
              </svg>
              <span>Download MP3</span>
            </button>
          </div>
          <small style="color: var(--text-tertiary); font-size: 0.78rem; margin-top: 6px; display: block;">
            Mendukung link <strong>Spotify Playlist / Album</strong> (otomatis download seluruh isi playlist berurutan), YouTube Video/Playlist, dan pencarian instan 320kbps.
          </small>
        </div>

        <!-- Download Status Indicator (Single Track) -->
        <div id="yt-status-box" style="display: none; background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 14px; margin-top: 14px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <div class="sound-waves-indicator" id="yt-spinner">
              <div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div>
            </div>
            <span id="yt-status-text" style="font-size: 0.88rem; color: var(--text-primary); font-weight: 500;">Sedang memproses audio dari server...</span>
          </div>
        </div>

        <!-- Batch Playlist Importer Container -->
        <div id="batch-playlist-container" style="display: none; margin-top: 16px; background: var(--bg-surface-elevated); border: 1px solid var(--border-medium); border-radius: var(--radius-md); padding: 16px;">
          <!-- Playlist Header Preview -->
          <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 14px;">
            <img id="batch-playlist-cover" src="assets/sample_covers/placeholder.svg" alt="Playlist Cover" style="width: 56px; height: 56px; border-radius: var(--radius-sm); object-fit: cover; border: 1px solid var(--border-subtle);" />
            <div style="flex: 1; min-width: 0;">
              <div style="display: flex; align-items: center; gap: 8px;">
                <span id="batch-platform-badge" style="font-size: 0.68rem; font-weight: 700; text-transform: uppercase; padding: 2px 8px; border-radius: var(--radius-full); background: #1db954; color: #000;">Spotify</span>
                <span id="batch-total-count" style="font-size: 0.78rem; color: var(--text-secondary); font-family: var(--font-mono);">0 Lagu</span>
              </div>
              <h4 id="batch-playlist-title" style="font-size: 1rem; font-weight: 700; color: var(--text-primary); margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Nama Playlist</h4>
            </div>
          </div>

          <!-- Options -->
          <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; padding: 8px 10px; background: var(--bg-surface); border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
            <input type="checkbox" id="batch-auto-playlist" checked style="accent-color: var(--accent-primary); width: 16px; height: 16px; cursor: pointer;" />
            <label for="batch-auto-playlist" style="font-size: 0.82rem; color: var(--text-primary); cursor: pointer; user-select: none;">
              Otomatis buat Playlist baru di Aura Music dengan nama ini
            </label>
          </div>

          <!-- Progress Bar -->
          <div id="batch-progress-wrap" style="display: none; margin-bottom: 12px;">
            <div style="display: flex; justify-content: space-between; font-size: 0.78rem; margin-bottom: 6px;">
              <span id="batch-progress-status" style="color: var(--accent-primary); font-weight: 600;">Mendownload antrean...</span>
              <span id="batch-progress-percent" style="color: var(--text-secondary); font-family: var(--font-mono);">0%</span>
            </div>
            <div style="width: 100%; height: 6px; background: var(--bg-surface); border-radius: var(--radius-full); overflow: hidden; border: 1px solid var(--border-subtle);">
              <div id="batch-progress-bar-fill" style="width: 0%; height: 100%; background: linear-gradient(90deg, var(--accent-primary), #10b981); transition: width 0.3s ease;"></div>
            </div>
          </div>

          <!-- Tracklist Scroll Area -->
          <div id="batch-tracklist" style="max-height: 200px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; padding-right: 4px; margin-bottom: 14px;">
            <!-- Dynamic Track Items -->
          </div>

          <!-- Batch Action Buttons -->
          <div style="display: flex; justify-content: flex-end; gap: 8px;">
            <button id="batch-cancel-btn" class="btn-ghost" style="padding: 8px 16px; font-size: 0.84rem; border-radius: var(--radius-sm);">
              Batal
            </button>
            <button id="batch-start-all-btn" class="btn-primary-play" style="padding: 8px 20px; font-size: 0.86rem; border-radius: var(--radius-sm);">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
              </svg>
              <span>Download Semua Lagu</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Panel 2: File Upload -->
      <div id="panel-upload" class="modal-tab-panel" style="display: none;">
        <div class="form-group">
          <label class="form-label">File Audio (MP3, FLAC, WAV, M4A, OGG)</label>
          <div class="dropzone-box" id="dropzone-box">
            <svg class="dropzone-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
              <polyline points="17 8 12 3 7 8"></polyline>
              <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Klik atau drag & drop file musik ke sini</p>
            <input type="file" id="audio-file-input" accept="audio/*,.mp3,.flac,.wav,.ogg,.m4a,.aac" multiple style="display: none;">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">File Lirik Sinkron (.LRC) - Opsional</label>
          <input type="file" id="lyrics-file-input" class="form-control" accept=".lrc,.txt">
          <small style="color: var(--text-tertiary); font-size: 0.75rem;">Beri nama file .lrc sama persis dengan nama file lagunya</small>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
          <button class="btn-primary-play" id="submit-upload-btn" style="width: 100%; justify-content: center; border-radius: var(--radius-sm);">
            Mulai Upload File
          </button>
        </div>
      </div>

      <!-- Folder Direct Copy Note -->
      <div style="margin-top: 20px; padding-top: 14px; border-top: 1px solid var(--border-subtle); display: flex; flex-direction: column; gap: 6px;">
        <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.05em;">Lokasi Penyimpanan Musik Server:</span>
        <div style="background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 10px 12px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
          <code id="server-songs-path" style="font-family: var(--font-mono); font-size: 0.76rem; color: var(--accent-primary); word-break: break-all;"><?= htmlspecialchars(realpath(__DIR__ . '/songs') ?: (__DIR__ . DIRECTORY_SEPARATOR . 'songs')) ?></code>
          <button class="btn-subtle-scan" id="modal-rescan-btn" style="flex-shrink: 0; padding: 5px 12px;">
            <span>Scan Folder</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ==========================================
       CREATE PLAYLIST MODAL
       ========================================== -->
  <div class="modal-overlay" id="new-playlist-modal">
    <div class="modal-card" style="max-width: 440px;">
      <div class="modal-header">
        <h3 class="modal-title">Buat Playlist Baru</h3>
        <button class="modal-close-btn" id="new-playlist-close-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="form-group">
        <label class="form-label">Nama Playlist</label>
        <input type="text" id="playlist-name-input" class="form-control" placeholder="Contoh: Midnight Vibes" autofocus>
      </div>
      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
        <button class="btn-primary-play" id="save-playlist-btn" style="width: 100%; justify-content: center; border-radius: var(--radius-sm);">
          Simpan Playlist
        </button>
      </div>
    </div>
  </div>

  <!-- ==========================================
       ADD TO PLAYLIST MODAL
       ========================================== -->
  <div class="modal-overlay" id="add-to-playlist-modal">
    <div class="modal-card" style="max-width: 460px;">
      <div class="modal-header">
        <h3 class="modal-title">Tambahkan ke Playlist</h3>
        <button class="modal-close-btn" id="add-to-playlist-close-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <p id="target-song-name" style="color: var(--accent-primary); font-weight: 600; font-size: 0.9rem; margin-bottom: 16px;"></p>
      
      <div id="playlist-selection-list" style="display: flex; flex-direction: column; gap: 8px; max-height: 260px; overflow-y: auto; margin-bottom: 16px;">
        <!-- Dynamic Playlist Choice Buttons -->
      </div>

      <div style="border-top: 1px solid var(--border-subtle); padding-top: 14px; display: flex; gap: 8px;">
        <input type="text" id="quick-create-pl-input" class="form-control" placeholder="Nama playlist baru..." />
        <button class="btn-primary-play" id="quick-create-pl-btn" style="white-space: nowrap; padding: 8px 16px; border-radius: var(--radius-sm); font-size: 0.85rem;">
          + Buat & Tambah
        </button>
      </div>
    </div>
  </div>

  <!-- ==========================================
       EDIT SONG METADATA MODAL
       ========================================== -->
  <div class="modal-overlay" id="edit-metadata-modal">
    <div class="modal-card" style="max-width: 480px;">
      <div class="modal-header">
        <h3 class="modal-title">Edit Info Lagu</h3>
        <button class="modal-close-btn" id="edit-metadata-close-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <input type="hidden" id="edit-song-id" />
      
      <!-- Auto Enrich Metadata Button -->
      <div style="margin-bottom: 14px; background: rgba(59, 130, 246, 0.08); border: 1px dashed rgba(59, 130, 246, 0.3); border-radius: var(--radius-sm); padding: 10px 12px; display: flex; flex-direction: column; gap: 8px;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
          <span style="font-size: 0.78rem; color: var(--text-secondary);">Cari otomatis cover HD, album & tahun rilis:</span>
          <button id="auto-enrich-btn" class="btn-subtle-scan" style="padding: 5px 12px; font-size: 0.78rem; border-color: var(--accent-primary); color: #60a5fa; background: rgba(37, 99, 235, 0.15);">
            <span>🪄 Auto-Enrich</span>
          </button>
        </div>
        <div id="enrich-results-wrap" style="display: none; max-height: 140px; overflow-y: auto; flex-direction: column; gap: 6px; margin-top: 4px;">
          <!-- Dynamic Enrich Match Cards -->
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Judul Lagu</label>
        <input type="text" id="edit-song-title" class="form-control" placeholder="Judul lagu..." />
      </div>
      <div class="form-group">
        <label class="form-label">Artis / Penyanyi</label>
        <input type="text" id="edit-song-artist" class="form-control" placeholder="Nama artis..." />
      </div>
      <div class="form-group">
        <label class="form-label">Album</label>
        <input type="text" id="edit-song-album" class="form-control" placeholder="Nama album..." />
      </div>
      <div class="form-group">
        <label class="form-label">Genre</label>
        <input type="text" id="edit-song-genre" class="form-control" placeholder="Pop, Rock, Anime, Dangdut, dll..." />
      </div>
      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
        <button class="btn-primary-play" id="save-metadata-btn" style="width: 100%; justify-content: center; border-radius: var(--radius-sm);">
          Simpan Perubahan
        </button>
      </div>
    </div>
  </div>

  <!-- ==========================================
       AUDIO TRIMMER & RINGTONE MAKER MODAL
       ========================================== -->
  <div class="modal-overlay" id="trimmer-modal">
    <div class="modal-card" style="max-width: 520px;">
      <div class="modal-header">
        <h3 class="modal-title" style="display: flex; align-items: center; gap: 8px;">
          <span>✂️</span> Audio Trimmer & Ringtone Maker
        </h3>
        <button class="modal-close-btn" id="trimmer-close-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <p id="trimmer-song-title" style="font-weight: 700; color: var(--accent-primary); font-size: 0.95rem; margin-bottom: 12px;"></p>
      
      <!-- Visual Timeline Preview Bar -->
      <div style="background: var(--bg-surface); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); margin-bottom: 16px;">
        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 8px;">
          <span>Mulai: <b id="trim-start-display" style="color: var(--accent-primary); font-family: var(--font-mono);">0:00</b></span>
          <span>Durasi: <b id="trim-dur-display" style="color: var(--accent-amber); font-family: var(--font-mono);">30s</b></span>
          <span>Selesai: <b id="trim-end-display" style="color: var(--accent-emerald); font-family: var(--font-mono);">0:30</b></span>
        </div>

        <div style="margin-bottom: 12px;">
          <label style="font-size: 0.75rem; color: var(--text-tertiary); display: block; margin-bottom: 4px;">Titik Awal Potongan (Start Time):</label>
          <input type="range" id="trim-start-slider" min="0" max="100" step="1" value="0" style="width: 100%; accent-color: var(--accent-primary);">
        </div>

        <div>
          <label style="font-size: 0.75rem; color: var(--text-tertiary); display: block; margin-bottom: 4px;">Durasi Potongan (10s - 60s):</label>
          <input type="range" id="trim-dur-slider" min="10" max="60" step="5" value="30" style="width: 100%; accent-color: var(--accent-amber);">
        </div>
      </div>

      <!-- Action Buttons -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
        <button class="btn-secondary" id="trim-preview-btn" style="justify-content: center; padding: 10px 14px; font-size: 0.86rem; border-radius: var(--radius-sm);">
          <span>▶ Putar Potongan</span>
        </button>
        <button class="btn-primary-play" id="trim-download-btn" style="justify-content: center; padding: 10px 14px; font-size: 0.86rem; border-radius: var(--radius-sm);">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
          </svg>
          <span>Download Ringtone</span>
        </button>
      </div>
    </div>
  </div>

  <!-- ==========================================
       INTERACTIVE LRC LYRIC MAKER STUDIO MODAL
       ========================================== -->
  <div class="modal-overlay" id="lrc-maker-modal">
    <div class="modal-card" style="max-width: 600px;">
      <div class="modal-header">
        <h3 class="modal-title" style="display: flex; align-items: center; gap: 8px;">
          <span>✍️</span> Studio Pembuat Lirik Sinkron (.LRC)
        </h3>
        <button class="modal-close-btn" id="lrc-maker-close-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <p id="lrc-maker-song-title" style="font-weight: 700; color: var(--accent-primary); font-size: 0.9rem; margin-bottom: 12px;"></p>

      <!-- Step 1: Input Textarea -->
      <div id="lrc-step-input">
        <label class="form-label">Tempel Teks Lirik Mentah (1 baris per bait):</label>
        <textarea id="lrc-raw-input" class="form-control" rows="8" placeholder="Baris lirik pertama&#10;Baris lirik kedua&#10;Baris lirik ketiga..." style="font-family: inherit; font-size: 0.88rem; line-height: 1.5; resize: vertical;"></textarea>
        <button class="btn-primary-play" id="lrc-start-sync-btn" style="margin-top: 14px; width: 100%; justify-content: center; border-radius: var(--radius-sm);">
          Mulai Sinkronisasi Lagu 🎙️
        </button>
      </div>

      <!-- Step 2: Live Tapper Studio -->
      <div id="lrc-step-tapper" style="display: none;">
        <div style="background: var(--bg-surface); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); margin-bottom: 14px; max-height: 200px; overflow-y: auto;" id="lrc-tapper-lines-container">
          <!-- Dynamic Stepper Lines -->
        </div>

        <div style="display: flex; flex-direction: column; align-items: center; gap: 10px; margin-bottom: 14px;">
          <button id="lrc-tap-button" style="width: 100%; height: 56px; border-radius: var(--radius-md); background: linear-gradient(135deg, var(--accent-primary), #6366f1); border: none; color: #fff; font-weight: 800; font-size: 1.05rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 4px 20px var(--accent-subtle); transition: var(--transition-smooth);">
            <span>⏱️ [TAP / TEKAN SPASI] UNTUK BARIS INI</span>
          </button>
          <span style="font-size: 0.75rem; color: var(--text-tertiary);">Tekan tombol di atas atau tombol SPASI di keyboard saat penyanyi menyanyikan baris berikutnya</span>
        </div>

        <div style="display: flex; justify-content: space-between; gap: 10px;">
          <button class="btn-secondary" id="lrc-reset-btn" style="padding: 8px 16px; font-size: 0.82rem; border-radius: var(--radius-sm);">
            Ulangi Dari Awal
          </button>
          <button class="btn-primary-play" id="lrc-save-btn" style="padding: 8px 20px; font-size: 0.86rem; border-radius: var(--radius-sm);">
            💾 Simpan Lirik Sinkron
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Mobile Song Actions Bottom Sheet Modal -->
  <div class="modal-overlay song-actions-overlay" id="song-actions-modal">
    <div class="modal-card song-actions-card" id="song-actions-card">
      <div class="song-actions-header">
        <img class="song-actions-thumb" id="song-actions-thumb" src="assets/sample_covers/placeholder.svg" alt="Cover" />
        <div class="song-actions-meta">
          <div class="song-actions-title" id="song-actions-title">Judul Lagu</div>
          <div class="song-actions-artist" id="song-actions-artist">Nama Artis</div>
        </div>
        <button class="modal-close-btn" id="song-actions-close-btn" title="Tutup">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="song-actions-list" id="song-actions-list">
        <!-- Rendered dynamically by JS -->
      </div>
    </div>
  </div>

  <!-- Toast Notification Container -->
  <div class="toast-container" id="toast-container"></div>


  <!-- Audio Library Health Doctor Modal -->
  <div class="modal-overlay" id="health-modal">
    <div class="modal-card" style="max-width: 520px;">
      <div class="modal-header">
        <h3 class="modal-title">🩺 Cek Kesehatan Library Lagu</h3>
        <button class="modal-close-btn" id="health-close-btn">&times;</button>
      </div>
      <div class="modal-body" style="padding: 10px 0 0;">
        <div id="health-status-wrap" style="text-align: center; padding: 24px 0;">
          <div style="font-size: 2.5rem; margin-bottom: 8px;">⏳</div>
          <p style="color: var(--text-secondary); font-size: 0.9rem;">Memeriksa integritas file audio di server...</p>
        </div>
        <div id="health-results-wrap" style="display: none;">
          <div style="display: flex; gap: 10px; margin-bottom: 16px;">
            <div style="flex: 1; background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.2); border-radius: 10px; padding: 12px; text-align: center;">
              <div id="health-total-val" style="font-size: 1.3rem; font-weight: 700; color: #38bdf8;">0</div>
              <div style="font-size: 0.72rem; color: var(--text-secondary); margin-top: 2px;">Total Lagu</div>
            </div>
            <div style="flex: 1; background: rgba(34, 197, 94, 0.08); border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 10px; padding: 12px; text-align: center;">
              <div id="health-healthy-val" style="font-size: 1.3rem; font-weight: 700; color: #22c55e;">0</div>
              <div style="font-size: 0.72rem; color: var(--text-secondary); margin-top: 2px;">Sehat</div>
            </div>
            <div style="flex: 1; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 10px; padding: 12px; text-align: center;">
              <div id="health-broken-val" style="font-size: 1.3rem; font-weight: 700; color: #ef4444;">0</div>
              <div style="font-size: 0.72rem; color: var(--text-secondary); margin-top: 2px;">Rusak / Korup</div>
            </div>
          </div>
          <div id="health-broken-list-wrap" style="display: none; margin-bottom: 16px;">
            <p style="font-size: 0.8rem; font-weight: 600; color: #ef4444; margin-bottom: 6px;">Daftar Lagu Bermasalah:</p>
            <div id="health-broken-list" style="max-height: 160px; overflow-y: auto; border: 1px solid var(--border-subtle); border-radius: 8px; padding: 8px; background: rgba(0,0,0,0.2);"></div>
          </div>
          <div id="health-all-good-msg" style="display: none; text-align: center; padding: 12px; color: #22c55e; font-size: 0.9rem; font-weight: 600;">
            🎉 Semua lagu di koleksi Anda sehat dan siap diputar!
          </div>
          <button id="health-repair-btn" class="btn-primary" style="width: 100%; padding: 12px; font-size: 0.9rem; border-radius: 10px; display: none;">
            ⚡ Download Ulang & Perbaiki Semua Lagu Rusak
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Keyboard Shortcuts Cheatsheet Modal -->
  <div class="modal-overlay" id="shortcuts-modal">
    <div class="modal-card" style="max-width: 480px;">
      <div class="modal-header">
        <h3 class="modal-title">⌨️ Keyboard Shortcuts</h3>
        <button class="modal-close-btn" id="shortcuts-close-btn">&times;</button>
      </div>
      <div class="modal-body" style="padding: 10px 0 0;">
        <div class="shortcuts-grid">
          <div class="shortcut-item"><kbd>Space</kbd><span>Play / Pause</span></div>
          <div class="shortcut-item"><kbd>N</kbd><span>Next Track</span></div>
          <div class="shortcut-item"><kbd>P</kbd><span>Previous Track</span></div>
          <div class="shortcut-item"><kbd>←</kbd><span>Mundur 5 detik</span></div>
          <div class="shortcut-item"><kbd>→</kbd><span>Maju 5 detik</span></div>
          <div class="shortcut-item"><kbd>↑</kbd><span>Volume +5%</span></div>
          <div class="shortcut-item"><kbd>↓</kbd><span>Volume -5%</span></div>
          <div class="shortcut-item"><kbd>M</kbd><span>Mute / Unmute</span></div>
          <div class="shortcut-item"><kbd>S</kbd><span>Toggle Shuffle</span></div>
          <div class="shortcut-item"><kbd>R</kbd><span>Toggle Repeat</span></div>
          <div class="shortcut-item"><kbd>L</kbd><span>Immersive Lyrics</span></div>
          <div class="shortcut-item"><kbd>Q</kbd><span>Toggle Queue Panel</span></div>
          <div class="shortcut-item"><kbd>F</kbd><span>Fullscreen Mode</span></div>
          <div class="shortcut-item"><kbd>?</kbd><span>Shortcut Cheatsheet</span></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Application Scripts -->
  <script src="assets/js/ambient-color.js"></script>
  <script src="assets/js/audio-core.js"></script>
  <script src="assets/js/visualizer.js"></script>
  <script src="assets/js/lyrics.js"></script>
  <script src="assets/js/offline-storage.js"></script>
  <script src="assets/js/waveform.js"></script>
  <script src="assets/js/playlist.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>
