/**
 * Audio Core Engine
 * Web Audio API graph: Source -> 10-Band EQ -> Bass Booster -> Treble Booster -> (Dry / Wet Reverb) -> Master Gain -> Analyser -> Output
 */

class AudioCore {
  constructor() {
    this.audio = new Audio();
    this.audio.preload = 'auto';
    this.audio.crossOrigin = 'anonymous';

    this.audioCtx = null;
    this.sourceNode = null;
    this.analyser = null;
    this.gainNode = null;
    this.bassBoostNode = null;
    this.trebleBoostNode = null;
    this.eqFilters = [];

    // Reverb / Delay FX Nodes (connected on demand only)
    this.reverbConvolver = null;
    this.reverbWetGain = null;
    this.isReverbActive = false;

    // Preamp Booster (1.0x to 3.0x / 300%)
    this.preampGain = 1.0;
    this.preampNode = null;

    // Vocal Remover (Phase Inversion L-R)
    this.isVocalRemoverActive = false;
    this.splitterNode = null;
    this.mergerNode = null;
    this.inverterNode = null;

    // 8D Spatial Audio (360 Binaural Rotation)
    this.is8DActive = false;
    this.panner8DNode = null;
    this.spatial8DSpeed = 1.0;
    this.spatial8DAngle = 0;
    this.spatial8DAnimId = null;

    // Ambient White Noise Mixer (Rain, Campfire, Vinyl)
    this.ambientType = 'off';
    this.ambientVolume = 0.4;
    this.ambientSourceNode = null;
    this.ambientGainNode = null;

    // Haptic Bass Vibration
    this.isHapticActive = false;
    this.lastHapticTime = 0;

    // Active DSP Preset
    this.activeDspPreset = 'normal';

    // Crossfade & Settings
    this.crossfadeDuration = 0; // seconds (0, 2, 4, 6)
    this.playbackRate = 1.0;

    this.frequencies = [32, 64, 125, 250, 500, 1000, 2000, 4000, 8000, 16000];
    this.presets = {
      flat: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
      bass_boost: [7, 6, 5, 3, 1, 0, 0, 0, 1, 2],
      pop: [-1, 2, 4, 5, 3, -1, -2, -1, 2, 3],
      rock: [5, 4, 2, 0, -2, -1, 1, 3, 4, 5],
      electronic: [6, 5, 2, 0, -2, 2, 1, 2, 4, 5],
      jazz: [3, 2, 1, 2, -1, -1, 0, 1, 3, 4],
      vocal: [-3, -2, 0, 3, 6, 5, 3, 1, -1, -2],
      acoustic: [4, 3, 2, 1, 2, 2, 3, 3, 4, 3]
    };

    this.currentPreset = 'flat';
    this.isInitialized = false;

    // Callbacks
    this.onTimeUpdate = null;
    this.onEnded = null;
    this.onPlay = null;
    this.onPause = null;
    this.onMetadata = null;
    this.onError = null;

    this.bindEvents();
  }

  createSyntheticImpulse(seconds = 1.2, decay = 2.0) {
    if (!this.audioCtx) return null;
    const rate = this.audioCtx.sampleRate;
    const length = Math.floor(rate * seconds);
    const impulse = this.audioCtx.createBuffer(2, length, rate);
    const left = impulse.getChannelData(0);
    const right = impulse.getChannelData(1);

    for (let i = 0; i < length; i++) {
      const n = i / length;
      const factor = Math.pow(1 - n, decay);
      left[i] = (Math.random() * 2 - 1) * factor;
      right[i] = (Math.random() * 2 - 1) * factor;
    }
    return impulse;
  }

