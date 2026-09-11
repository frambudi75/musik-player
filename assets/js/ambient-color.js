/**
 * Ambient Color Extractor & Theme Accent Engine
 * Manages Dynamic Adaptive Palette from album art and curated Studio Theme Presets:
 * (Adaptive Glow, Royal Blue, Cyber Indigo, Emerald Green, Velvet Rose, Sunset Amber, Cyber Cyan)
 */

const THEME_PALETTES = {
  blue: {
    primary: '#3b82f6',
    hover: '#2563eb',
    subtle: 'rgba(59, 130, 246, 0.15)',
    glow1: 'rgba(59, 130, 246, 0.35)',
    glow2: 'rgba(30, 58, 138, 0.7)'
  },
  purple: {
    primary: '#8b5cf6',
    hover: '#7c3aed',
    subtle: 'rgba(139, 92, 246, 0.15)',
    glow1: 'rgba(139, 92, 246, 0.35)',
    glow2: 'rgba(76, 29, 149, 0.7)'
  },
  emerald: {
    primary: '#10b981',
    hover: '#059669',
    subtle: 'rgba(16, 185, 129, 0.15)',
    glow1: 'rgba(16, 185, 129, 0.35)',
    glow2: 'rgba(6, 78, 59, 0.7)'
  },
  rose: {
    primary: '#f43f5e',
    hover: '#e11d48',
    subtle: 'rgba(244, 63, 94, 0.15)',
    glow1: 'rgba(244, 63, 94, 0.35)',
    glow2: 'rgba(136, 19, 55, 0.7)'
  },
  amber: {
    primary: '#f59e0b',
    hover: '#d97706',
    subtle: 'rgba(245, 158, 11, 0.15)',
    glow1: 'rgba(245, 158, 11, 0.35)',
    glow2: 'rgba(120, 53, 15, 0.7)'
  },
  cyan: {
    primary: '#06b6d4',
    hover: '#0891b2',
    subtle: 'rgba(6, 182, 212, 0.15)',
    glow1: 'rgba(6, 182, 212, 0.35)',
    glow2: 'rgba(22, 78, 99, 0.7)'
  }
};

class AmbientColorEngine {
  constructor() {
    this.canvas = document.createElement('canvas');
    this.ctx = this.canvas.getContext('2d', { willReadFrequently: true });
    this.canvas.width = 64;
    this.canvas.height = 64;
    this.currentTheme = localStorage.getItem('aura_theme_accent') || 'adaptive';
    this.lastImageSrc = '';
  }

  /**
   * Set active theme accent (e.g. 'adaptive', 'blue', 'purple', 'emerald', 'rose', 'amber', 'cyan')
   */
  setTheme(themeName) {
    this.currentTheme = themeName;
    localStorage.setItem('aura_theme_accent', themeName);
    this.applyToRoot(this.lastImageSrc);
  }

  getTheme() {
    return this.currentTheme;
  }

