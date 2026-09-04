/**
 * Aura Music - Offline Storage Engine (IndexedDB)
 * Allows downloading tracks & artwork for 100% offline playback
 */

class AuraOfflineDB {
  constructor() {
    this.dbName = 'aura_music_offline_v1';
    this.storeName = 'offline_tracks';
    this.db = null;
    this.savedIds = new Set();
    this.initPromise = this.init();
  }

  async init() {
    if (!('indexedDB' in window)) {
      console.warn('IndexedDB not supported in this browser.');
      return null;
    }

    return new Promise((resolve, reject) => {
      const request = indexedDB.open(this.dbName, 1);

      request.onupgradeneeded = (event) => {
        const db = event.target.result;
        if (!db.objectStoreNames.contains(this.storeName)) {
          const store = db.createObjectStore(this.storeName, { keyPath: 'id' });
          store.createIndex('savedAt', 'savedAt', { unique: false });
        }
      };

      request.onsuccess = (event) => {
        this.db = event.target.result;
        this.refreshSavedIds().then(() => resolve(this.db));
      };

      request.onerror = (event) => {
        console.error('IndexedDB open error:', event.target.error);
        resolve(null);
      };
    });
  }

  async refreshSavedIds() {
    if (!this.db) return;
    try {
      const tx = this.db.transaction(this.storeName, 'readonly');
      const store = tx.objectStore(this.storeName);
      const req = store.getAllKeys();
      req.onsuccess = () => {
        this.savedIds = new Set(req.result || []);
      };
    } catch (e) {}
  }

  hasOfflineBlob(songId) {
    return this.savedIds.has(songId);
  }

  async isAvailable() {
    await this.initPromise;
    return this.db !== null;
  }

  async isSaved(songId) {
    await this.initPromise;
    if (this.savedIds.has(songId)) return true;
    if (!this.db) return false;

    return new Promise((resolve) => {
      try {
        const tx = this.db.transaction(this.storeName, 'readonly');
        const store = tx.objectStore(this.storeName);
        const req = store.get(songId);
        req.onsuccess = () => {
          const exists = !!req.result;
          if (exists) this.savedIds.add(songId);
          resolve(exists);
        };
        req.onerror = () => resolve(false);
      } catch (e) {
        resolve(false);
      }
    });
  }

  async saveTrack(song, onProgress = null) {
    await this.initPromise;
    if (!this.db) throw new Error('IndexedDB not available');

    // Fetch Audio as Blob
    if (onProgress) onProgress(0.2, 'Mengunduh audio...');
    const audioRes = await fetch(song.url);
    if (!audioRes.ok) throw new Error('Gagal mengunduh audio');
    const audioBlob = await audioRes.blob();

    // Fetch Cover as Blob (if present)
    let coverBlob = null;
    if (song.cover) {
      if (onProgress) onProgress(0.6, 'Menyimpan artwork...');
      try {
        const coverRes = await fetch(song.cover);
        if (coverRes.ok) {
          coverBlob = await coverRes.blob();
        }
      } catch (err) {}
    }

    // Save Record
    if (onProgress) onProgress(0.9, 'Menyimpan ke IndexedDB...');
    const record = {
      id: song.id,
      title: song.title,
      artist: song.artist,
      album: song.album || '',
      genre: song.genre || '',
      lyrics: song.lyrics || null,
      audioBlob: audioBlob,
      coverBlob: coverBlob,
      size: audioBlob.size,
      savedAt: Date.now()
    };

    return new Promise((resolve, reject) => {
      const tx = this.db.transaction(this.storeName, 'readwrite');
      const store = tx.objectStore(this.storeName);
      const req = store.put(record);
      req.onsuccess = () => resolve(record);
      req.onerror = (e) => reject(e.target.error);
    });
  }

  async removeTrack(songId) {
    await this.initPromise;
    if (!this.db) return false;

    return new Promise((resolve, reject) => {
      const tx = this.db.transaction(this.storeName, 'readwrite');
      const store = tx.objectStore(this.storeName);
      const req = store.delete(songId);
      req.onsuccess = () => resolve(true);
      req.onerror = () => resolve(false);
    });
  }

  async getTrackBlobUrl(songId) {
    await this.initPromise;
    if (!this.db) return null;

    return new Promise((resolve) => {
      const tx = this.db.transaction(this.storeName, 'readonly');
      const store = tx.objectStore(this.storeName);
      const req = store.get(songId);
      req.onsuccess = () => {
        if (req.result && req.result.audioBlob) {
          const url = URL.createObjectURL(req.result.audioBlob);
          resolve(url);
        } else {
          resolve(null);
        }
      };
      req.onerror = () => resolve(null);
    });
  }

  async getAllSavedSongs() {
    await this.initPromise;
    if (!this.db) return [];

    return new Promise((resolve) => {
      const tx = this.db.transaction(this.storeName, 'readonly');
      const store = tx.objectStore(this.storeName);
      const req = store.getAll();
      req.onsuccess = () => {
        const list = (req.result || []).map((item) => ({
          id: item.id,
          title: item.title,
          artist: item.artist,
          album: item.album,
          genre: item.genre,
          lyrics: item.lyrics,
          url: item.audioBlob ? URL.createObjectURL(item.audioBlob) : '',
          cover: item.coverBlob ? URL.createObjectURL(item.coverBlob) : '',
          isOffline: true,
          size: item.size,
          savedAt: item.savedAt
        }));
        resolve(list);
      };
      req.onerror = () => resolve([]);
    });
  }
}

window.OfflineDB = new AuraOfflineDB();