  initWebAudio() {
    if (this.isInitialized) return;

    try {
      const AudioContextClass = window.AudioContext || window.webkitAudioContext;
      this.audioCtx = new AudioContextClass();

      // Create Analyser
      this.analyser = this.audioCtx.createAnalyser();
      this.analyser.fftSize = 256;
      this.analyser.smoothingTimeConstant = 0.82;

      // Create Master Gain & Preamp Booster
      this.gainNode = this.audioCtx.createGain();
      this.preampNode = this.audioCtx.createGain();
      this.preampNode.gain.value = this.preampGain;

      // Create Bass Booster (Lowshelf) & Treble Booster (Highshelf)
      this.bassBoostNode = this.audioCtx.createBiquadFilter();
      this.bassBoostNode.type = 'lowshelf';
      this.bassBoostNode.frequency.value = 80;
      this.bassBoostNode.gain.value = 0;

      this.trebleBoostNode = this.audioCtx.createBiquadFilter();
      this.trebleBoostNode.type = 'highshelf';
      this.trebleBoostNode.frequency.value = 10000;
      this.trebleBoostNode.gain.value = 0;

      // Create 10-Band EQ Filters
      this.eqFilters = this.frequencies.map((freq) => {
        const filter = this.audioCtx.createBiquadFilter();
        filter.type = 'peaking';
        filter.frequency.value = freq;
        filter.Q.value = 1.4;
        filter.gain.value = 0;
        return filter;
      });

      // Connect Media Element Source
      this.sourceNode = this.audioCtx.createMediaElementSource(this.audio);

      // Connect Chain: Source -> EQ Filters -> Bass -> Treble -> Preamp -> 8D Panner -> Master Gain
      let lastNode = this.sourceNode;
      this.eqFilters.forEach((filter) => {
        lastNode.connect(filter);
        lastNode = filter;
      });

      // 8D Panner Node
      if (this.audioCtx.createStereoPanner) {
        this.panner8DNode = this.audioCtx.createStereoPanner();
      }

      lastNode.connect(this.bassBoostNode);
      this.bassBoostNode.connect(this.trebleBoostNode);
      this.trebleBoostNode.connect(this.preampNode);

      if (this.panner8DNode) {
        this.preampNode.connect(this.panner8DNode);
        this.panner8DNode.connect(this.gainNode);
      } else {
        this.preampNode.connect(this.gainNode);
      }

      // Create Dynamics Compressor (Smooth Normalizer / Limiter)
      this.compressorNode = this.audioCtx.createDynamicsCompressor();
      this.compressorNode.threshold.value = -18;
      this.compressorNode.knee.value = 16;
      this.compressorNode.ratio.value = 3.5;
      this.compressorNode.attack.value = 0.005;
      this.compressorNode.release.value = 0.15;
      this.isNormalizerActive = true;

      // Master Chain: Gain -> Compressor -> Analyser -> Output
      this.gainNode.connect(this.compressorNode);

      // Ambient Noise Gain (Mixes straight into Compressor)
      this.ambientGainNode = this.audioCtx.createGain();
      this.ambientGainNode.gain.value = 0;
      this.ambientGainNode.connect(this.compressorNode);
      this.compressorNode.connect(this.analyser);
      this.analyser.connect(this.audioCtx.destination);

      this.isInitialized = true;
    } catch (e) {
      console.warn('Web Audio API not fully available or blocked by autoplay policy:', e);
    }
  }

  setPreamp(multiplier) {
    this.preampGain = Math.max(1.0, Math.min(3.0, multiplier));
    if (this.preampNode && this.audioCtx) {
      this.preampNode.gain.setValueAtTime(this.preampGain, this.audioCtx.currentTime);
    }
  }

  setVocalRemover(enabled) {
    this.isVocalRemoverActive = !!enabled;
    if (!this.audioCtx || !this.trebleBoostNode || !this.preampNode) return;

    try {
      if (this.isVocalRemoverActive) {
        // Disconnect direct path
        this.trebleBoostNode.disconnect(this.preampNode);

        if (!this.splitterNode) {
          this.splitterNode = this.audioCtx.createChannelSplitter(2);
          this.mergerNode = this.audioCtx.createChannelMerger(2);
          this.inverterNode = this.audioCtx.createGain();
          this.inverterNode.gain.value = -1; // Phase inversion
        }

        // L - R Phase Cancellation
        this.trebleBoostNode.connect(this.splitterNode);
        // Left channel directly to both Left and Right output
        this.splitterNode.connect(this.mergerNode, 0, 0);
        this.splitterNode.connect(this.mergerNode, 0, 1);
        // Right channel inverted into both Left and Right output
        this.splitterNode.connect(this.inverterNode, 1);
        this.inverterNode.connect(this.mergerNode, 0, 0);
        this.inverterNode.connect(this.mergerNode, 0, 1);

        this.mergerNode.connect(this.preampNode);
      } else {
        // Restore direct stereo path
        if (this.mergerNode) {
          try {
            this.mergerNode.disconnect(this.preampNode);
            this.trebleBoostNode.disconnect(this.splitterNode);
          } catch(e) {}
        }
        this.trebleBoostNode.connect(this.preampNode);
      }
    } catch (e) {
      console.warn('Error toggling vocal remover:', e);
    }
  }

  setHapticBass(enabled) {
    this.isHapticActive = !!enabled;
  }