  /**
   * Extract dominant soft tones from an image URL
   */
  async extractColors(imageSrc) {
    return new Promise((resolve) => {
      if (!imageSrc) {
        resolve({
          primary: '#3b82f6',
          primaryHover: '#2563eb',
          subtle: 'rgba(59, 130, 246, 0.15)',
          color1: 'rgba(30, 41, 59, 0.4)',
          color2: 'rgba(15, 23, 42, 0.8)'
        });
        return;
      }

      const img = new Image();
      img.crossOrigin = 'Anonymous';
      img.onload = () => {
        try {
          this.ctx.drawImage(img, 0, 0, 64, 64);
          const imageData = this.ctx.getImageData(0, 0, 64, 64).data;
          
          let rTotal = 0, gTotal = 0, bTotal = 0, count = 0;
          let samples = [];

          for (let i = 0; i < imageData.length; i += 16) {
            const r = imageData[i];
            const g = imageData[i + 1];
            const b = imageData[i + 2];
            const a = imageData[i + 3];

            if (a < 128) continue; // skip transparent

            // Calculate brightness
            const brightness = (r * 299 + g * 587 + b * 114) / 1000;
            if (brightness > 20 && brightness < 235) {
              rTotal += r;
              gTotal += g;
              bTotal += b;
              count++;
              samples.push({ r, g, b, brightness });
            }
          }

          if (count === 0) {
            resolve({
              primary: '#3b82f6',
              primaryHover: '#2563eb',
              subtle: 'rgba(59, 130, 246, 0.15)',
              color1: 'rgba(30, 41, 59, 0.4)',
              color2: 'rgba(15, 23, 42, 0.8)'
            });
            return;
          }

          const avgR = Math.round(rTotal / count);
          const avgG = Math.round(gTotal / count);
          const avgB = Math.round(bTotal / count);

          const secondary = samples[Math.floor(samples.length * 0.75)] || { r: avgR, g: avgG, b: avgB };

          // Boost vibrant accent slightly
          const maxVal = Math.max(avgR, avgG, avgB, 1);
          const boostR = Math.min(255, Math.round(avgR * (210 / maxVal)));
          const boostG = Math.min(255, Math.round(avgG * (210 / maxVal)));
          const boostB = Math.min(255, Math.round(avgB * (210 / maxVal)));

          const primary = `rgb(${boostR}, ${boostG}, ${boostB})`;
          const primaryHover = `rgb(${Math.max(0, boostR - 25)}, ${Math.max(0, boostG - 25)}, ${Math.max(0, boostB - 25)})`;
          const subtle = `rgba(${boostR}, ${boostG}, ${boostB}, 0.18)`;
          const color1 = `rgba(${avgR}, ${avgG}, ${avgB}, 0.38)`;
          const color2 = `rgba(${secondary.r}, ${secondary.g}, ${secondary.b}, 0.25)`;

          resolve({ primary, primaryHover, subtle, color1, color2 });
        } catch (e) {
          resolve({
            primary: '#3b82f6',
            primaryHover: '#2563eb',
            subtle: 'rgba(59, 130, 246, 0.15)',
            color1: 'rgba(30, 41, 59, 0.4)',
            color2: 'rgba(15, 23, 42, 0.8)'
          });
        }
      };

      img.onerror = () => {
        resolve({
          primary: '#3b82f6',
          primaryHover: '#2563eb',
          subtle: 'rgba(59, 130, 246, 0.15)',
          color1: 'rgba(30, 41, 59, 0.4)',
          color2: 'rgba(15, 23, 42, 0.8)'
        });
      };

      img.src = imageSrc;
    });
  }

  /**
   * Apply colors to root CSS variables with smooth transition
   */
  async applyToRoot(imageSrc) {
    this.lastImageSrc = imageSrc || this.lastImageSrc;

    if (this.currentTheme !== 'adaptive' && THEME_PALETTES[this.currentTheme]) {
      const palette = THEME_PALETTES[this.currentTheme];
      document.documentElement.style.setProperty('--accent-primary', palette.primary);
      document.documentElement.style.setProperty('--accent-primary-hover', palette.hover);
      document.documentElement.style.setProperty('--accent-subtle', palette.subtle);
      document.documentElement.style.setProperty('--ambient-color-1', palette.glow1);
      document.documentElement.style.setProperty('--ambient-color-2', palette.glow2);
      return;
    }

    // Adaptive Theme (Extract from album art)
    const res = await this.extractColors(this.lastImageSrc);
    document.documentElement.style.setProperty('--accent-primary', res.primary);
    document.documentElement.style.setProperty('--accent-primary-hover', res.primaryHover);
    document.documentElement.style.setProperty('--accent-subtle', res.subtle);
    document.documentElement.style.setProperty('--ambient-color-1', res.color1);
    document.documentElement.style.setProperty('--ambient-color-2', res.color2);
  }
}

window.AmbientColor = new AmbientColorEngine();
