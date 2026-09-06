/**
 * Precision Audio Visualizer
 * High-performance HTML5 Canvas rendering for live audio spectrum & wave data.
 * Pure editorial minimalism, no cheesy slop graphics.
 */

class AudioVisualizer {
  constructor(canvasElement) {
    this.canvas = canvasElement;
    this.ctx = this.canvas ? this.canvas.getContext('2d') : null;
    this.mode = 'bars'; // 'bars', 'wave', 'radial', 'orb', 'matrix'
    this.animationId = null;
    this.isRunning = false;

    // Particle state for Orb mode
    this.orbParticles = [];
    for (let i = 0; i < 40; i++) {
      this.orbParticles.push({
        angle: Math.random() * Math.PI * 2,
        dist: 20 + Math.random() * 60,
        speed: (Math.random() * 0.02 + 0.008) * (Math.random() > 0.5 ? 1 : -1),
        size: Math.random() * 3 + 1.5,
        alpha: Math.random() * 0.7 + 0.3,
        hue: Math.random() > 0.5 ? 210 : 280
      });
    }

    // Peak hold physics for Matrix mode
    this.peakCaps = [];
    this.peakSpeeds = [];

    this.initCanvasSize();
    window.addEventListener('resize', () => this.initCanvasSize());
  }

  setCanvas(canvasElement) {
    this.canvas = canvasElement;
    if (this.canvas) {
      this.ctx = this.canvas.getContext('2d');
      this.initCanvasSize();
    }
  }

  initCanvasSize() {
    if (!this.canvas) return;
    const rect = this.canvas.getBoundingClientRect();
    const dpr = window.devicePixelRatio || 1;
    this.canvas.width = (rect.width || 320) * dpr;
    this.canvas.height = (rect.height || 220) * dpr;
    if (this.ctx) {
      this.ctx.scale(dpr, dpr);
    }
    this.width = rect.width || 320;
    this.height = rect.height || 220;
  }

  setMode(modeName) {
    this.mode = modeName;
  }

  start() {
    if (this.isRunning) return;
    this.isRunning = true;
    this.render();
  }

  stop() {
    this.isRunning = false;
    if (this.animationId) {
      cancelAnimationFrame(this.animationId);
      this.animationId = null;
    }
    if (this.ctx) {
      this.ctx.clearRect(0, 0, this.width, this.height);
    }
  }

  render() {
    if (!this.isRunning) return;

    this.animationId = requestAnimationFrame(() => this.render());

    if (!this.ctx || !this.canvas || this.canvas.offsetParent === null) return;

    this.ctx.clearRect(0, 0, this.width, this.height);

    if (this.mode === 'bars') {
      this.drawBars();
    } else if (this.mode === 'wave') {
      this.drawWave();
    } else if (this.mode === 'radial') {
      this.drawRadial();
    } else if (this.mode === 'orb') {
      this.drawOrb();
    } else if (this.mode === 'matrix') {
      this.drawMatrix();
    }
  }

  drawBars() {
    const data = window.AudioCore.getFrequencyData();
    const count = 36;
    const gap = 3;
    const totalWidth = this.width - 24;
    const barWidth = Math.max(2, (totalWidth - (count - 1) * gap) / count);
    const startX = 12;

    const maxBarHeight = this.height - 30;

    for (let i = 0; i < count; i++) {
      const index = Math.floor(Math.pow(i / count, 1.4) * (data.length * 0.75));
      const value = data[index] || 0;
      const percent = value / 255;
      const barHeight = Math.max(3, percent * maxBarHeight);

      const x = startX + i * (barWidth + gap);
      const y = this.height - 15 - barHeight;

      const grad = this.ctx.createLinearGradient(0, y, 0, this.height - 15);
      grad.addColorStop(0, 'rgba(255, 255, 255, 0.95)');
      grad.addColorStop(0.5, 'rgba(59, 130, 246, 0.85)');
      grad.addColorStop(1, 'rgba(30, 58, 138, 0.4)');

      this.ctx.fillStyle = grad;
      this.ctx.beginPath();
      this.ctx.roundRect(x, y, barWidth, barHeight, [2, 2, 0, 0]);
      this.ctx.fill();
    }
  }

  drawWave() {
    const data = window.AudioCore.getTimeDomainData();
    this.ctx.lineWidth = 2.5;
    this.ctx.strokeStyle = '#3b82f6';
    this.ctx.shadowBlur = 10;
    this.ctx.shadowColor = 'rgba(59, 130, 246, 0.4)';

    this.ctx.beginPath();
    const sliceWidth = this.width / data.length;
    let x = 0;

    for (let i = 0; i < data.length; i++) {
      const v = data[i] / 128.0;
      const y = (v * this.height) / 2;

      if (i === 0) {
        this.ctx.moveTo(x, y);
      } else {
        this.ctx.lineTo(x, y);
      }
      x += sliceWidth;
    }

    this.ctx.stroke();
    this.ctx.shadowBlur = 0;
  }