  triggerHapticPulse() {
    if (!this.isHapticActive || !('vibrate' in navigator)) return;
    const now = performance.now();
    if (now - this.lastHapticTime < 160) return; // Cooldown 160ms

    const freqData = this.getFrequencyData();
    // Sub-bass frequency range (index 0 - 2 = ~0 - 150Hz)
    const bassEnergy = Math.max(freqData[0] || 0, freqData[1] || 0, freqData[2] || 0);
    if (bassEnergy > 190) {
      navigator.vibrate(25);
      this.lastHapticTime = now;
    }
  }

  setNormalizer(enabled) {
    this.isNormalizerActive = !!enabled;
    if (!this.compressorNode || !this.audioCtx) return;
    const now = this.audioCtx.currentTime;
    if (this.isNormalizerActive) {
      this.compressorNode.threshold.setValueAtTime(-18, now);
      this.compressorNode.ratio.setValueAtTime(3.5, now);
    } else {
      this.compressorNode.threshold.setValueAtTime(0, now);
      this.compressorNode.ratio.setValueAtTime(1, now);
    }
  }

  bindEvents() {
    this.audio.addEventListener('timeupdate', () => {
      this.triggerHapticPulse();
      if (this.onTimeUpdate) {
        this.onTimeUpdate(this.audio.currentTime, this.audio.duration || 0);
      }
    });

    this.audio.addEventListener('loadedmetadata', () => {
      if (this.onMetadata) {
        this.onMetadata(this.audio.duration || 0);
      }
    });

    this.audio.addEventListener('play', () => {
      if (this.audioCtx && this.audioCtx.state === 'suspended') {
        this.audioCtx.resume().catch(() => {});
      }
      if (this.onPlay) this.onPlay();
    });

    this.audio.addEventListener('pause', () => {
      if (this.onPause) this.onPause();
    });

    this.audio.addEventListener('ended', () => {
      if (this.onEnded) this.onEnded();
    });

    this.audio.addEventListener('error', (e) => {
      const err = this.audio.error;
      let errMsg = 'Audio playback error';
      if (err) {
        switch (err.code) {
          case 1: errMsg = 'Media playback aborted by user'; break;
          case 2: errMsg = 'Network error while loading audio'; break;
          case 3: errMsg = 'Audio decode error (corrupted file)'; break;
          case 4: errMsg = 'Audio format not supported by browser'; break;
        }
      }
      console.warn('AudioCore error:', errMsg, err);
      if (this.onError) this.onError(errMsg, err);
    });

    // Auto-resume audio context on user interaction if suspended by browser
    ['click', 'touchstart', 'keydown'].forEach((evt) => {
      window.addEventListener(evt, () => {
        if (this.audioCtx && this.audioCtx.state === 'suspended' && !this.audio.paused) {
          this.audioCtx.resume().catch(() => {});
        }
      }, { passive: true, once: false });
    });
  }

  loadTrack(src) {
    this.initWebAudio();

    // Memory Leak Shield: Revoke previous blob URL if needed
    if (this._currentBlobUrl && this._currentBlobUrl.startsWith('blob:') && this._currentBlobUrl !== src) {
      try {
        URL.revokeObjectURL(this._currentBlobUrl);
      } catch (e) {}
      this._currentBlobUrl = null;
    }

    if (src && src.startsWith('blob:')) {
      this._currentBlobUrl = src;
    }

    this.audio.src = src;
    this.audio.playbackRate = this.playbackRate;
    this.audio.load();
  }

  async play() {
    this.initWebAudio();
    if (this.audioCtx && this.audioCtx.state === 'suspended') {
      try {
        await this.audioCtx.resume();
      } catch (e) {}
    }
    this.audio.playbackRate = this.playbackRate;
    try {
      return await this.audio.play();
    } catch (err) {
      console.warn('Audio play request was interrupted or prevented by browser:', err);
      if (this.onError) {
        this.onError('Autoplay prevented or interrupted', err);
      }
      return Promise.reject(err);
    }
  }

  pause() {
    this.audio.pause();
  }

  togglePlay() {
    if (this.audio.paused) {
      return this.play();
    } else {
      this.pause();
      return Promise.resolve();
    }
  }

  seek(seconds) {
    if (Number.isFinite(seconds) && this.audio.duration) {
      this.audio.currentTime = Math.max(0, Math.min(seconds, this.audio.duration));
    }
  }

  setVolume(fraction) {
    const val = Math.max(0, Math.min(1, fraction));
    this.audio.volume = val;
  }

  getVolume() {
    return this.audio.volume;
  }

  setPlaybackRate(rate) {
    const val = Math.max(0.5, Math.min(2.0, rate));
    this.playbackRate = val;
    this.audio.playbackRate = val;
    this.audio.preservesPitch = true;
  }

