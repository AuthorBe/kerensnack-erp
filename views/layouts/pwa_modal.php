<?php
/**
 * views/layouts/pwa_modal.php
 * Modal Konfirmasi & Panduan Pemasangan Aplikasi PWA (Progressive Web App)
 * Pola Desain Kanonikal: Supabase Minimalist Design System & Customer Order Detail Modal Pattern
 */
?>
<?php
$currentAppHostPath = htmlspecialchars(($_SERVER['HTTP_HOST'] ?? 'preview.ajisakha.my.id') . \App\Core\Router::getBasePath());
?>
<!-- PWA INSTALLATION CONFIRMATION MODAL -->
<div id="pwaInstallModal" 
     class="modal-backdrop"
     style="display:none !important;" 
     role="dialog" 
     aria-modal="true" 
     aria-labelledby="pwaModalTitle">
    
    <div class="modal-box modal-box-lg" 
         style="max-width:520px;width:100%;padding:0;border-radius:20px;overflow:hidden;display:flex;flex-direction:column;max-height:min(90vh, 680px);border:1px solid var(--color-hairline);box-shadow:0 25px 60px -15px rgba(0,0,0,0.3);-webkit-overflow-scrolling:touch;"
         onclick="event.stopPropagation()">

        <!-- MOBILE PULL HANDLE -->
        <div class="sm:hidden w-full flex justify-center pt-3 pb-1 flex-shrink-0" style="background:var(--color-canvas);">
            <div style="width:40px;height:4px;border-radius:2px;background:var(--color-hairline-strong);"></div>
        </div>

        <!-- 1. MODAL HEADER (Tanpa Tombol X Sesuai Standar Konfirmasi ERP) -->
        <div style="padding:16px 20px;border-bottom:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:space-between;background:var(--color-canvas);flex-shrink:0;gap:12px;">
            <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
                <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg, rgba(239,68,68,0.12) 0%, rgba(30,58,138,0.12) 100%);border:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                    <img src="<?= \App\Core\Router::url('/assets/favicon/web-app-manifest-192x192.png') ?>" 
                         alt="Logo Keren One" 
                         style="width:30px;height:30px;object-fit:contain;border-radius:6px;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div style="display:none;width:100%;height:100%;align-items:center;justify-content:center;color:var(--color-primary);font-weight:900;font-size:18px;">
                        K
                    </div>
                </div>
                <div style="min-width:0;flex:1;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <h3 id="pwaModalTitle" class="modal-title font-black" style="font-size:15.5px;color:var(--color-ink);letter-spacing:-0.02em;margin:0;">
                            Pasang Aplikasi KEREN ONE
                        </h3>
                        <span class="badge badge-primary font-mono" style="font-size:9.5px;font-weight:800;padding:2px 7px;border-radius:6px;">
                            PWA
                        </span>
                    </div>
                    <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;display:flex;align-items:center;gap:5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <i data-lucide="shield-check" style="width:13px;height:13px;color:var(--color-success);flex-shrink:0;"></i>
                        <span id="pwaModalDomainLabel" class="font-mono" style="font-weight:600;"><?= $currentAppHostPath ?></span>
                        <span style="opacity:0.4;">&bull;</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold text-[11px]">Terverifikasi &amp; Aman</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. TAB NAVIGATION BAR -->
        <div class="modal-tab-nav custom-scrollbar">
            <button type="button" 
                    id="pwaMainTabBtnOverview" 
                    onclick="switchPwaMainTab('overview')" 
                    class="modal-tab-btn is-active">
                <i data-lucide="sparkles" style="width:14px;height:14px;"></i>
                <span>Ringkasan &amp; Pasang</span>
            </button>
            <button type="button" 
                    id="pwaMainTabBtnGuide" 
                    onclick="switchPwaMainTab('guide')" 
                    class="modal-tab-btn">
                <i data-lucide="book-open" style="width:14px;height:14px;"></i>
                <span>Panduan Manual</span>
                <span class="badge" style="font-size:9.5px;padding:1px 6px;border-radius:8px;">OS</span>
            </button>
            <button type="button" 
                    id="pwaMainTabBtnShare" 
                    onclick="switchPwaMainTab('share')" 
                    class="modal-tab-btn">
                <i data-lucide="share-2" style="width:14px;height:14px;"></i>
                <span>Bagikan Link</span>
            </button>
        </div>

        <!-- 3. TAB BODIES -->
        <div class="modal-tab-body custom-scrollbar" style="padding:18px 20px;flex:1;overflow-y:auto;-webkit-overflow-scrolling:touch;">

            <!-- TAB 1: OVERVIEW & PASANG -->
            <div id="pwaMainTabContentOverview" class="space-y-3.5">
                
                <!-- Hero Device & System Status Card -->
                <div class="p-3.5 rounded-2xl flex items-center justify-between gap-3" 
                     style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                    <div class="flex items-center gap-3 min-w-0">
                        <div style="width:38px;height:38px;border-radius:10px;background:rgba(30,58,138,0.1);color:#1e3a8a;border:1px solid rgba(30,58,138,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="monitor-smartphone" style="width:18px;height:18px;"></i>
                        </div>
                        <div class="min-w-0">
                            <div style="font-size:13px;font-weight:700;color:var(--color-ink);line-height:1.2;">KEREN ONE — Enterprise ERP</div>
                            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">UD Musamil Makhrojan</div>
                        </div>
                    </div>
                    <div class="flex flex-col items-end flex-shrink-0">
                        <span id="pwaDeviceDetectBadge" class="badge badge-secondary text-[10px] font-bold" style="padding:3px 8px;border-radius:8px;">
                            <span id="pwaDeviceDetectText">Desktop / PC</span>
                        </span>
                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold mt-1 flex items-center gap-1">
                            <span style="width:6px;height:6px;border-radius:50%;background:#10b981;display:inline-block;"></span>
                            Sistem Siap
                        </span>
                    </div>
                </div>

                <!-- 4 Value Highlight Cards (2x2 Grid) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <!-- Point 1 -->
                    <div class="p-3 rounded-xl flex items-start gap-2.5" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
                        <div style="width:28px;height:28px;border-radius:8px;background:rgba(239,68,68,0.1);color:#ef4444;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                            <i data-lucide="zap" style="width:14px;height:14px;"></i>
                        </div>
                        <div class="min-w-0">
                            <div style="font-size:12px;font-weight:700;color:var(--color-ink);">Akses Mandiri &amp; Cepat</div>
                            <div style="font-size:11px;color:var(--color-ink-mute);line-height:1.35;margin-top:2px;">Buka langsung di layar tanpa terganggu address bar browser.</div>
                        </div>
                    </div>

                    <!-- Point 2 -->
                    <div class="p-3 rounded-xl flex items-start gap-2.5" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
                        <div style="width:28px;height:28px;border-radius:8px;background:rgba(30,58,138,0.1);color:#1e3a8a;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                            <i data-lucide="gauge" style="width:14px;height:14px;"></i>
                        </div>
                        <div class="min-w-0">
                            <div style="font-size:12px;font-weight:700;color:var(--color-ink);">Performa Teroptimasi</div>
                            <div style="font-size:11px;color:var(--color-ink-mute);line-height:1.35;margin-top:2px;">Aset tersimpan di memori lokal, pemuatan halaman instan.</div>
                        </div>
                    </div>

                    <!-- Point 3 -->
                    <div class="p-3 rounded-xl flex items-start gap-2.5" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
                        <div style="width:28px;height:28px;border-radius:8px;background:rgba(16,185,129,0.1);color:#10b981;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                            <i data-lucide="bell" style="width:14px;height:14px;"></i>
                        </div>
                        <div class="min-w-0">
                            <div style="font-size:12px;font-weight:700;color:var(--color-ink);">Notifikasi Operasional</div>
                            <div style="font-size:11px;color:var(--color-ink-mute);line-height:1.35;margin-top:2px;">Siap menerima info faktur, pengiriman, dan penagihan.</div>
                        </div>
                    </div>

                    <!-- Point 4 -->
                    <div class="p-3 rounded-xl flex items-start gap-2.5" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
                        <div style="width:28px;height:28px;border-radius:8px;background:rgba(245,158,11,0.1);color:#f59e0b;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                            <i data-lucide="hard-drive" style="width:14px;height:14px;"></i>
                        </div>
                        <div class="min-w-0">
                            <div style="font-size:12px;font-weight:700;color:var(--color-ink);">Ringan &amp; Hemat Kuota</div>
                            <div style="font-size:11px;color:var(--color-ink-mute);line-height:1.35;margin-top:2px;">Ukuran di bawah 5MB, tidak membebani memori HP/PC.</div>
                        </div>
                    </div>
                </div>

                <!-- Info Helper Box -->
                <div class="p-3 rounded-xl flex items-start gap-2.5" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                    <i data-lucide="info" style="width:15px;height:15px;color:var(--color-primary);flex-shrink:0;margin-top:1px;"></i>
                    <div style="font-size:11.5px;color:var(--color-ink-mute);line-height:1.4;">
                        Tekan tombol <strong>"Pasang Sekarang"</strong> di bawah untuk memasang aplikasi secara otomatis pada perangkat Anda.
                    </div>
                </div>

            </div>

            <!-- TAB 2: PANDUAN MANUAL -->
            <div id="pwaMainTabContentGuide" class="space-y-3" style="display:none;">
                
                <!-- OS Selector Segmented Control -->
                <div class="p-1 rounded-xl flex gap-1" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                    <button type="button" 
                            id="pwaTabBtnAndroid" 
                            onclick="switchPwaTab('android')" 
                            class="btn btn-primary btn-sm flex-1 justify-center" 
                            style="font-size:11.5px;font-weight:700;height:32px;border-radius:8px;padding:0 6px;gap:5px;">
                        <i data-lucide="smartphone" style="width:13px;height:13px;flex-shrink:0;"></i>
                        <span>Android</span>
                    </button>
                    <button type="button" 
                            id="pwaTabBtnIos" 
                            onclick="switchPwaTab('ios')" 
                            class="btn btn-ghost btn-sm flex-1 justify-center" 
                            style="font-size:11.5px;font-weight:600;height:32px;border-radius:8px;color:var(--color-ink-mute);padding:0 6px;gap:5px;">
                        <i data-lucide="apple" style="width:13px;height:13px;flex-shrink:0;"></i>
                        <span>iPhone / iOS</span>
                    </button>
                    <button type="button" 
                            id="pwaTabBtnDesktop" 
                            onclick="switchPwaTab('desktop')" 
                            class="btn btn-ghost btn-sm flex-1 justify-center" 
                            style="font-size:11.5px;font-weight:600;height:32px;border-radius:8px;color:var(--color-ink-mute);padding:0 6px;gap:5px;">
                        <i data-lucide="monitor" style="width:13px;height:13px;flex-shrink:0;"></i>
                        <span>Laptop / PC</span>
                    </button>
                </div>

                <!-- SUB-TAB 1: ANDROID -->
                <div id="pwaTabContentAndroid" class="space-y-2">
                    <div class="p-3.5 rounded-xl space-y-2.5" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex items-start gap-2.5">
                            <div class="font-mono font-bold" style="width:20px;height:20px;border-radius:6px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-primary);font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">1</div>
                            <div style="font-size:12px;color:var(--color-ink);line-height:1.4;">
                                Buka ERP di <strong>Google Chrome</strong> atau <strong>Samsung Internet</strong> pada HP Anda.
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <div class="font-mono font-bold" style="width:20px;height:20px;border-radius:6px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-primary);font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">2</div>
                            <div style="font-size:12px;color:var(--color-ink);line-height:1.4;">
                                Tekan tombol <strong>Menu Titik Tiga (⋮)</strong> di sudut kanan atas browser.
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <div class="font-mono font-bold" style="width:20px;height:20px;border-radius:6px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-primary);font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">3</div>
                            <div style="font-size:12px;color:var(--color-ink);line-height:1.4;">
                                Pilih menu <strong>"Pasang Aplikasi"</strong> atau <strong>"Tambahkan ke Layar Utama"</strong>.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUB-TAB 2: IOS (Safari iPhone / iPad) -->
                <div id="pwaTabContentIos" class="space-y-2" style="display:none;">
                    <div class="p-3.5 rounded-xl space-y-2.5" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex items-start gap-2.5">
                            <div class="font-mono font-bold" style="width:20px;height:20px;border-radius:6px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-primary);font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">1</div>
                            <div style="font-size:12px;color:var(--color-ink);line-height:1.4;">
                                Buka ERP di browser <strong>Safari</strong> pada iPhone atau iPad Anda.
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <div class="font-mono font-bold" style="width:20px;height:20px;border-radius:6px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-primary);font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">2</div>
                            <div style="font-size:12px;color:var(--color-ink);line-height:1.4;">
                                Tekan tombol <strong>Bagikan / Share (ikon ⎋)</strong> di bilah navigasi bawah Safari.
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <div class="font-mono font-bold" style="width:20px;height:20px;border-radius:6px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-primary);font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">3</div>
                            <div style="font-size:12px;color:var(--color-ink);line-height:1.4;">
                                Gulir ke bawah lalu pilih <strong>"Tambah ke Layar Utama" (Add to Home Screen)</strong>.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUB-TAB 3: DESKTOP -->
                <div id="pwaTabContentDesktop" class="space-y-2" style="display:none;">
                    <div class="p-3.5 rounded-xl space-y-2.5" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex items-start gap-2.5">
                            <div class="font-mono font-bold" style="width:20px;height:20px;border-radius:6px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-primary);font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">1</div>
                            <div style="font-size:12px;color:var(--color-ink);line-height:1.4;">
                                Gunakan browser <strong>Google Chrome</strong> atau <strong>Microsoft Edge</strong> di Komputer/Laptop.
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <div class="font-mono font-bold" style="width:20px;height:20px;border-radius:6px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-primary);font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">2</div>
                            <div style="font-size:12px;color:var(--color-ink);line-height:1.4;">
                                Klik ikon <strong>Instal (📥)</strong> di sebelah kanan address bar / kolom URL browser.
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <div class="font-mono font-bold" style="width:20px;height:20px;border-radius:6px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-primary);font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">3</div>
                            <div style="font-size:12px;color:var(--color-ink);line-height:1.4;">
                                Klik tombol <strong>"Instal"</strong>. Aplikasi akan terbuka di jendela mandiri.
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- TAB 3: BAGIKAN LINK -->
            <div id="pwaMainTabContentShare" class="space-y-3.5" style="display:none;">
                <div class="p-3.5 rounded-xl space-y-3" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                    <div style="font-size:12.5px;font-weight:700;color:var(--color-ink);">Bagikan Tautan ke Tim Operasional</div>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);line-height:1.4;">
                        Kirimkan tautan resmi aplikasi ERP kepada staf gudang, sales lapangan, atau pengemudi armada untuk instalasi langsung.
                    </p>
                    <div class="p-2.5 rounded-xl flex items-center justify-between gap-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
                        <div class="flex items-center gap-2 min-w-0">
                            <i data-lucide="link" style="width:14px;height:14px;color:var(--color-ink-mute);flex-shrink:0;"></i>
                            <span id="pwaModalDomainText" class="font-mono text-xs truncate" style="color:var(--color-ink-mute);">
                                <?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'preview.ajisakha.my.id') ?>
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <button type="button" 
                                    id="btnPwaShareApp" 
                                    onclick="shareAppUrl()" 
                                    class="btn btn-ghost btn-sm" 
                                    style="font-size:11px;font-weight:700;height:28px;padding:0 8px;gap:4px;"
                                    title="Bagikan Tautan">
                                <i data-lucide="share-2" style="width:12px;height:12px;"></i>
                                <span>Bagi</span>
                            </button>
                            <button type="button" 
                                    onclick="copyAppUrl()" 
                                    class="btn btn-secondary btn-sm" 
                                    style="font-size:11px;font-weight:700;height:28px;padding:0 10px;gap:4px;"
                                    title="Salin Tautan ke Clipboard">
                                <i data-lucide="copy" style="width:12px;height:12px;"></i>
                                <span>Salin</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- 4. MODAL FOOTER (Hanya Batal & Download di Sisi Kanan + iOS Safe-Area Padding) -->
        <div style="padding:14px 20px;padding-bottom:max(16px, env(safe-area-inset-bottom, 16px));border-top:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:flex-end;background:var(--color-canvas);flex-shrink:0;gap:10px;">
            <button type="button" 
                    onclick="closePwaInstallModal()" 
                    class="btn btn-secondary btn-sm" 
                    style="font-weight:700;height:38px;padding:0 18px;font-size:12.5px;">
                Batal
            </button>
            <button type="button" 
                    id="btnPwaTriggerDirect" 
                    onclick="handlePwaDirectInstall()" 
                    class="btn btn-primary btn-sm" 
                    style="font-weight:700;height:38px;padding:0 22px;gap:6px;font-size:12.5px;box-shadow:0 3px 10px rgba(30,58,138,0.22);">
                <i data-lucide="download" style="width:15px;height:15px;"></i>
                <span id="btnPwaTriggerDirectText">Pasang Sekarang</span>
            </button>
        </div>

    </div>
