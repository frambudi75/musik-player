/**
 * Synchronized Lyrics Engine (.LRC format parser, timing offset, & real-time smooth scroller)
 */

class LyricsEngine {
  constructor() {
    this.lyrics = []; // Array of { time: seconds, text: string }
    this.currentIndex = -1;
    this.offset = 0; // seconds (positive = earlier, negative = later)
    this.containerEl = null;
    this.immersiveContainerEl = null;
    this.isAutoScrollEnabled = true;
    this.userScrollTimeout = null;
    this.onActiveLineChange = null;
  }

  setContainers(mainContainer, immersiveContainer) {
    this.containerEl = mainContainer;
    this.immersiveContainerEl = immersiveContainer;
  }

  setOffset(seconds) {
    this.offset = Math.round(seconds * 10) / 10;
  }

  adjustOffset(delta) {
    this.offset = Math.round((this.offset + delta) * 10) / 10;
    return this.offset;
  }

  resetOffset() {
    this.offset = 0;
    return this.offset;
  }

  getCurrentLyricText() {
    if (this.currentIndex >= 0 && this.currentIndex < this.lyrics.length) {
      return this.lyrics[this.currentIndex].text;
    }
    return '';
  }

  /**
   * Parse LRC text into structured timestamps
   */
  parseLRC(lrcText) {
    this.lyrics = [];
    this.currentIndex = -1;
    this.offset = 0;

    if (!lrcText || typeof lrcText !== 'string') {
      return this.lyrics;
    }

    const lines = lrcText.split('\n');
    const timeRegex = /\[(\d{2}):(\d{2})(?:\.(\d{2,3}))?\]/g;

    for (let rawLine of lines) {
      rawLine = rawLine.trim();
      if (!rawLine) continue;

      let match;
      const timestamps = [];
      while ((match = timeRegex.exec(rawLine)) !== null) {
        const min = parseInt(match[1], 10);
        const sec = parseInt(match[2], 10);
        const ms = match[3] ? parseInt(match[3].padEnd(3, '0').substring(0, 3), 10) : 0;
        const totalSec = min * 60 + sec + ms / 1000;
        timestamps.push(totalSec);
      }

      const text = rawLine.replace(/\[\d{2}:\d{2}(?:\.\d{2,3})?\]/g, '').trim();

      if (timestamps.length > 0 && text) {
        timestamps.forEach((t) => {
          this.lyrics.push({ time: t, text });
        });
      }
    }

    // Sort by timestamp
    this.lyrics.sort((a, b) => a.time - b.time);
    return this.lyrics;
  }

  /**
   * Load and parse from URL or raw string
   */
  async loadLyrics(sourceUrlOrText) {
    this.offset = 0;
    if (!sourceUrlOrText) {
      this.lyrics = [];
      this.render();
      return;
    }

    if (sourceUrlOrText.startsWith('http') || sourceUrlOrText.includes('/') || sourceUrlOrText.endsWith('.lrc')) {
      try {
        const res = await fetch(sourceUrlOrText);
        if (res.ok) {
          const text = await res.text();
          this.parseLRC(text);
        } else {
          this.lyrics = [];
        }
      } catch (e) {
        console.warn('Failed to load lyrics file:', e);
        this.lyrics = [];
      }
    } else {
      this.parseLRC(sourceUrlOrText);
    }

    this.render();
  }

  /**
   * Render lyrics into DOM
   */
  render() {
    const renderTo = (container, isImmersive = false) => {
      if (!container) return;
      container.innerHTML = '';

      if (this.lyrics.length === 0) {
        container.innerHTML = `
          <div class="lyric-empty-state">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M9 18V5l12-2v13"></path>
              <circle cx="6" cy="18" r="3"></circle>
              <circle cx="18" cy="16" r="3"></circle>
            </svg>
            <p>Tidak ada lirik sinkron lokal</p>
            <button class="btn-fetch-lyrics" id="fetch-online-lyrics-btn">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <span>Cari Lirik Online</span>
            </button>
          </div>
        `;
        const btn = container.querySelector('#fetch-online-lyrics-btn');
        if (btn && window.fetchOnlineLyricsForCurrentSong) {
          btn.onclick = () => window.fetchOnlineLyricsForCurrentSong();
        }
        return;
      }

      this.lyrics.forEach((item, index) => {
        const lineEl = document.createElement('div');
        lineEl.className = 'lyric-line';
        lineEl.dataset.index = index;
        lineEl.dataset.time = item.time;
        lineEl.textContent = item.text;

        // Click line to seek
        lineEl.addEventListener('click', () => {
          window.AudioCore.seek(Math.max(0, item.time - this.offset));
        });

        container.appendChild(lineEl);
      });
    };

    renderTo(this.containerEl, false);
    renderTo(this.immersiveContainerEl, true);
    if (this.lyrics.length === 0 && this.onActiveLineChange) {
      this.onActiveLineChange('');
    }
  }

  /**
   * Sync active line with current audio playback time
   */
  updateTime(currentTime) {
    if (this.lyrics.length === 0) return;

    const adjustedTime = currentTime + this.offset;
    let activeIdx = -1;
    for (let i = 0; i < this.lyrics.length; i++) {
      if (adjustedTime >= this.lyrics[i].time - 0.25) {
        activeIdx = i;
      } else {
        break;
      }
    }

    if (activeIdx !== this.currentIndex && activeIdx !== -1) {
      this.currentIndex = activeIdx;
      this.highlightActiveLine(activeIdx);
    }
  }

  highlightActiveLine(index) {
    const activeText = this.lyrics[index]?.text || '';
    if (this.onActiveLineChange) {
      this.onActiveLineChange(activeText, index);
    }

    const applyHighlight = (container) => {
      if (!container) return;
      const allLines = container.querySelectorAll('.lyric-line');
      allLines.forEach((el, i) => {
        if (i === index) {
          el.classList.add('active');
          if (this.isAutoScrollEnabled) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        } else {
          el.classList.remove('active');
        }
      });
    };

    applyHighlight(this.containerEl);
    applyHighlight(this.immersiveContainerEl);
  }
}

window.LyricsEngine = new LyricsEngine();