  setReverb(enabled, wetAmount = 0.5) {
    this.isReverbActive = !!enabled;
    if (!this.audioCtx || !this.trebleBoostNode || !this.gainNode) return;

    if (enabled) {
      if (!this.reverbConvolver) {
        this.reverbConvolver = this.audioCtx.createConvolver();
        this.reverbConvolver.buffer = this.createSyntheticImpulse(1.2, 2.0);
        this.reverbWetGain = this.audioCtx.createGain();
        this.reverbConvolver.connect(this.reverbWetGain);
        this.reverbWetGain.connect(this.gainNode);
      }
      try {
        this.trebleBoostNode.connect(this.reverbConvolver);
      } catch (e) {}
      const now = this.audioCtx.currentTime;
      this.reverbWetGain.gain.setValueAtTime(wetAmount, now);
    } else {
      if (this.reverbConvolver) {
        try {
          this.trebleBoostNode.disconnect(this.reverbConvolver);
        } catch (e) {}
      }
      if (this.reverbWetGain) {
        const now = this.audioCtx.currentTime;
        this.reverbWetGain.gain.setValueAtTime(0, now);
      }
    }
  }

  setEQBandGain(index, gainDb) {
    if (this.eqFilters[index]) {
      this.eqFilters[index].gain.value = Math.max(-12, Math.min(12, gainDb));
    }
  }

  setBassBoost(gainDb) {
    if (this.bassBoostNode) {
      this.bassBoostNode.gain.value = Math.max(0, Math.min(15, gainDb));
    }
  }

  setTrebleBoost(gainDb) {
    if (this.trebleBoostNode) {
      this.trebleBoostNode.gain.value = Math.max(-10, Math.min(10, gainDb));
    }
  }

  applyPreset(presetName) {
    const gains = this.presets[presetName];
    if (gains && this.eqFilters.length > 0) {
      this.currentPreset = presetName;
      gains.forEach((gain, i) => {
        this.setEQBandGain(i, gain);
      });
    }
  }

  /**
   * Smoothly ramp volume from current level to target level over durationMs
   */
  fadeVolume(toVolume, durationMs = 3000) {
    return new Promise((resolve) => {
      const startVol = this.audio.volume;
      const targetVol = Math.max(0, Math.min(1, toVolume));
      const startTime = performance.now();

      const step = () => {
        const elapsed = performance.now() - startTime;
        const progress = Math.min(1, elapsed / durationMs);
        this.audio.volume = startVol + (targetVol - startVol) * progress;

        if (progress < 1) {
          requestAnimationFrame(step);
        } else {
          this.audio.volume = targetVol;
          resolve();
        }
      };
      requestAnimationFrame(step);
    });
  }

  // ==========================================
  // 8D SPATIAL AUDIO (360 Binaural Rotation)
  // ==========================================
  set8DAudio(enabled, speed = 1.0) {
    this.is8DActive = !!enabled;
    this.spatial8DSpeed = Math.max(0.2, Math.min(3.0, speed));

    if (this.is8DActive) {
      if (!this.spatial8DAnimId) {
        this._run8DAudioLoop();
      }
    } else {
      if (this.spatial8DAnimId) {
        cancelAnimationFrame(this.spatial8DAnimId);
        this.spatial8DAnimId = null;
      }
      if (this.panner8DNode) {
        this.panner8DNode.pan.value = 0;
      }
    }
  }

  _run8DAudioLoop() {
    if (!this.is8DActive) return;

    this.spatial8DAngle += 0.015 * this.spatial8DSpeed;
    if (this.panner8DNode) {
      // Smooth sinusoidal pan from -1.0 (left) to +1.0 (right) in 360 circle
      this.panner8DNode.pan.value = Math.sin(this.spatial8DAngle);
    }
    this.spatial8DAnimId = requestAnimationFrame(() => this._run8DAudioLoop());
  }

