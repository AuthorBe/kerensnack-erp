/**
 * KEREN SNACK ERP — Progressive Web App Service Worker
 * Version: 2.0.0 | 2026
 * Strategy:
 *   - Static Assets (CSS, JS, Fonts, Icons, Favicon): Stale-While-Revalidate / Cache-First
 *   - ERP Dynamic Data & Transaksi (PHP / Navigation / API): Network-First (Online-Safe)
 *   - Offline Fallback: High-Fidelity UI matching restricted.php (Zero False-Positive)
 */

const CACHE_NAME = 'ksnack-erp-cache-v2';

// Static assets to pre-cache on install
const PRECACHE_ASSETS = [
  './assets/css/app.css',
  './assets/js/app.js',
  './assets/js/lucide.min.js',
  './assets/js/alpine.min.js',
  './assets/js/erp-helpers.js',
  './assets/favicon/favicon.ico',
  './assets/favicon/favicon.svg',
  './assets/favicon/favicon-96x96.png',
  './assets/favicon/apple-touch-icon.png',
  './assets/favicon/web-app-manifest-192x192.png',
  './assets/favicon/web-app-manifest-512x512.png',
  './assets/favicon/site.webmanifest'
];

// Offline HTML Template (Design standard matching views/errors/restricted.php)
const OFFLINE_HTML = `<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Koneksi Terputus — KEREN SNACK ERP</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="icon" type="image/png" href="./assets/favicon/favicon-96x96.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">

  <style>
    :root {
      --bg: #090d16;
      --surface: #0f172a;
      --surface-card: rgba(15, 23, 42, 0.88);
      --border: rgba(255, 255, 255, 0.08);
      --border-hover: rgba(255, 255, 255, 0.16);
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      --text-dim: #64748b;
      --primary: #e11d48;
      --primary-glow: rgba(225, 29, 72, 0.25);
      --accent-amber: #f59e0b;
      --accent-cyan: #38bdf8;
      --accent-indigo: #6366f1;
      --font-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      --font-display: 'Outfit', sans-serif;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: var(--font-sans);
      background-color: var(--bg);
      color: var(--text-main);
      min-height: 100vh;
      min-height: 100dvh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      position: relative;
      overflow-x: hidden;
      background-image: 
        linear-gradient(to right, rgba(255, 255, 255, 0.08) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(255, 255, 255, 0.08) 1px, transparent 1px);
      background-size: 28px 28px;
    }

    .container {
      width: 100%;
      max-width: 540px;
      margin: 0 auto;
      text-align: center;
      position: relative;
      z-index: 10;
    }

    /* Glass Card */
    .error-card {
      background: var(--surface-card);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid var(--border);
      border-radius: 28px;
      padding: clamp(28px, 6vw, 44px) clamp(20px, 5vw, 36px);
      box-shadow: 
        0 25px 50px -12px rgba(0, 0, 0, 0.6),
        0 0 0 1px rgba(255, 255, 255, 0.03) inset;
      transition: border-color 0.3s ease, transform 0.3s ease;
    }

    .error-card:hover {
      border-color: var(--border-hover);
    }

    /* Offline Security Badge */
    .security-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(245, 158, 11, 0.12);
      border: 1px solid rgba(245, 158, 11, 0.35);
      color: #fbbf24;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      padding: 6px 14px;
      border-radius: 9999px;
      margin-bottom: 20px;
    }

    .security-badge i {
      font-size: 11px;
      animation: pulse-icon 2s infinite ease-in-out;
    }

    @keyframes pulse-icon {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.4; transform: scale(0.92); }
    }

    /* ==========================================================================
       VECTOR ANIMATION: PERSON SEARCHING SIGNAL / OFFLINE
       ========================================================================== */
    .illustration-wrap {
      position: relative;
      width: 200px;
      height: 180px;
      margin: 0 auto 16px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .svg-character {
      width: 100%;
      height: 100%;
      overflow: visible;
    }

    .char-head {
      transform-origin: 100px 78px;
      animation: head-tilt 4.5s ease-in-out infinite;
    }

    @keyframes head-tilt {
      0%, 100% { transform: rotate(0deg); }
      20% { transform: rotate(-9deg) translateY(-2px); }
      45% { transform: rotate(0deg); }
      70% { transform: rotate(11deg) translateY(-1px); }
      85% { transform: rotate(0deg); }
    }

    .char-eyes {
      animation: eyes-look 4.5s ease-in-out infinite;
    }

    @keyframes eyes-look {
      0%, 100% { transform: translate(0, 0); }
      20% { transform: translate(-3.5px, 0); }
      45% { transform: translate(0, 0); }
      70% { transform: translate(4px, 0); }
      85% { transform: translate(0, 0); }
    }

    .magnifier-arm {
      transform-origin: 65px 105px;
      animation: search-arm 4s ease-in-out infinite;
    }

    @keyframes search-arm {
      0%, 100% { transform: rotate(0deg); }
      30% { transform: rotate(-12deg) translateY(-3px); }
      65% { transform: rotate(8deg) translateY(2px); }
    }

    .magnifier-glass {
      transform-origin: 127px 97px;
      animation: glass-pulse 3s ease-in-out infinite alternate;
    }

    @keyframes glass-pulse {
      0% { transform: scale(1); filter: drop-shadow(0 0 4px rgba(245, 158, 11, 0.3)); }
      100% { transform: scale(1.06); filter: drop-shadow(0 0 12px rgba(245, 158, 11, 0.7)); }
    }

    .wifi-off-signal {
      animation: float-signal 3s ease-in-out infinite;
    }

    @keyframes float-signal {
      0%, 100% { transform: translateY(0); opacity: 0.8; }
      50% { transform: translateY(-6px); opacity: 1; }
    }

    .radar-ring {
      transform-origin: 100px 158px;
      animation: radar-expand 3s ease-out infinite;
    }

    @keyframes radar-expand {
      0% { r: 18; opacity: 0.8; stroke-width: 1.5; }
      100% { r: 52; opacity: 0; stroke-width: 0.5; }
    }

    /* ==========================================================================
       TYPOGRAPHY & CONTENT
       ========================================================================== */
    .error-title {
      font-family: var(--font-display);
      font-size: clamp(22px, 5vw, 28px);
      font-weight: 800;
      line-height: 1.25;
      color: var(--text-main);
      margin-bottom: 10px;
      letter-spacing: -0.02em;
    }

    .error-desc {
      font-size: 14px;
      color: var(--text-muted);
      line-height: 1.6;
      margin-bottom: 22px;
      max-width: 440px;
      margin-left: auto;
      margin-right: auto;
    }

    /* Current Path Display */
    .path-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(0, 0, 0, 0.4);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 10px;
      padding: 8px 14px;
      margin-bottom: 26px;
      max-width: 100%;
      overflow: hidden;
      font-family: 'JetBrains Mono', Consolas, monospace;
      font-size: 12px;
      color: #e2e8f0;
    }

    .path-pill i {
      color: var(--accent-amber);
      flex-shrink: 0;
      font-size: 11px;
    }

    .path-pill span {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 320px;
    }

    /* Action Buttons */
    .actions-group {
      display: flex;
      flex-direction: column;
      gap: 10px;
      width: 100%;
    }

    @media (min-width: 440px) {
      .actions-group {
        flex-direction: row;
        justify-content: center;
      }
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      font-family: var(--font-sans);
      font-size: 13.5px;
      font-weight: 600;
      padding: 11px 22px;
      border-radius: 12px;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      border: none;
      outline: none;
    }

    .btn-primary {
      background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
      color: #ffffff;
      box-shadow: 0 4px 16px rgba(225, 29, 72, 0.35);
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(225, 29, 72, 0.5);
      color: #ffffff;
    }

    .btn-secondary {
      background: rgba(255, 255, 255, 0.05);
      color: var(--text-main);
      border: 1px solid var(--border);
    }

    .btn-secondary:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: var(--border-hover);
      color: #ffffff;
      transform: translateY(-1px);
    }

    .footer-note {
      margin-top: 24px;
      font-size: 11.5px;
      color: var(--text-dim);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .footer-note i {
      color: #f59e0b;
      font-size: 9px;
    }

    .spin-fast {
      animation: fa-spin 0.8s infinite linear;
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="error-card">
      <!-- Security & Network Badge -->
      <div class="security-badge">
        <i class="fa-solid fa-wifi"></i>
        <span>Koneksi Terputus &bull; Offline Mode</span>
      </div>

      <!-- Vector Character Illustration: Signal Searching -->
      <div class="illustration-wrap" aria-hidden="true">
        <svg class="svg-character" viewBox="0 0 200 180" fill="none" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <radialGradient id="floorGrad" cx="50%" cy="50%" r="50%">
              <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.25"/>
              <stop offset="100%" stop-color="#f59e0b" stop-opacity="0"/>
            </radialGradient>
            <linearGradient id="bodyGrad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#3b82f6"/>
              <stop offset="100%" stop-color="#1d4ed8"/>
            </linearGradient>
            <linearGradient id="skinGrad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#fed7aa"/>
              <stop offset="100%" stop-color="#fdba74"/>
            </linearGradient>
            <linearGradient id="hairGrad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#334155"/>
              <stop offset="100%" stop-color="#0f172a"/>
            </linearGradient>
            <linearGradient id="glassGrad" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="rgba(245, 158, 11, 0.45)"/>
              <stop offset="100%" stop-color="rgba(217, 119, 6, 0.15)"/>
            </linearGradient>
          </defs>

          <!-- Floor Base Shadow & Ripple -->
          <ellipse cx="100" cy="158" rx="55" ry="11" fill="url(#floorGrad)"/>
          <circle class="radar-ring" cx="100" cy="158" r="28" stroke="#f59e0b" fill="none"/>

          <!-- Disconnected Signal Graphic Top Right -->
          <g class="wifi-off-signal" transform="translate(136, 42)">
            <path d="M-6 8 C-1 4, 7 4, 12 8" stroke="#f43f5e" stroke-width="2.2" stroke-linecap="round" fill="none" opacity="0.4"/>
            <path d="M-10 4 C-3 -1, 11 -1, 18 4" stroke="#f43f5e" stroke-width="2.2" stroke-linecap="round" fill="none"/>
            <circle cx="3" cy="14" r="2.2" fill="#f43f5e"/>
            <line x1="-12" y1="18" x2="20" y2="-2" stroke="#fb7185" stroke-width="2.2" stroke-linecap="round"/>
          </g>

          <!-- Body / Torso -->
          <g id="character-body">
            <path d="M72 152C70 126 76 112 88 108C94 106 106 106 112 108C124 112 130 126 128 152H72Z" fill="url(#bodyGrad)"/>
            <path d="M94 107L100 116L106 107" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" opacity="0.6"/>
          </g>

          <!-- Head & Face (Animated Group) -->
          <g class="char-head" id="character-head">
            <rect x="95" y="96" width="10" height="13" rx="4" fill="url(#skinGrad)"/>
            <circle cx="100" cy="78" r="19" fill="url(#skinGrad)"/>
            <path d="M81 76C81 65 89 57 100 57C111 57 119 65 119 76C119 78 117 76 115 74C111 70 106 72 101 69C96 66 90 71 85 75C83 77 81 77 81 76Z" fill="url(#hairGrad)"/>
            <g class="char-eyes">
              <path d="M89 71C91 69 94 70 96 71" stroke="#334155" stroke-width="1.4" stroke-linecap="round"/>
              <path d="M104 70C106 68 109 69 111 72" stroke="#334155" stroke-width="1.4" stroke-linecap="round"/>
              <circle cx="93" cy="77" r="2.2" fill="#0f172a"/>
              <circle cx="92.2" cy="76.2" r="0.7" fill="#ffffff"/>
              <circle cx="107" cy="77" r="2.2" fill="#0f172a"/>
              <circle cx="106.2" cy="76.2" r="0.7" fill="#ffffff"/>
            </g>
            <ellipse cx="100" cy="88" rx="2.5" ry="3" fill="#e11d48" opacity="0.8"/>
          </g>

          <!-- Arm Holding Magnifying Glass -->
          <g class="magnifier-arm">
            <path d="M78 116C72 122 68 132 82 135" stroke="url(#bodyGrad)" stroke-width="8" stroke-linecap="round"/>
            <circle cx="82" cy="134" r="5" fill="url(#skinGrad)"/>
            <line x1="84" y1="133" x2="114" y2="108" stroke="#f59e0b" stroke-width="4.5" stroke-linecap="round"/>
            <circle class="magnifier-glass" cx="127" cy="97" r="16" stroke="#f59e0b" stroke-width="3.5" fill="url(#glassGrad)"/>
            <path d="M118 90C121 86 127 85 132 87" stroke="#ffffff" stroke-width="2" stroke-linecap="round" opacity="0.75"/>
          </g>
        </svg>
      </div>

      <!-- Headline -->
      <h1 class="error-title">Koneksi Internet Terputus</h1>

      <!-- Explanatory Text -->
      <p class="error-desc">
        Perangkat Anda saat ini sedang tidak terhubung ke jaringan internet. Demi menjaga integritas data keuangan &amp; stok riil, transaksi ditangguhkan hingga koneksi pulih.
      </p>

      <!-- Path Info -->
      <div class="path-pill">
        <i class="fa-solid fa-cloud-slash"></i>
        <span id="current-url">Menunggu koneksi online...</span>
      </div>

      <!-- Action Buttons -->
      <div class="actions-group">
        <button onclick="handleRetry()" class="btn btn-primary" id="btn-retry" type="button">
          <i class="fa-solid fa-rotate-right" id="btn-retry-icon"></i>
          <span id="btn-retry-label">Coba Hubungkan Kembali</span>
        </button>
        <button onclick="handleGoBack()" class="btn btn-secondary" type="button">
          <i class="fa-solid fa-arrow-left"></i>
          <span>Halaman Sebelumnya</span>
        </button>
      </div>

      <!-- Footer Compliance Note -->
      <div class="footer-note">
        <i class="fa-solid fa-shield-check"></i>
        <span>Sistem Keamanan &amp; Integritas Data &bull; KEREN SNACK ERP</span>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('current-url').textContent = window.location.pathname || 'Halaman ERP';

    function handleRetry() {
      const btn = document.getElementById('btn-retry');
      const icon = document.getElementById('btn-retry-icon');
      const label = document.getElementById('btn-retry-label');
      
      btn.disabled = true;
      icon.classList.add('spin-fast');
      label.textContent = 'Memeriksa sinyal...';

      // Perform real connectivity check (no fake timeout)
      fetch('./assets/favicon/favicon.ico?_ping=' + Date.now(), { method: 'HEAD', cache: 'no-store' })
        .then(() => {
          label.textContent = 'Koneksi Terhubung! Memuat...';
          setTimeout(() => { window.location.reload(); }, 300);
        })
        .catch(() => {
          if (navigator.onLine) {
            window.location.reload();
            return;
          }
          icon.classList.remove('spin-fast');
          label.textContent = 'Masih Offline (Coba Lagi)';
          btn.disabled = false;
        });
    }

    function handleGoBack() {
      if (window.history.length > 1) {
        window.history.back();
      } else {
        window.location.href = './dashboard';
      }
    }

    // Auto-reload when connection is restored
    window.addEventListener('online', function() {
      const label = document.getElementById('btn-retry-label');
      if (label) label.textContent = 'Sinyal Kembali! Memuat...';
      setTimeout(() => { window.location.reload(); }, 600);
    });
  </script>
</body>
</html>`;