</div>

<script>
(function() {
    function detectUserDeviceOS() {
        const ua = navigator.userAgent || navigator.vendor || window.opera || '';
        if (/iPad|iPhone|iPod/.test(ua) && !window.MSStream) {
            return 'ios';
        }
        if (/android/i.test(ua)) {
            return 'android';
        }
        return 'desktop';
    }

    function updateDeviceDetectionUI() {
        const os = detectUserDeviceOS();
        const badgeText = document.getElementById('pwaDeviceDetectText');
        if (!badgeText) return;

        if (os === 'android') {
            badgeText.textContent = '📱 Android Terdeteksi';
        } else if (os === 'ios') {
            badgeText.textContent = '🍎 iPhone / iOS Safari';
        } else {
            badgeText.textContent = '💻 Desktop / PC';
        }
    }

    function getAppFullUrl() {
        const origin = window.location.origin;
        const basePath = (window.APP_BASE_PATH || '').replace(/\/$/, '');
        return origin + basePath;
    }

    function getAppDisplayHostPath() {
        const host = window.location.host;
        const basePath = (window.APP_BASE_PATH || '').replace(/\/$/, '');
        return host + basePath;
    }

    window.openPwaInstallModal = function() {
        const modal = document.getElementById('pwaInstallModal');
        if (modal) {
            const os = detectUserDeviceOS();
            updateDeviceDetectionUI();
            switchPwaTab(os);
            switchPwaMainTab('overview');

            // Dynamic live host and base path
            const domainEl = document.getElementById('pwaModalDomainText');
            const domainLabel = document.getElementById('pwaModalDomainLabel');
            const currentDisplay = getAppDisplayHostPath();
            if (domainEl) domainEl.textContent = currentDisplay;
            if (domainLabel) domainLabel.textContent = currentDisplay;

            // Periksa jika sudah dalam mode terpasang (standalone)
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            const actionBtnText = document.getElementById('btnPwaTriggerDirectText');
            if (isStandalone && actionBtnText) {
                actionBtnText.textContent = 'Aplikasi Terpasang ✓';
            }

            modal.style.setProperty('display', 'flex', 'important');
            document.body.style.overflow = 'hidden';
            if (window.lucide) lucide.createIcons();
        }
    };

    window.closePwaInstallModal = function() {
        const modal = document.getElementById('pwaInstallModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            document.body.style.overflow = '';
        }
    };

    window.switchPwaMainTab = function(tabName) {
        const tabs = ['overview', 'guide', 'share'];
        tabs.forEach(t => {
            const content = document.getElementById('pwaMainTabContent' + t.charAt(0).toUpperCase() + t.slice(1));
            const btn = document.getElementById('pwaMainTabBtn' + t.charAt(0).toUpperCase() + t.slice(1));
            if (content && btn) {
                if (t === tabName) {
                    content.style.display = 'block';
                    btn.classList.add('is-active');
                } else {
                    content.style.display = 'none';
                    btn.classList.remove('is-active');
                }
            }
        });
        if (window.lucide) lucide.createIcons();
    };

    window.switchPwaTab = function(tabName) {
        const tabs = ['android', 'ios', 'desktop'];
        tabs.forEach(t => {
            const content = document.getElementById('pwaTabContent' + t.charAt(0).toUpperCase() + t.slice(1));
            const btn = document.getElementById('pwaTabBtn' + t.charAt(0).toUpperCase() + t.slice(1));
            if (content && btn) {
                if (t === tabName) {
                    content.style.display = 'block';
                    btn.className = 'btn btn-primary btn-sm flex-1 justify-center';
                    btn.style.color = '#ffffff';
                    btn.style.fontWeight = '700';
                } else {
                    content.style.display = 'none';
                    btn.className = 'btn btn-ghost btn-sm flex-1 justify-center';
                    btn.style.color = 'var(--color-ink-mute)';
                    btn.style.fontWeight = '600';
                }
            }
        });
        if (window.lucide) lucide.createIcons();
    };

    window.copyAppUrl = function() {
        const url = getAppFullUrl();
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(() => {
                if (window.showToast) {
                    showToast('Tautan aplikasi berhasil disalin ke clipboard! 📋', 'success');
                } else {
                    alert('Tautan aplikasi berhasil disalin ke clipboard!');
                }
            }).catch(() => {
                prompt('Salin tautan aplikasi ini:', url);
            });
        } else {
            prompt('Salin tautan aplikasi ini:', url);
        }
    };

    window.shareAppUrl = function() {
        const shareData = {
            title: 'KEREN ONE ERP',
            text: 'Aplikasi Manajemen Operasional & Penjualan Keren Snack Indonesia',
            url: getAppFullUrl()
        };
        if (navigator.share) {
            navigator.share(shareData).catch(() => {});
        } else {
            window.copyAppUrl();
        }
    };

    window.handlePwaDirectInstall = function() {
        const os = detectUserDeviceOS();
        const deferredPrompt = (window.PWAEngine && window.PWAEngine._deferredInstallPrompt) 
                                || (window.App && window.App._deferredInstallPrompt);

        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choice) => {
                if (choice.outcome === 'accepted') {
                    if (window.showToast) {
                        showToast('Aplikasi KEREN ONE berhasil dipasang! 🎉', 'success');
                    }
                }
                if (window.PWAEngine) window.PWAEngine._deferredInstallPrompt = null;
                if (window.App) window.App._deferredInstallPrompt = null;
                closePwaInstallModal();
            });
        } else if (os === 'ios') {
            // Di iOS Safari tidak ada direct prompt API, tampilkan panduan Safari
            switchPwaMainTab('guide');
            switchPwaTab('ios');
            if (window.showToast) {
                showToast('Di iPhone/iPad: Tekan tombol Bagikan (⎋) di bawah Safari lalu "Tambah ke Layar Utama" 📲', 'info');
            }
        } else {
            // Jika prompt belum siap atau di desktop/android manual
            switchPwaMainTab('guide');
            switchPwaTab(os);
            if (window.showToast) {
                showToast('Silakan ikuti panduan langkah di layar untuk memasang aplikasi 📲', 'info');
            }
        }
    };

    // Close via ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closePwaInstallModal();
    });
})();
</script>