  drawRadial() {
    const data = window.AudioCore.getFrequencyData();
    const centerX = this.width / 2;
    const centerY = this.height / 2;
    const radius = Math.min(centerX, centerY) * 0.45;
    const barsCount = 48;

    this.ctx.save();
    this.ctx.translate(centerX, centerY);

    // Inner circle
    this.ctx.beginPath();
    this.ctx.arc(0, 0, radius - 4, 0, Math.PI * 2);
    this.ctx.fillStyle = 'rgba(15, 18, 26, 0.8)';
    this.ctx.fill();
    this.ctx.lineWidth = 1.5;
    this.ctx.strokeStyle = 'rgba(255, 255, 255, 0.1)';
    this.ctx.stroke();

    for (let i = 0; i < barsCount; i++) {
      const angle = (i / barsCount) * Math.PI * 2;
      const sampleIndex = Math.floor((i / barsCount) * (data.length * 0.6));
      const val = (data[sampleIndex] || 0) / 255;
      const barLen = Math.max(2, val * (radius * 0.85));

      const x1 = Math.cos(angle) * radius;
      const y1 = Math.sin(angle) * radius;
      const x2 = Math.cos(angle) * (radius + barLen);
      const y2 = Math.sin(angle) * (radius + barLen);

      this.ctx.beginPath();
      this.ctx.moveTo(x1, y1);
      this.ctx.lineTo(x2, y2);
      this.ctx.strokeStyle = `rgba(243, 244, 246, ${Math.max(0.2, val)})`;
      this.ctx.lineWidth = 2.5;
      this.ctx.lineCap = 'round';
      this.ctx.stroke();
    }

    this.ctx.restore();
  }

  drawOrb() {
    const data = window.AudioCore.getFrequencyData();
    const centerX = this.width / 2;
    const centerY = this.height / 2;

    const bass = ((data[1] || 0) + (data[2] || 0) + (data[3] || 0)) / (255 * 3);
    const treble = ((data[20] || 0) + (data[30] || 0)) / (255 * 2);

    const baseRadius = Math.min(centerX, centerY) * 0.28;
    const currentRadius = baseRadius + bass * 24;

    this.ctx.save();
    this.ctx.translate(centerX, centerY);

    // Glowing outer aura rings
    const auraGrad = this.ctx.createRadialGradient(0, 0, currentRadius * 0.5, 0, 0, currentRadius * 1.8);
    auraGrad.addColorStop(0, `rgba(168, 85, 247, ${0.4 + bass * 0.4})`);
    auraGrad.addColorStop(0.6, `rgba(59, 130, 246, ${0.2 + bass * 0.3})`);
    auraGrad.addColorStop(1, 'rgba(0, 0, 0, 0)');

    this.ctx.fillStyle = auraGrad;
    this.ctx.beginPath();
    this.ctx.arc(0, 0, currentRadius * 1.8, 0, Math.PI * 2);
    this.ctx.fill();

    // Solid pulsing core
    const coreGrad = this.ctx.createRadialGradient(0, 0, 0, 0, 0, currentRadius);
    coreGrad.addColorStop(0, '#ffffff');
    coreGrad.addColorStop(0.4, '#38bdf8');
    coreGrad.addColorStop(0.8, '#a855f7');
    coreGrad.addColorStop(1, '#1e1b4b');

    this.ctx.fillStyle = coreGrad;
    this.ctx.shadowColor = '#60a5fa';
    this.ctx.shadowBlur = 15 + bass * 25;
    this.ctx.beginPath();
    this.ctx.arc(0, 0, currentRadius, 0, Math.PI * 2);
    this.ctx.fill();
    this.ctx.shadowBlur = 0;

    // Orbiting particle swarm
    this.orbParticles.forEach((p) => {
      p.angle += p.speed * (1 + bass * 2);
      const dist = currentRadius + p.dist * (0.8 + treble * 0.6);
      const px = Math.cos(p.angle) * dist;
      const py = Math.sin(p.angle) * (dist * 0.6); // elliptical perspective

      this.ctx.fillStyle = `hsla(${p.hue}, 90%, 65%, ${p.alpha * (0.5 + bass * 0.5)})`;
      this.ctx.beginPath();
      this.ctx.arc(px, py, p.size * (1 + bass * 0.5), 0, Math.PI * 2);
      this.ctx.fill();
    });

    this.ctx.restore();
  }

