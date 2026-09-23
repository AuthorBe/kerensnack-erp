<?php
/**
 * views/layouts/pwa_modal.php
 * Modal Interaktif & Responsif Panduan Instalasi Aplikasi (PWA)
 * Pola Desain Kanonikal: Supabase Minimalist Design System
 */
?>
<div id="pwaInstallModal" 
     style="display:none !important;position:fixed;top:0;left:0;right:0;bottom:0;width:100vw;height:100vh;z-index:99999;align-items:center;justify-content:center;padding:12px;background:rgba(15,23,42,0.65);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);"
     role="dialog" 
     aria-modal="true" 
     aria-labelledby="pwaModalTitle">
    
    <div class="card overflow-hidden relative" 
         style="max-width:460px;width:100%;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:20px;box-shadow:0 25px 50px -12px rgba(0,0,0,0.35);display:flex;flex-direction:column;max-height:calc(100vh - 24px);"
         onclick="event.stopPropagation()">

        <!-- MODAL HEADER (Tanpa Lis Atas & Tanpa Tombol X) -->
        <div class="p-4 sm:p-5 border-b border-hairline" style="background:var(--color-canvas);">
            <div class="flex items-center gap-3">
                <div style="width:40px;height:40px;border-radius:12px;background:rgba(37,99,235,0.1);color:var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(37,99,235,0.2);">
                    <i data-lucide="smartphone" style="width:20px;height:20px;"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 id="pwaModalTitle" style="font-size:15px;font-weight:800;color:var(--color-ink);margin:0;line-height:1.2;">Pasang Aplikasi Mobile</h3>
                        <span class="badge badge-primary font-mono text-[9px]" style="padding:2px 6px;">PWA</span>
                    </div>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.35;">Jalankan aplikasi mandiri tanpa browser bar di layar HP Anda.</p>
                </div>
            </div>
        </div>

        <!-- MODAL BODY -->
        <div class="p-4 sm:p-5 space-y-3.5 overflow-y-auto custom-scrollbar" style="flex:1;">
            
            <!-- OS Segmented Selector (Touch & Mobile Responsive) -->
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

            <!-- TAB 1: ANDROID -->
            <div id="pwaTabContentAndroid" class="space-y-2.5">
                <div class="p-3.5 rounded-xl space-y-3" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                    <div class="flex items-start gap-2.5">
                        <div class="font-mono" style="width:22px;height:22px;border-radius:6px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-primary);font-weight:800;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">1</div>
                        <div style="font-size:12px;color:var(--color-ink);line-height:1.45;">
                            Buka tautan ini di <strong>Google Chrome</strong> atau <strong>Samsung Internet</strong> pada HP Anda.
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <div class="font-mono" style="width:22px;height:22px;border-radius:6px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-primary);font-weight:800;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">2</div>
                        <div style="font-size:12px;color:var(--color-ink);line-height:1.45;">
                            Tekan tombol <strong>Menu Titik Tiga (⋮)</strong> di sudut kanan atas browser.
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <div class="font-mono" style="width:22px;height:22px;border-radius:6px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-primary);font-weight:800;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">3</div>
                        <div style="font-size:12px;color:var(--color-ink);line-height:1.45;">
                            Pilih menu <strong>"Pasang Aplikasi"</strong> atau <strong>"Tambahkan ke Layar Utama"</strong>.
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5" style="padding-top:4px;border-top:1px dashed var(--color-hairline);">
                        <div class="font-mono" style="width:22px;height:22px;border-radius:6px;background:rgba(16,185,129,0.15);color:var(--color-success);font-weight:800;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">✓</div>
                        <div style="font-size:12px;color:var(--color-ink);line-height:1.45;">
                            Ikon <strong>Keren One</strong> akan langsung terpasang di layar utama HP Anda.
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: IOS -->
            <div id="pwaTabContentIos" class="space-y-2.5" style="display:none;">
                <div class="p-3.5 rounded-xl space-y-3" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                    <div class="flex items-start gap-2.5">
                        <div class="font-mono" style="width:22px;height:22px;border-radius:6px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-primary);font-weight:800;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">1</div>
                        <div style="font-size:12px;color:var(--color-ink);line-height:1.45;">
                            Buka tautan ini di browser <strong>Safari</strong> pada iPhone atau iPad Anda.
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <div class="font-mono" style="width:22px;height:22px;border-radius:6px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-primary);font-weight:800;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">2</div>
                        <div style="font-size:12px;color:var(--color-ink);line-height:1.45;">
                            Tekan tombol <strong>Bagikan / Share (ikon ⎋)</strong> di bilah navigasi bawah Safari.
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <div class="font-mono" style="width:22px;height:22px;border-radius:6px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-primary);font-weight:800;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">3</div>
                        <div style="font-size:12px;color:var(--color-ink);line-height:1.45;">
                            Gulir ke bawah lalu pilih menu <strong>"Tambah ke Layar Utama" (Add to Home Screen)</strong>.
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5" style="padding-top:4px;border-top:1px dashed var(--color-hairline);">
                        <div class="font-mono" style="width:22px;height:22px;border-radius:6px;background:rgba(16,185,129,0.15);color:var(--color-success);font-weight:800;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">✓</div>
                        <div style="font-size:12px;color:var(--color-ink);line-height:1.45;">
                            Tekan <strong>"Tambah"</strong> di sudut kanan atas untuk menyelesaikan instalasi.
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: DESKTOP -->
            <div id="pwaTabContentDesktop" class="space-y-2.5" style="display:none;">
                <div class="p-3.5 rounded-xl space-y-3" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                    <div class="flex items-start gap-2.5">
                        <div class="font-mono" style="width:22px;height:22px;border-radius:6px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-primary);font-weight:800;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">1</div>
                        <div style="font-size:12px;color:var(--color-ink);line-height:1.45;">
                            Gunakan browser <strong>Google Chrome</strong> atau <strong>Microsoft Edge</strong>.
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <div class="font-mono" style="width:22px;height:22px;border-radius:6px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-primary);font-weight:800;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">2</div>
                        <div style="font-size:12px;color:var(--color-ink);line-height:1.45;">
                            Klik ikon <strong>Instal (📥)</strong> di sebelah kanan kolom URL / address bar.
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5" style="padding-top:4px;border-top:1px dashed var(--color-hairline);">
                        <div class="font-mono" style="width:22px;height:22px;border-radius:6px;background:rgba(16,185,129,0.15);color:var(--color-success);font-weight:800;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">✓</div>
                        <div style="font-size:12px;color:var(--color-ink);line-height:1.45;">
                            Klik <strong>"Instal"</strong>. ERP akan terbuka dalam jendela aplikasi mandiri.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Interaktif: Salin Tautan Cepat -->
            <div class="p-2.5 rounded-xl flex items-center justify-between gap-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center gap-2 min-w-0">
                    <i data-lucide="link" style="width:13px;height:13px;color:var(--color-ink-mute);flex-shrink:0;"></i>
                    <span id="pwaModalDomainText" class="font-mono text-xs truncate" style="color:var(--color-ink-mute);"><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'kerensnack-erp.test') ?></span>
                </div>
                <button type="button" 
                        onclick="copyAppUrl()" 
                        class="btn btn-secondary btn-sm" 
                        style="font-size:11px;font-weight:700;height:26px;padding:0 8px;gap:4px;flex-shrink:0;">
                    <i data-lucide="copy" style="width:12px;height:12px;"></i>
                    <span>Salin Link</span>
                </button>
            </div>

        </div>

        <!-- MODAL FOOTER (Tombol Tutup & Pasang Otomatis Berdampingan di Kanan) -->
        <div class="p-3.5 sm:p-4 border-t border-hairline flex items-center justify-end gap-2" style="background:var(--color-canvas);">
            <button type="button" 
                    onclick="closePwaInstallModal()" 
                    class="btn btn-secondary btn-sm" 
                    style="font-weight:700;height:36px;padding:0 14px;">
                Tutup
            </button>
            <button type="button" 
                    id="btnPwaTriggerDirect" 
                    onclick="handlePwaDirectInstall()" 
                    class="btn btn-primary btn-sm" 
                    style="font-weight:700;height:36px;padding:0 16px;gap:6px;">
                <i data-lucide="download" style="width:14px;height:14px;"></i>
                <span>Pasang Otomatis</span>
            </button>
        </div>

    </div>