  // ==========================================
  // AMBIENT WHITE NOISE MIXER (Procedural Synth)
  // ==========================================
  setAmbientSound(type, volume = 0.4) {
    this.ambientType = type;
    this.ambientVolume = Math.max(0, Math.min(1, volume));

    if (!this.audioCtx || !this.ambientGainNode) return;

    // Stop existing ambient source
    if (this.ambientSourceNode) {
      try {
        this.ambientSourceNode.stop();
        this.ambientSourceNode.disconnect();
      } catch (e) {}
      this.ambientSourceNode = null;
    }

    if (type === 'off') {
      this.ambientGainNode.gain.setValueAtTime(0, this.audioCtx.currentTime);
      return;
    }

    // Generate Procedural Loop
    const sampleRate = this.audioCtx.sampleRate;
    const duration = 4.0; // 4 second loopable buffer
    const frameCount = sampleRate * duration;
    const buffer = this.audioCtx.createBuffer(2, frameCount, sampleRate);
    const left = buffer.getChannelData(0);
    const right = buffer.getChannelData(1);

    if (type === 'rain') {
      // Pink/Brown Noise Simulation (Warm continuous rainfall)
      let b0L = 0, b1L = 0, b2L = 0;
      let b0R = 0, b1R = 0, b2R = 0;
      for (let i = 0; i < frameCount; i++) {
        const whiteL = Math.random() * 2 - 1;
        const whiteR = Math.random() * 2 - 1;
        b0L = 0.99886 * b0L + whiteL * 0.0555179;
        b1L = 0.99332 * b1L + whiteL * 0.0750759;
        b2L = 0.96900 * b2L + whiteL * 0.1538520;
        left[i] = (b0L + b1L + b2L) * 0.35;

        b0R = 0.99886 * b0R + whiteR * 0.0555179;
        b1R = 0.99332 * b1R + whiteR * 0.0750759;
        b2R = 0.96900 * b2R + whiteR * 0.1538520;
        right[i] = (b0R + b1R + b2R) * 0.35;
      }
    } else if (type === 'fire') {
      // Campfire with random crackle pops
      for (let i = 0; i < frameCount; i++) {
        let valL = (Math.random() * 2 - 1) * 0.08;
        let valR = (Math.random() * 2 - 1) * 0.08;
        // Random popping spark
        if (Math.random() < 0.0008) {
          const pop = (Math.random() * 2 - 1) * 0.85;
          valL += pop;
          valR += pop * 0.7;
        }
        left[i] = valL;
        right[i] = valR;
      }
    } else if (type === 'vinyl') {
      // Vinyl 33 RPM hiss and stylus ticks
      for (let i = 0; i < frameCount; i++) {
        let valL = (Math.random() * 2 - 1) * 0.04;
        let valR = (Math.random() * 2 - 1) * 0.04;
        if (Math.random() < 0.0004) {
          const tick = (Math.random() * 2 - 1) * 0.6;
          valL += tick;
          valR += tick;
        }
        left[i] = valL;
        right[i] = valR;
      }
    }

    this.ambientSourceNode = this.audioCtx.createBufferSource();
    this.ambientSourceNode.buffer = buffer;
    this.ambientSourceNode.loop = true;
    this.ambientSourceNode.connect(this.ambientGainNode);
    this.ambientGainNode.gain.setValueAtTime(this.ambientVolume, this.audioCtx.currentTime);
    this.ambientSourceNode.start();
  }

  // ==========================================
  // 1-CLICK STUDIO DSP PRESETS
  // ==========================================
  setDspPreset(presetName) {
    this.activeDspPreset = presetName;

    switch (presetName) {
      case 'nightcore':
        this.audio.playbackRate = 1.25;
        this.audio.preservesPitch = false; // Characteristic high-pitched energetic vocal
        this.setReverb(false);
        this.setBassBoost(4);
        break;

      case 'slowed_reverb':
        this.audio.playbackRate = 0.85;
        this.audio.preservesPitch = false; // Deep pitch
        this.setReverb(true, 0.65);
        this.setBassBoost(6);
        this.setTrebleBoost(-2);
        break;

      case 'vaporwave':
        this.audio.playbackRate = 0.78;
        this.audio.preservesPitch = false;
        this.setReverb(true, 0.45);
        this.setTrebleBoost(-5);
        break;

      case 'bass_heavy':
        this.audio.playbackRate = 1.0;
        this.audio.preservesPitch = true;
        this.setReverb(false);
        this.applyPreset('bass_boost');
        this.setBassBoost(10);
        break;

      case 'normal':
      default:
        this.audio.playbackRate = 1.0;
        this.audio.preservesPitch = true;
        this.setReverb(false);
        this.applyPreset('flat');
        this.setBassBoost(0);
        this.setTrebleBoost(0);
        break;
    }
  }

  getFrequencyData() {
    if (!this.analyser) return new Uint8Array(128);
    const bufferLength = this.analyser.frequencyBinCount;
    const dataArray = new Uint8Array(bufferLength);
    this.analyser.getByteFrequencyData(dataArray);
    return dataArray;
  }

  getTimeDomainData() {
    if (!this.analyser) return new Uint8Array(128);
    const bufferLength = this.analyser.frequencyBinCount;
    const dataArray = new Uint8Array(bufferLength);
    this.analyser.getByteTimeDomainData(dataArray);
    return dataArray;
  }
}

window.AudioCore = new AudioCore();
