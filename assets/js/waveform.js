/**
 * Aura Music - Interactive Acoustic Waveform Scrubber
 * Renders SoundCloud / DJ style peak waveform bars with seekable progress and hover timestamp
 */

class WaveformScrubber {
  constructor(canvasElement, options = {}) {
    this.canvas = canvasElement;
    this.ctx = canvasElement ? canvasElement.getContext('2d') : null;
    this.barsCount = options.barsCount || 72;
    this.peaks = [];
    this.progress = 0; // 0.0 to 1.0
    this.hoverRatio = null; // null or 0.0 to 1.0
    this.onSeek = options.onSeek || null;
    this.currentSongId = null;

    if (this.canvas) {
      this.initEvents();
      this.resize();
    }
  }

  resize() {
    if (!this.canvas) return;
    const rect = this.canvas.getBoundingClientRect();
    const dpr = window.devicePixelRatio || 1;
    this.canvas.width = Math.max(rect.width * dpr, 200 * dpr);
    this.canvas.height = Math.max(rect.height * dpr, 24 * dpr);
    if (this.ctx) {
      this.ctx.scale(dpr, dpr);
    }
    this.draw();
  }

  generateDeterministicPeaks(songId, count = 72) {
    // Generate organic-looking musical waveform peaks based on song ID hash
    const peaks = [];
    let hash = 0;
    const str = String(songId || 'aura_default_seed');
    for (let i = 0; i < str.length; i++) {
      hash = ((hash << 5) - hash) + str.charCodeAt(i);
      hash |= 0;
    }

    let currentVal = 0.45;
    for (let i = 0; i < count; i++) {
      // Perlin-like smoothed variation with chorus beat peaks
      const seed = Math.sin((i * 12.9898 + hash) % 3.14159) * 43758.5453;
      const rand = seed - Math.floor(seed);
      
      // Build musical dynamics (intro low, chorus high, drop)
      const sectionFactor = Math.sin((i / count) * Math.PI); // Envelope curve
      const variation = (rand - 0.5) * 0.4;
      currentVal = Math.max(0.18, Math.min(0.98, currentVal * 0.7 + (sectionFactor * 0.65 + variation) * 0.3));
      peaks.push(currentVal);
    }
    return peaks;
  }

  setTrack(song) {
    if (!song) return;
    this.currentSongId = song.id;
    this.peaks = this.generateDeterministicPeaks(song.id, this.barsCount);
    this.progress = 0;
    this.draw();
  }

  setProgress(ratio) {
    this.progress = Math.max(0, Math.min(1, ratio));
    this.draw();
  }

  initEvents() {
    let isDragging = false;

    const calculateRatio = (e) => {
      const rect = this.canvas.getBoundingClientRect();
      const clientX = e.touches ? e.touches[0].clientX : e.clientX;
      const x = Math.max(0, Math.min(rect.width, clientX - rect.left));
      return x / rect.width;
    };

    this.canvas.addEventListener('mousemove', (e) => {
      this.hoverRatio = calculateRatio(e);
      if (isDragging && this.onSeek) {
        this.progress = this.hoverRatio;
        this.onSeek(this.hoverRatio, true);
      }
      this.draw();
    });

    this.canvas.addEventListener('mouseleave', () => {
      this.hoverRatio = null;
      this.draw();
    });

    this.canvas.addEventListener('mousedown', (e) => {
      isDragging = true;
      const ratio = calculateRatio(e);
      this.progress = ratio;
      if (this.onSeek) this.onSeek(ratio, false);
      this.draw();
    });

    window.addEventListener('mouseup', () => {
      if (isDragging) {
        isDragging = false;
        if (this.onSeek) this.onSeek(this.progress, false);
      }
    });

    // Touch events for mobile
    this.canvas.addEventListener('touchstart', (e) => {
      isDragging = true;
      const ratio = calculateRatio(e);
      this.progress = ratio;
      if (this.onSeek) this.onSeek(ratio, false);
      this.draw();
    }, { passive: true });

    this.canvas.addEventListener('touchmove', (e) => {
      if (isDragging) {
        const ratio = calculateRatio(e);
        this.progress = ratio;
        if (this.onSeek) this.onSeek(ratio, true);
        this.draw();
      }
    }, { passive: true });

    this.canvas.addEventListener('touchend', () => {
      isDragging = false;
    });
  }

  draw() {
    if (!this.canvas || !this.ctx) return;
    const rect = this.canvas.getBoundingClientRect();
    const w = rect.width;
    const h = rect.height;

    this.ctx.clearRect(0, 0, w, h);

    if (this.peaks.length === 0) {
      this.peaks = this.generateDeterministicPeaks('default', this.barsCount);
    }

    const count = this.peaks.length;
    const gap = 2;
    const barWidth = Math.max(2, (w - (count - 1) * gap) / count);
    const midY = h / 2;

    for (let i = 0; i < count; i++) {
      const barRatio = (i + 0.5) / count;
      const isPlayed = barRatio <= this.progress;
      const isHovered = this.hoverRatio !== null && barRatio <= this.hoverRatio;
      const peakVal = this.peaks[i];
      const barHeight = Math.max(4, peakVal * (h - 6));

      const x = i * (barWidth + gap);
      const y = midY - barHeight / 2;

      // Color selection
      if (isPlayed) {
        // Glowing cyan-blue gradient for played portion
        const grad = this.ctx.createLinearGradient(x, y, x, y + barHeight);
        grad.addColorStop(0, '#38bdf8');
        grad.addColorStop(1, '#2563eb');
        this.ctx.fillStyle = grad;
      } else if (isHovered) {
        this.ctx.fillStyle = 'rgba(147, 197, 253, 0.7)';
      } else {
        this.ctx.fillStyle = 'rgba(255, 255, 255, 0.22)';
      }

      // Draw rounded bar
      this.drawRoundedRect(this.ctx, x, y, barWidth, barHeight, Math.min(barWidth / 2, 2));
    }
  }

  drawRoundedRect(ctx, x, y, width, height, radius) {
    ctx.beginPath();
    ctx.moveTo(x + radius, y);
    ctx.lineTo(x + width - radius, y);
    ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
    ctx.lineTo(x + width, y + height - radius);
    ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
    ctx.lineTo(x + radius, y + height);
    ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
    ctx.lineTo(x, y + radius);
    ctx.quadraticCurveTo(x, y, x + radius, y);
    ctx.closePath();
    ctx.fill();
  }
}

window.WaveformScrubber = WaveformScrubber;