</div>

<script>
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

window.openPwaInstallModal = function() {
    const modal = document.getElementById('pwaInstallModal');
    if (modal) {
        // Auto-switch to user's device OS tab
        switchPwaTab(detectUserDeviceOS());
        // Dynamic live domain detection
        const domainEl = document.getElementById('pwaModalDomainText');
        if (domainEl && window.location.host) {
            domainEl.textContent = window.location.host;
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
};

window.copyAppUrl = function() {
    const url = window.location.origin || window.location.href;
    navigator.clipboard.writeText(url).then(() => {
        if (window.showToast) {
            showToast('Tautan berhasil disalin ke clipboard! 📋', 'success');
        } else {
            alert('Tautan berhasil disalin!');
        }
    }).catch(() => {
        prompt('Salin tautan ini:', url);
    });
};

window.handlePwaDirectInstall = function() {
    if (window.App && window.App._deferredInstallPrompt) {
        window.App._deferredInstallPrompt.prompt();
        window.App._deferredInstallPrompt.userChoice.then((choice) => {
            if (choice.outcome === 'accepted') {
                if (window.showToast) showToast('Aplikasi berhasil dipasang! 🎉', 'success');
            }
            window.App._deferredInstallPrompt = null;
            closePwaInstallModal();
        });
    } else {
        if (window.showToast) {
            showToast('Silakan ikuti panduan langkah di atas untuk menambahkan aplikasi ke layar HP kamu 📲', 'info');
        } else {
            alert('Silakan ikuti panduan langkah di atas untuk memasang aplikasi ke layar HP kamu.');
        }
    }
};

// Close ONLY via explicit button or ESC key (Backdrop click is blocked as per ERP standards)
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closePwaInstallModal();
});
</script>