  drawMatrix() {
    const data = window.AudioCore.getFrequencyData();
    const count = 28;
    const gap = 4;
    const totalWidth = this.width - 24;
    const barWidth = Math.max(4, (totalWidth - (count - 1) * gap) / count);
    const startX = 12;
    const maxHeight = this.height - 30;

    if (this.peakCaps.length !== count) {
      this.peakCaps = new Array(count).fill(0);
      this.peakSpeeds = new Array(count).fill(0);
    }

    const segmentsCount = 14;
    const segGap = 2;
    const segHeight = (maxHeight - (segmentsCount - 1) * segGap) / segmentsCount;

    for (let i = 0; i < count; i++) {
      const sampleIdx = Math.floor(Math.pow(i / count, 1.3) * (data.length * 0.7));
      const val = (data[sampleIdx] || 0) / 255;
      const targetHeight = val * maxHeight;

      // Peak hold calculation with gravity
      if (targetHeight >= this.peakCaps[i]) {
        this.peakCaps[i] = targetHeight;
        this.peakSpeeds[i] = 0;
      } else {
        this.peakSpeeds[i] += 0.25;
        this.peakCaps[i] = Math.max(0, this.peakCaps[i] - this.peakSpeeds[i]);
      }

      const activeSegments = Math.round(val * segmentsCount);
      const x = startX + i * (barWidth + gap);

      // Draw LED Segments
      for (let s = 0; s < segmentsCount; s++) {
        const segY = this.height - 15 - (s + 1) * (segHeight + segGap);
        const isActive = s < activeSegments;

        if (isActive) {
          // Color grading: Green -> Yellow -> Neon Cyan/Pink on top
          let color = '#10b981';
          if (s > segmentsCount * 0.75) {
            color = '#ec4899';
          } else if (s > segmentsCount * 0.5) {
            color = '#38bdf8';
          } else if (s > segmentsCount * 0.3) {
            color = '#34d399';
          }
          this.ctx.fillStyle = color;
        } else {
          this.ctx.fillStyle = 'rgba(255, 255, 255, 0.04)';
        }

        this.ctx.beginPath();
        this.ctx.roundRect(x, segY, barWidth, segHeight, 1);
        this.ctx.fill();
      }

      // Draw Peak Cap
      if (this.peakCaps[i] > 4) {
        const capY = this.height - 15 - this.peakCaps[i];
        this.ctx.fillStyle = '#f43f5e';
        this.ctx.shadowColor = '#f43f5e';
        this.ctx.shadowBlur = 4;
        this.ctx.beginPath();
        this.ctx.roundRect(x, capY, barWidth, 3, 1);
        this.ctx.fill();
        this.ctx.shadowBlur = 0;
      }
    }
  }
}

/**
 * Spotify Canvas Style Fluid Ambient Waves Visualizer for Immersive Screen
 */
class ImmersiveCanvasVisualizer {
  constructor(canvasElement) {
    this.canvas = canvasElement;
    this.ctx = this.canvas ? this.canvas.getContext('2d') : null;
    this.animationId = null;
    this.isRunning = false;
    this.phase = 0;

    this.initCanvasSize();
    window.addEventListener('resize', () => this.initCanvasSize());
  }

  initCanvasSize() {
    if (!this.canvas) return;
    this.canvas.width = window.innerWidth;
    this.canvas.height = window.innerHeight;
    this.width = window.innerWidth;
    this.height = window.innerHeight;
  }

  start() {
    if (this.isRunning) return;
    this.isRunning = true;
    this.initCanvasSize();
    this.render();
  }

  stop() {
    this.isRunning = false;
    if (this.animationId) {
      cancelAnimationFrame(this.animationId);
      this.animationId = null;
    }
  }

  render() {
    if (!this.isRunning) return;
    this.animationId = requestAnimationFrame(() => this.render());

    if (!this.ctx || !this.canvas) return;
    const parent = document.getElementById('immersive-overlay');
    if (!parent || !parent.classList.contains('open')) return;

    const data = window.AudioCore.getFrequencyData();
    const bass = (data[1] || 0) / 255;
    const mid = (data[10] || 0) / 255;

    this.ctx.clearRect(0, 0, this.width, this.height);
    this.phase += 0.02 + bass * 0.03;

    // Draw 3 layers of smooth fluid glowing sine waves
    const layers = [
      { color: 'rgba(59, 130, 246, 0.25)', amp: 60 + bass * 90, freq: 0.003, speed: 1.0 },
      { color: 'rgba(147, 51, 234, 0.20)', amp: 80 + mid * 70, freq: 0.002, speed: 0.7 },
      { color: 'rgba(16, 185, 129, 0.15)', amp: 40 + bass * 60, freq: 0.004, speed: 1.3 }
    ];

    layers.forEach((layer) => {
      this.ctx.beginPath();
      this.ctx.moveTo(0, this.height);

      for (let x = 0; x <= this.width; x += 15) {
        const y = this.height * 0.55 + Math.sin(x * layer.freq + this.phase * layer.speed) * layer.amp;
        this.ctx.lineTo(x, y);
      }

      this.ctx.lineTo(this.width, this.height);
      this.ctx.closePath();
      this.ctx.fillStyle = layer.color;
      this.ctx.fill();
    });
  }
}

window.AudioVisualizer = AudioVisualizer;
window.ImmersiveCanvasVisualizer = ImmersiveCanvasVisualizer;

