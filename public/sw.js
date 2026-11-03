// FinTrack Service Worker
const CACHE_NAME = 'fintrack-v1.0.0';

// Core assets to pre-cache on install for faster startup
const PRECACHE_ASSETS = [
    '/',
    '/manifest.json',
    '/favicon.ico',
    '/apple-touch-icon.png',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    '/icons/icon.svg',
];

// Install Event: pre-cache essential assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS).catch((err) => {
                // If some asset fails to cache on install, continue installation
                console.warn('[ServiceWorker] Precache failed for some assets:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Activate Event: remove stale caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event: handle requests
self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Only handle GET requests
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Skip cross-origin requests except Google Fonts
    const isGoogleFont = url.hostname.includes('fonts.googleapis.com') || url.hostname.includes('fonts.gstatic.com');
    if (url.origin !== self.location.origin && !isGoogleFont) {
        return;
    }

    // Static assets (Vite build, images, icons, fonts): Cache-First with Network fallback
    const isStaticAsset = (
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/icons/') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.ico') ||
        isGoogleFont
    );

    if (isStaticAsset) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseToCache);
                        });
                    }
                    return networkResponse;
                });
            })
        );
        return;
    }

    // Dynamic pages & Inertia API requests: Network-First with Cache fallback
    event.respondWith(
        fetch(request)
            .then((networkResponse) => {
                // If network response is good, clone it to cache
                if (networkResponse && networkResponse.status === 200) {
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseToCache);
                    });
                }
                return networkResponse;
            })
            .catch(() => {
                // Network failed (offline), try returning cached version
                return caches.match(request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // If root HTML was requested, fallback to cached root
                    if (request.headers.get('accept')?.includes('text/html')) {
                        return caches.match('/');
                    }
                });
            })
    );
});

