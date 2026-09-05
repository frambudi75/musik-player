const CACHE_NAME = 'aura-music-v20';
const ASSETS_TO_CACHE = [
  './',
  './index.php',
  './manifest.json',
  './assets/css/style.css',
  './assets/css/player.css',
  './assets/js/ambient-color.js',
  './assets/js/audio-core.js',
  './assets/js/offline-storage.js',
  './assets/js/waveform.js',
  './assets/js/visualizer.js',
  './assets/js/lyrics.js',
  './assets/js/playlist.js',
  './assets/js/app.js',
  './assets/sample_covers/placeholder.svg',
  './assets/icons/icon-192.png',
  './assets/icons/icon-512.png'
];

// Install Event
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE).catch((err) => {
        console.warn('Non-critical cache add warning:', err);
      });
    })
  );
  self.skipWaiting();
});

// Activate Event - Clean old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((name) => {
          if (name !== CACHE_NAME) {
            return caches.delete(name);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch Event
self.addEventListener('fetch', (event) => {
  // Ignore non-GET requests and unsupported protocols (e.g. chrome-extension://, blob:, data:)
  if (event.request.method !== 'GET') return;
  if (!event.request.url.startsWith('http://') && !event.request.url.startsWith('https://')) return;

  const url = new URL(event.request.url);

  // For dynamic API and audio streaming, always bypass cache to prevent memory bloat
  if (url.pathname.includes('/api/') || url.pathname.endsWith('.mp3') || url.pathname.endsWith('.wav') || url.pathname.endsWith('.flac') || url.pathname.endsWith('.ogg')) {
    event.respondWith(
      fetch(event.request).catch(() => new Response(JSON.stringify({ status: 'offline' }), {
        headers: { 'Content-Type': 'application/json' }
      }))
    );
    return;
  }

  // Stale-While-Revalidate for UI assets
  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      const fetchPromise = fetch(event.request).then((networkResponse) => {
        if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
          const responseClone = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseClone).catch(() => {});
          });
        }
        return networkResponse;
      }).catch(() => cachedResponse);

      return cachedResponse || fetchPromise;
    })
  );
});
