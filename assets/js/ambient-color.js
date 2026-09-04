/**
 * Ambient Color Extractor
 * Extracts subtle, authentic palette tones from album artwork to drive organic ambient lighting.
 * Avoids oversaturated neon "AI-slop" tones.
 */

class AmbientColorEngine {
  constructor() {
    this.canvas = document.createElement('canvas');
    this.ctx = this.canvas.getContext('2d', { willReadFrequently: true });
    this.canvas.width = 64;
    this.canvas.height = 64;
  }

  /**
   * Extract dominant soft tones from an image URL or image element
   */
  async extractColors(imageSrc) {
    return new Promise((resolve) => {
      if (!imageSrc) {
        resolve({
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
            // Ignore extreme whites and pitch blacks
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
              color1: 'rgba(30, 41, 59, 0.4)',
              color2: 'rgba(15, 23, 42, 0.8)'
            });
            return;
          }

          const avgR = Math.round(rTotal / count);
          const avgG = Math.round(gTotal / count);
          const avgB = Math.round(bTotal / count);

          // Get a secondary contrasting sample
          const secondary = samples[Math.floor(samples.length * 0.75)] || { r: avgR, g: avgG, b: avgB };

          // Clamp saturation slightly for calm, mature, editorial aesthetic
          const color1 = `rgba(${avgR}, ${avgG}, ${avgB}, 0.38)`;
          const color2 = `rgba(${secondary.r}, ${secondary.g}, ${secondary.b}, 0.25)`;

          resolve({ color1, color2 });
        } catch (e) {
          resolve({
            color1: 'rgba(30, 41, 59, 0.4)',
            color2: 'rgba(15, 23, 42, 0.8)'
          });
        }
      };

      img.onerror = () => {
        resolve({
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
    const { color1, color2 } = await this.extractColors(imageSrc);
    document.documentElement.style.setProperty('--ambient-color-1', color1);
    document.documentElement.style.setProperty('--ambient-color-2', color2);
  }
}

window.AmbientColor = new AmbientColorEngine();