// Install Event (Pre-cache core assets)
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

// Activate Event (Cleanup old cache versions & take immediate control)
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME)
          .map((name) => {
            console.log('[SW] Deleting legacy cache:', name);
            return caches.delete(name);
          })
      );
    }).then(() => self.clients.claim())
  );
});

// Message Event (Support instant skipWaiting from UI update notifier)
self.addEventListener('message', (event) => {
  if (event.data && event.data.action === 'skipWaiting') {
    self.skipWaiting();
  }
});

// Fetch Event (Zero false-positive online-safe strategy)
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
    url.hostname.includes('cdnjs.cloudflare.com') ||
    url.pathname.endsWith('.css') ||
    url.pathname.endsWith('.js') ||
    url.pathname.endsWith('.png') ||
    url.pathname.endsWith('.jpg') ||
    url.pathname.endsWith('.svg') ||
    url.pathname.endsWith('.ico') ||
    url.pathname.endsWith('.woff2') ||
    url.pathname.endsWith('.webmanifest')
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

  // Strategy 2: HTML Navigation / Dynamic PHP routes -> Network-Only (Live ERP Data)
  // ZERO FALSE-POSITIVE: Only fallback to offline screen if network fetch natively fails (offline / unreachable)
  if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(request).catch((err) => {
        // True physical network failure / offline
        return new Response(OFFLINE_HTML, {
          status: 503,
          statusText: 'Service Unavailable (Offline)',
          headers: {
            'Content-Type': 'text/html; charset=UTF-8',
            'Cache-Control': 'no-store, no-cache, must-revalidate'
          }
        });
      })
    );
    return;
  }

  // Default: Direct Network Fetch
  event.respondWith(fetch(request));
});
