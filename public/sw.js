/**
 * KEREN SNACK ERP — Progressive Web App Service Worker
 * Version: 1.0.0 | 2026
 * Strategy:
 *   - Static Assets (CSS, JS, Fonts, Icons): Cache-First / Stale-While-Revalidate
 *   - ERP Dynamic Data & Transaksi (PHP / API / Navigation): Network-First
 */

const CACHE_NAME = 'ksnack-erp-cache-v1';

// Static assets to pre-cache on install
const PRECACHE_ASSETS = [
  './assets/css/app.css',
  './assets/js/app.js',
  './assets/js/lucide.min.js',
  './assets/js/alpine.min.js',
  './assets/favicon/favicon.ico',
  './assets/favicon/favicon.svg',
  './assets/favicon/favicon-96x96.png',
  './assets/favicon/apple-touch-icon.png',
  './assets/favicon/web-app-manifest-192x192.png',
  './assets/favicon/web-app-manifest-512x512.png',
  './assets/favicon/site.webmanifest'
];

// Install Event
self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(PRECACHE_ASSETS).catch((err) => {
        console.warn('[SW] Non-critical precache item skipped:', err);
      });
    })
  );
});

// Activate Event (Cleanup old caches)
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME)
          .map((name) => caches.delete(name))
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Event
self.addEventListener('fetch', (event) => {
  const request = event.request;

  // Only handle GET requests; pass mutations (POST, PUT, DELETE) straight to network
  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);

  // Strategy 1: Static Assets (CSS, JS, Images, Fonts, Favicon) -> Stale-While-Revalidate
  const isStaticAsset = (
    url.pathname.includes('/assets/') ||
    url.hostname.includes('fonts.googleapis.com') ||
    url.hostname.includes('fonts.gstatic.com') ||
    url.pathname.endsWith('.css') ||
    url.pathname.endsWith('.js') ||
    url.pathname.endsWith('.png') ||
    url.pathname.endsWith('.jpg') ||
    url.pathname.endsWith('.svg') ||
    url.pathname.endsWith('.ico') ||
    url.pathname.endsWith('.woff2')
  );

  if (isStaticAsset) {
    event.respondWith(
      caches.open(CACHE_NAME).then((cache) => {
        return cache.match(request).then((cachedResponse) => {
          const fetchPromise = fetch(request)
            .then((networkResponse) => {
              if (networkResponse && networkResponse.status === 200) {
                cache.put(request, networkResponse.clone());
              }
              return networkResponse;
            })
            .catch(() => cachedResponse);

          return cachedResponse || fetchPromise;
        });
      })
    );
    return;
  }

  // Strategy 2: HTML Navigation / Dynamic PHP routes -> Network-First (realtime ERP data)
  if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(request)
        .then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const responseClone = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(request, responseClone);
            });
          }
          return networkResponse;
        })
        .catch(() => {
          // Fallback to cache if network is offline
          return caches.match(request).then((cachedResponse) => {
            if (cachedResponse) return cachedResponse;
            return new Response(
              `<!DOCTYPE html>
              <html lang="id" class="dark">
              <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Offline — KEREN SNACK ERP</title>
                <style>
                  body { margin:0; padding:0; background:#111827; color:#f8fafc; font-family:system-ui,-apple-system,sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; text-align:center; }
                  .card { max-width:400px; padding:32px; background:#1f2937; border-radius:16px; border:1px solid #374151; }
                  h1 { font-size:20px; margin-bottom:8px; color:#fb7185; }
                  p { font-size:13px; color:#9ca3af; line-height:1.5; margin-bottom:20px; }
                  button { background:#e11d48; color:#fff; border:none; padding:10px 20px; border-radius:8px; font-weight:600; cursor:pointer; }
                </style>
              </head>
              <body>
                <div class="card">
                  <h1>Koneksi Terputus</h1>
                  <p>Anda sedang offline atau server tidak dapat dihubungi. Silakan periksa koneksi internet Anda dan coba lagi.</p>
                  <button onclick="window.location.reload()">Muat Ulang</button>
                </div>
              </body>
              </html>`,
              { headers: { 'Content-Type': 'text/html; charset=UTF-8' } }
            );
          });
        })
    );
    return;
  }

  // Default: direct fetch
  event.respondWith(fetch(request));
});
